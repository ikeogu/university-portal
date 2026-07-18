<?php

namespace App\Services\Academic;

use App\Domain\Grading\Grade;
use App\Domain\Grading\ScoreRow;
use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Score;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Support\Collection;

class StudentResultService
{
    public function __construct(private SemesterResultCalculator $calculator) {}

    /**
     * Every session the student has been enrolled in, oldest first, with the
     * level they were at — drives the result view's session selector.
     *
     * @return Collection<int, array{id: string, name: string, level: int}>
     */
    public function sessionsFor(Student $student): Collection
    {
        return StudentEnrollment::query()
            ->where('student_id', $student->id)
            ->with('academicSession')
            ->get()
            ->sortBy(fn (StudentEnrollment $enrollment) => $enrollment->academicSession->created_at)
            ->map(fn (StudentEnrollment $enrollment) => [
                'id' => $enrollment->academicSession->id,
                'name' => $enrollment->academicSession->name,
                'level' => $enrollment->level,
            ])
            ->values();
    }

    /**
     * @return array{rows: array, tcu: int, tqp: int, gpa: float}
     */
    public function semesterResult(Student $student, AcademicSession $session, Semester $semester): array
    {
        $scores = Score::query()
            ->where('student_id', $student->id)
            ->where('academic_session_id', $session->id)
            ->whereHas('course', fn ($query) => $query->where('semester', $semester->value))
            ->with('course')
            ->get()
            ->sortBy(fn (Score $score) => $score->course->code)
            ->values();

        $rows = $scores->map(function (Score $score) {
            $scored = $score->ca !== null && $score->exam !== null;
            $grade = $scored ? Grade::fromMark($score->ca + $score->exam) : null;

            return [
                'code' => $score->course->code,
                'title' => $score->course->title,
                'cu' => $score->credit_units_at_entry,
                'ca' => $score->ca,
                'exam' => $score->exam,
                'mark' => $scored ? $score->ca + $score->exam : null,
                'grade' => $grade?->value,
                'gp' => $grade?->gradePoint(),
                'qp' => $grade ? $grade->gradePoint() * $score->credit_units_at_entry : null,
            ];
        });

        $totals = $this->calculator->calculate(
            $scores->map(fn (Score $score) => new ScoreRow($score->ca, $score->exam, $score->credit_units_at_entry)),
        );

        return [
            'rows' => $rows,
            'tcu' => $totals['tcu'],
            'tqp' => $totals['tqp'],
            'gpa' => $totals['gpa'],
        ];
    }

    /**
     * Resolve which session a result view should show: the requested one if
     * the student was actually enrolled in it, otherwise their most recent.
     * Shared by every screen that lets a student/admin browse result
     * history (on-screen view, statement print, future print entry points),
     * so "which session am I looking at" can never resolve differently
     * between them.
     *
     * @return array{sessions: Collection, academicSession: AcademicSession, level: int}
     */
    public function resolveSelectedSession(Student $student, ?string $requestedSessionId): array
    {
        $sessions = $this->sessionsFor($student);

        abort_if($sessions->isEmpty(), 404, 'No academic record found for this student yet.');

        $selected = $sessions->firstWhere('id', $requestedSessionId) ?? $sessions->last();

        return [
            'sessions' => $sessions,
            'academicSession' => AcademicSession::findOrFail($selected['id']),
            'level' => $selected['level'],
        ];
    }

    /**
     * The full prop payload a result-view (on-screen or print) needs for a
     * given student/session/semester — student bio, the session list for
     * the selector, this semester's rows/totals, and CGPA as of that
     * session. Callers add their own navigation props (back/print links).
     */
    public function viewProps(Student $student, ?string $requestedSessionId, Semester $semester): array
    {
        ['sessions' => $sessions, 'academicSession' => $academicSession, 'level' => $level]
            = $this->resolveSelectedSession($student, $requestedSessionId);

        $semesterResult = $this->semesterResult($student, $academicSession, $semester);
        $cumulative = $this->cumulativeAsOf($student, $academicSession);

        return [
            'student' => [
                'name' => $student->full_name,
                'mat_no' => $student->mat_no,
                'photo_url' => $student->photo_url,
                'dob' => $student->dob?->format('d-M-Y'),
                'gender' => $student->gender?->label(),
                'state_of_origin' => $student->state_of_origin,
                'marital_status' => $student->marital_status?->label(),
                'mode_of_study' => $student->mode_of_study->label(),
                'entry_year' => $student->entry_year,
            ],
            'sessions' => $sessions,
            'selectedSessionId' => $academicSession->id,
            'selectedSessionName' => $academicSession->name,
            'selectedLevel' => $level,
            'semester' => $semester->value,
            'rows' => $semesterResult['rows'],
            'semTcu' => $semesterResult['tcu'],
            'semGpa' => $semesterResult['gpa'],
            'cgpa' => $cumulative['cgpa'],
        ];
    }

    /**
     * CGPA as it stood at the end of $uptoSession — every session at or
     * before it, never sessions that come after. This is the real
     * replacement for the prototype's fabricated multi-year history: the
     * number shown is always a true aggregate of rows that were actually
     * entered, for exactly the point in time the student is viewing.
     *
     * @return array{tcu: int, tqp: int, cgpa: float}
     */
    public function cumulativeAsOf(Student $student, AcademicSession $uptoSession): array
    {
        $sessionIds = StudentEnrollment::query()
            ->where('student_id', $student->id)
            ->whereHas('academicSession', fn ($query) => $query->where('created_at', '<=', $uptoSession->created_at))
            ->pluck('academic_session_id');

        $scores = Score::query()
            ->where('student_id', $student->id)
            ->whereIn('academic_session_id', $sessionIds)
            ->get();

        $totals = $this->calculator->calculate(
            $scores->map(fn (Score $score) => new ScoreRow($score->ca, $score->exam, $score->credit_units_at_entry)),
        );

        return [
            'tcu' => $totals['tcu'],
            'tqp' => $totals['tqp'],
            'cgpa' => $totals['gpa'],
        ];
    }
}

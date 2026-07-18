<?php

namespace App\Services\Academic;

use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Score;
use App\Models\ScoreAuditLog;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScoreService
{
    /**
     * Ensure every actively-enrolled student at $course->level in $session
     * has a Score row (creating missing ones, credit units snapshotted from
     * the course), then return the full roster with students eager-loaded.
     */
    public function rosterFor(Course $course, AcademicSession $session): Collection
    {
        $studentIds = StudentEnrollment::query()
            ->where('academic_session_id', $session->id)
            ->where('level', $course->level)
            ->whereHas('student', fn ($query) => $query->where('is_active', true))
            ->pluck('student_id');

        $existingStudentIds = Score::query()
            ->where('course_id', $course->id)
            ->where('academic_session_id', $session->id)
            ->pluck('student_id');

        foreach ($studentIds->diff($existingStudentIds) as $studentId) {
            Score::create([
                'student_id' => $studentId,
                'course_id' => $course->id,
                'academic_session_id' => $session->id,
                'credit_units_at_entry' => $course->credit_units,
            ]);
        }

        return Score::query()
            ->where('course_id', $course->id)
            ->where('academic_session_id', $session->id)
            ->whereIn('student_id', $studentIds)
            ->with('student')
            ->get()
            ->sortBy(fn (Score $score) => $score->student->full_name)
            ->values();
    }

    /**
     * @param  array<string, array{ca?: int, exam?: int}>  $entries  keyed by student_id
     */
    public function saveScores(Course $course, AcademicSession $session, array $entries, User $actor): void
    {
        DB::transaction(function () use ($course, $session, $entries, $actor) {
            $scores = Score::query()
                ->where('course_id', $course->id)
                ->where('academic_session_id', $session->id)
                ->whereIn('student_id', array_keys($entries))
                ->get()
                ->keyBy('student_id');

            foreach ($entries as $studentId => $values) {
                $score = $scores->get($studentId);

                if (! $score) {
                    continue;
                }

                $newCa = max(0, min(30, (int) ($values['ca'] ?? 0)));
                $newExam = max(0, min(70, (int) ($values['exam'] ?? 0)));

                if ($newCa === $score->ca && $newExam === $score->exam) {
                    continue;
                }

                ScoreAuditLog::create([
                    'score_id' => $score->id,
                    'changed_by' => $actor->id,
                    'old_ca' => $score->ca,
                    'old_exam' => $score->exam,
                    'new_ca' => $newCa,
                    'new_exam' => $newExam,
                    'source' => 'manual',
                    'changed_at' => now(),
                ]);

                $score->update([
                    'ca' => $newCa,
                    'exam' => $newExam,
                    'updated_by' => $actor->id,
                ]);
            }
        });
    }
}

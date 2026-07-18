<?php

namespace App\Http\Controllers;

use App\Domain\Grading\Grade;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Score;
use App\Policies\ScorePolicy;
use App\Services\Academic\ScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScoreEntryController extends Controller
{
    public function show(Request $request, Course $course, ScoreService $scoreService, ScorePolicy $policy): Response
    {
        $session = AcademicSession::current();

        abort_if(! $session, 404, 'There is no current academic session.');
        abort_unless($policy->manageCourse($request->user(), $course, $session), 403);

        $roster = $scoreService->rosterFor($course, $session);

        return Inertia::render('ScoreEntry/Show', [
            'course' => [
                'id' => $course->id,
                'code' => $course->code,
                'title' => $course->title,
                'semester' => $course->semester->label(),
            ],
            'session' => ['name' => $session->name],
            'rows' => $roster->map(fn (Score $score) => [
                'student_id' => $score->student_id,
                'mat_no' => $score->student->mat_no,
                'name' => $score->student->full_name,
                'ca' => $score->ca,
                'exam' => $score->exam,
                'grade' => $this->gradeFor($score),
            ]),
        ]);
    }

    public function update(Request $request, Course $course, ScoreService $scoreService, ScorePolicy $policy): RedirectResponse
    {
        $session = AcademicSession::current();

        abort_if(! $session, 404, 'There is no current academic session.');
        abort_unless($policy->manageCourse($request->user(), $course, $session), 403);

        $validated = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*.ca' => ['required', 'integer'],
            'scores.*.exam' => ['required', 'integer'],
        ]);

        $entries = [];
        foreach ($validated['scores'] as $studentId => $row) {
            $entries[$studentId] = ['ca' => $row['ca'], 'exam' => $row['exam']];
        }

        $scoreService->saveScores($course, $session, $entries, $request->user());

        return redirect()->route('scores.show', $course)
            ->with('toast', "Scores saved for {$course->code}");
    }

    private function gradeFor(Score $score): ?string
    {
        if ($score->ca === null || $score->exam === null) {
            return null;
        }

        return Grade::fromMark($score->ca + $score->exam)->value;
    }
}

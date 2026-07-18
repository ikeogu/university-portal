<?php

namespace App\Http\Controllers\Print;

use App\Domain\Grading\Grade;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Score;
use App\Policies\ScorePolicy;
use App\Services\Academic\ScoreService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScoreSheetController extends Controller
{
    public function show(Request $request, Course $course, ScoreService $scoreService, ScorePolicy $policy): Response
    {
        $session = AcademicSession::current();

        abort_if(! $session, 404, 'There is no current academic session.');
        abort_unless($policy->manageCourse($request->user(), $course, $session), 403);

        $roster = $scoreService->rosterFor($course, $session);

        return Inertia::render('Print/ScoreSheet', [
            'course' => [
                'code' => $course->code,
                'title' => $course->title,
                'credit_units' => $course->credit_units,
                'semester' => $course->semester->label(),
            ],
            'session' => ['name' => $session->name],
            'lecturerName' => $request->user()->name,
            'rows' => $roster->values()->map(fn (Score $score, int $index) => [
                'i' => $index + 1,
                'mat_no' => $score->student->mat_no,
                'name' => $score->student->full_name,
                'ca' => $score->ca,
                'exam' => $score->exam,
                'mark' => $score->ca !== null && $score->exam !== null ? $score->ca + $score->exam : null,
                'grade' => $score->ca !== null && $score->exam !== null
                    ? Grade::fromMark($score->ca + $score->exam)->value
                    : null,
            ]),
        ]);
    }
}

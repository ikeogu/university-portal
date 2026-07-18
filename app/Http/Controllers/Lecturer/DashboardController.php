<?php

namespace App\Http\Controllers\Lecturer;

use App\Enums\Semester;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\StudentEnrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $semester = (int) $request->query('semester', Semester::First->value);
        $session = AcademicSession::current();
        $user = $request->user();

        $courses = Course::query()
            ->where('semester', $semester)
            ->whereHas('allocations', function ($query) use ($user, $session) {
                $query->where('user_id', $user->id)
                    ->where('academic_session_id', $session?->id);
            })
            ->orderBy('code')
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'code' => $course->code,
                'title' => $course->title,
                'credit_units' => $course->credit_units,
                'student_count' => $session
                    ? StudentEnrollment::query()
                        ->where('academic_session_id', $session->id)
                        ->where('level', $course->level)
                        ->whereHas('student', fn ($q) => $q->where('is_active', true))
                        ->count()
                    : 0,
            ]);

        return Inertia::render('Lecturer/Dashboard', [
            'courses' => $courses,
            'semester' => $semester,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Semester;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Models\AcademicSession;
use App\Models\Course;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        $currentSessionId = AcademicSession::current()?->id;

        $courses = Course::query()
            ->with(['allocations' => function ($query) use ($currentSessionId) {
                $query->where('academic_session_id', $currentSessionId)
                    ->with('lecturer:id,name');
            }])
            ->orderBy('code')
            ->get()
            ->groupBy(fn (Course $course) => $course->semester->value);

        $semesters = collect(Semester::cases())->map(function (Semester $semester) use ($courses) {
            $semesterCourses = $courses->get($semester->value, collect());

            return [
                'value' => $semester->value,
                'label' => $semester->label(),
                'total_credit_units' => $semesterCourses->sum('credit_units'),
                'courses' => $semesterCourses->map(fn (Course $course) => [
                    'id' => $course->id,
                    'code' => $course->code,
                    'title' => $course->title,
                    'credit_units' => $course->credit_units,
                    'category' => $course->category->label(),
                    'elective_group' => $course->elective_group,
                    'lecturers' => $course->allocations->isEmpty()
                        ? 'Unassigned'
                        : $course->allocations->pluck('lecturer.name')->filter()->implode(', '),
                ])->values(),
            ];
        })->values();

        return Inertia::render('Admin/Courses/Index', [
            'semesters' => $semesters,
        ]);
    }

    public function store(StoreCourseRequest $request)
    {
        $course = Course::create($request->validated());

        return redirect()->route('admin.courses.index')
            ->with('toast', "{$course->code} added — {$course->credit_units} credit units");
    }
}

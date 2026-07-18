<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseCategory;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ElectiveRegistrationController extends Controller
{
    public function index(Request $request): Response
    {
        $sessionId = $request->query('session_id');
        $level = $request->query('level') ? (int) $request->query('level') : null;
        $semester = $request->query('semester') ? (int) $request->query('semester') : null;

        return Inertia::render('Admin/Electives/Index', [
            'sessions' => AcademicSession::query()->orderByDesc('created_at')->get(['id', 'name']),
            'sessionId' => $sessionId,
            'level' => $level,
            'semester' => $semester,
            'grid' => ($sessionId && $level && $semester)
                ? $this->buildGrid(AcademicSession::findOrFail($sessionId), $level, $semester)
                : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'exists:academic_sessions,id'],
            'level' => ['required', 'integer'],
            'semester' => ['required', 'integer'],
            'selections' => ['array'],
            'selections.*.student_id' => ['required', 'exists:students,id'],
            'selections.*.course_id' => ['required', 'exists:courses,id'],
        ]);

        $session = AcademicSession::findOrFail($validated['session_id']);
        $level = (int) $validated['level'];
        $semester = (int) $validated['semester'];

        $electiveCourses = Course::query()
            ->where('level', $level)
            ->where('semester', $semester)
            ->where('category', CourseCategory::Elective)
            ->whereNotNull('elective_group')
            ->get()
            ->keyBy('id');

        $selections = collect($validated['selections'] ?? [])
            ->filter(fn (array $row) => $electiveCourses->has($row['course_id']))
            ->values();

        foreach ($selections->groupBy('student_id') as $rows) {
            $byGroup = collect($rows)->groupBy(fn (array $row) => $electiveCourses->get($row['course_id'])->elective_group);

            foreach ($byGroup as $group => $groupRows) {
                $expected = $electiveCourses->firstWhere('elective_group', $group)->choose_count ?? 1;
                $count = count($groupRows);

                if ($count !== $expected) {
                    return back()->withErrors([
                        'selections' => "A student picked {$count} course(s) in \"{$group}\" — must be exactly {$expected}.",
                    ]);
                }
            }
        }

        // Full sync: the grid always submits every checked box, so the
        // simplest correct move is to replace this session's registrations
        // for these elective courses wholesale rather than diff them. Any
        // Score row already materialized for a since-unselected course is
        // left alone — matching this app's convention elsewhere of never
        // auto-deleting entered scores.
        DB::transaction(function () use ($request, $session, $electiveCourses, $selections) {
            CourseRegistration::query()
                ->where('academic_session_id', $session->id)
                ->whereIn('course_id', $electiveCourses->keys())
                ->delete();

            foreach ($selections as $row) {
                CourseRegistration::query()->create([
                    'student_id' => $row['student_id'],
                    'course_id' => $row['course_id'],
                    'academic_session_id' => $session->id,
                    'registered_by' => $request->user()->id,
                ]);
            }
        });

        return redirect()->route('admin.electives.index', [
            'session_id' => $session->id, 'level' => $level, 'semester' => $semester,
        ])->with('toast', 'Elective registrations saved');
    }

    private function buildGrid(AcademicSession $session, int $level, int $semester): array
    {
        $groups = Course::query()
            ->where('level', $level)
            ->where('semester', $semester)
            ->where('category', CourseCategory::Elective)
            ->whereNotNull('elective_group')
            ->orderBy('code')
            ->get()
            ->groupBy('elective_group')
            ->map(fn ($courses, $group) => [
                'key' => $group,
                'choose_count' => $courses->first()->choose_count ?? 1,
                'courses' => $courses->map(fn (Course $course) => [
                    'id' => $course->id,
                    'code' => $course->code,
                    'title' => $course->title,
                ])->values(),
            ])
            ->values();

        $courseIds = $groups->flatMap(fn (array $group) => collect($group['courses'])->pluck('id'));

        $students = Student::query()
            ->whereHas('enrollments', fn ($query) => $query
                ->where('academic_session_id', $session->id)
                ->where('level', $level))
            ->where('is_active', true)
            ->orderBy('last_name')->orderBy('first_name')
            ->get();

        $selections = CourseRegistration::query()
            ->where('academic_session_id', $session->id)
            ->whereIn('course_id', $courseIds)
            ->get(['student_id', 'course_id']);

        return [
            'groups' => $groups,
            'students' => $students->map(fn (Student $student) => [
                'id' => $student->id,
                'mat_no' => $student->mat_no,
                'name' => $student->full_name,
            ])->values(),
            'selections' => $selections->map(fn (CourseRegistration $r) => [
                'student_id' => $r->student_id,
                'course_id' => $r->course_id,
            ])->values(),
        ];
    }
}

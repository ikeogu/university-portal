<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StaffRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLecturerRequest;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class LecturerController extends Controller
{
    public function index(): Response
    {
        $session = AcademicSession::current();

        $courses = Course::query()->orderBy('code')->get(['id', 'code']);

        // Keyed lookup of "course_id|user_id" pairs already allocated for the
        // current session, so per-chip "assigned" checks are O(1) instead of
        // a query per lecturer per course. No current session simply means
        // nothing is assigned yet.
        $assignedPairs = $session
            ? CourseAllocation::query()
                ->where('academic_session_id', $session->id)
                ->get(['course_id', 'user_id'])
                ->mapWithKeys(fn (CourseAllocation $allocation) => ["{$allocation->course_id}|{$allocation->user_id}" => true])
            : collect();

        $lecturers = User::query()
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role->label(),
                'chips' => $courses->map(fn (Course $course) => [
                    'course_id' => $course->id,
                    'code' => $course->code,
                    'assigned' => $assignedPairs->has("{$course->id}|{$user->id}"),
                ])->values(),
            ])
            ->values();

        return Inertia::render('Admin/Lecturers/Index', [
            'lecturers' => $lecturers,
        ]);
    }

    public function store(StoreLecturerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $lecturer = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make(Str::random(40)),
            'role' => StaffRole::Lecturer,
            'password_set_at' => null,
        ]);

        return redirect()->route('admin.lecturers.index')
            ->with('toast', "{$lecturer->name} added as a lecturer");
    }

    public function toggleCourse(User $lecturer, Course $course): RedirectResponse
    {
        $session = AcademicSession::current();

        if (! $session) {
            return redirect()->route('admin.lecturers.index')
                ->with('toast', 'Set a current academic session before allocating courses.');
        }

        $allocation = CourseAllocation::query()
            ->where('course_id', $course->id)
            ->where('user_id', $lecturer->id)
            ->where('academic_session_id', $session->id)
            ->first();

        if ($allocation) {
            $allocation->delete();

            $toast = "{$course->code} removed from {$lecturer->name}";
        } else {
            CourseAllocation::create([
                'course_id' => $course->id,
                'user_id' => $lecturer->id,
                'academic_session_id' => $session->id,
            ]);

            $toast = "{$course->code} assigned to {$lecturer->name}";
        }

        return redirect()->route('admin.lecturers.index')->with('toast', $toast);
    }
}

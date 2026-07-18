<?php

namespace Tests\Feature\Lecturer;

use App\Enums\ModeOfStudy;
use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lecturer_sees_only_their_own_courses_for_the_selected_semester_with_student_counts(): void
    {
        $lecturer = User::factory()->lecturer()->create();
        $otherLecturer = User::factory()->lecturer()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $myCourse = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        $otherCourse = Course::create(['code' => 'CSC 402', 'title' => 'Networks', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        $secondSemesterCourse = Course::create(['code' => 'CSC 499', 'title' => 'Project', 'credit_units' => 6, 'semester' => Semester::Second, 'level' => 400]);

        CourseAllocation::create(['course_id' => $myCourse->id, 'user_id' => $lecturer->id, 'academic_session_id' => $session->id]);
        CourseAllocation::create(['course_id' => $otherCourse->id, 'user_id' => $otherLecturer->id, 'academic_session_id' => $session->id]);
        CourseAllocation::create(['course_id' => $secondSemesterCourse->id, 'user_id' => $lecturer->id, 'academic_session_id' => $session->id]);

        foreach (['U2022/0001', 'U2022/0002'] as $matNo) {
            $student = Student::create([
                'mat_no' => $matNo, 'entry_year' => 2022,
                'last_name' => 'Test', 'first_name' => $matNo,
                'mode_of_study' => ModeOfStudy::FullTime,
            ]);
            StudentEnrollment::create([
                'student_id' => $student->id, 'academic_session_id' => $session->id,
                'level' => 400, 'mode_of_study' => ModeOfStudy::FullTime,
            ]);
        }

        $response = $this->actingAs($lecturer)->get(route('lecturer.dashboard', ['semester' => 1]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Lecturer/Dashboard')
            ->has('courses', 1)
            ->where('courses.0.code', 'CSC 401')
            ->where('courses.0.student_count', 2)
        );
    }

    public function test_empty_state_when_no_courses_allocated_for_the_semester(): void
    {
        $lecturer = User::factory()->lecturer()->create();
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $response = $this->actingAs($lecturer)->get(route('lecturer.dashboard'));

        $response->assertInertia(fn ($page) => $page->has('courses', 0));
    }
}

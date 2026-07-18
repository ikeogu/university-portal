<?php

namespace Tests\Feature\Print;

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

class ScoreSheetControllerTest extends TestCase
{
    use RefreshDatabase;

    private function setUp2(): array
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        $student = Student::create([
            'mat_no' => 'U2022/0001', 'entry_year' => 2022,
            'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime,
        ]);
        StudentEnrollment::create(['student_id' => $student->id, 'academic_session_id' => $session->id, 'level' => 400, 'mode_of_study' => ModeOfStudy::FullTime]);

        return [$session, $course, $student];
    }

    public function test_allocated_lecturer_can_view_the_score_sheet(): void
    {
        [$session, $course, $student] = $this->setUp2();
        $lecturer = User::factory()->lecturer()->create(['name' => 'Dr. Aisha Balogun']);
        CourseAllocation::create(['course_id' => $course->id, 'user_id' => $lecturer->id, 'academic_session_id' => $session->id]);

        $response = $this->actingAs($lecturer)->get(route('scores.print', $course));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Print/ScoreSheet')
            ->where('course.code', 'CSC 401')
            ->where('lecturerName', 'Dr. Aisha Balogun')
            ->where('rows.0.mat_no', 'U2022/0001')
        );
    }

    public function test_unallocated_lecturer_is_forbidden(): void
    {
        [, $course] = $this->setUp2();
        $lecturer = User::factory()->lecturer()->create();

        $this->actingAs($lecturer)->get(route('scores.print', $course))->assertForbidden();
    }

    public function test_admin_can_view_any_course_score_sheet(): void
    {
        [, $course] = $this->setUp2();
        $admin = User::factory()->examOfficer()->create();

        $this->actingAs($admin)->get(route('scores.print', $course))->assertOk();
    }
}

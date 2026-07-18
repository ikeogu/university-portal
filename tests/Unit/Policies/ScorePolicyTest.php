<?php

namespace Tests\Unit\Policies;

use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\User;
use App\Policies\ScorePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScorePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_hod_can_manage_any_course(): void
    {
        $hod = User::factory()->hod()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);

        $this->assertTrue((new ScorePolicy)->manageCourse($hod, $course, $session));
    }

    public function test_exam_officer_can_manage_any_course(): void
    {
        $examOfficer = User::factory()->examOfficer()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);

        $this->assertTrue((new ScorePolicy)->manageCourse($examOfficer, $course, $session));
    }

    public function test_allocated_lecturer_can_manage_the_course(): void
    {
        $lecturer = User::factory()->lecturer()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);

        CourseAllocation::create([
            'course_id' => $course->id,
            'user_id' => $lecturer->id,
            'academic_session_id' => $session->id,
        ]);

        $this->assertTrue((new ScorePolicy)->manageCourse($lecturer, $course, $session));
    }

    public function test_unallocated_lecturer_cannot_manage_the_course(): void
    {
        $lecturer = User::factory()->lecturer()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);

        $this->assertFalse((new ScorePolicy)->manageCourse($lecturer, $course, $session));
    }

    public function test_lecturer_allocated_in_a_different_session_cannot_manage_the_course(): void
    {
        $lecturer = User::factory()->lecturer()->create();
        $lastSession = AcademicSession::create(['name' => '2024/2025', 'is_current' => false]);
        $currentSession = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);

        CourseAllocation::create([
            'course_id' => $course->id,
            'user_id' => $lecturer->id,
            'academic_session_id' => $lastSession->id,
        ]);

        $this->assertFalse((new ScorePolicy)->manageCourse($lecturer, $course, $currentSession));
    }
}

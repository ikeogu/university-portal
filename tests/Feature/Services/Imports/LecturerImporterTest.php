<?php

namespace Tests\Feature\Services\Imports;

use App\Enums\Semester;
use App\Enums\StaffRole;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\User;
use App\Services\Imports\LecturerImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LecturerImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_lecturers_and_auto_assigns_matched_course_codes(): void
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 402', 'title' => 'Networks', 'credit_units' => 3, 'semester' => Semester::Second, 'level' => 400]);

        $result = (new LecturerImporter)->import([
            ['Dr. Ngozi Okereke', 'Lecturer', 'CSC 402', 'ngozi.okereke@unitystate.edu.ng'],
        ], $session);

        $this->assertSame(1, $result->added);
        $this->assertSame(0, $result->skipped);

        $user = User::where('email', 'ngozi.okereke@unitystate.edu.ng')->sole();
        $this->assertSame('Dr. Ngozi Okereke', $user->name);
        $this->assertSame(StaffRole::Lecturer, $user->role);
        $this->assertNull($user->password_set_at);

        $allocation = CourseAllocation::sole();
        $this->assertSame($course->id, $allocation->course_id);
        $this->assertSame($user->id, $allocation->user_id);
        $this->assertSame($session->id, $allocation->academic_session_id);
    }

    public function test_unmatched_course_codes_are_warned_but_do_not_fail_the_row(): void
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $result = (new LecturerImporter)->import([
            ['Dr. Ngozi Okereke', 'Lecturer', 'CSC 999', 'ngozi.okereke@unitystate.edu.ng'],
        ], $session);

        $this->assertSame(1, $result->added);
        $this->assertCount(1, $result->warnings);
        $this->assertSame(0, CourseAllocation::count());
        $this->assertSame(1, User::count());
    }

    public function test_skips_duplicate_names_and_duplicate_emails(): void
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        User::factory()->lecturer()->create(['name' => 'Dr. Ngozi Okereke', 'email' => 'existing@unitystate.edu.ng']);

        $result = (new LecturerImporter)->import([
            ['Dr. Ngozi Okereke', 'Lecturer', '', 'new.email@unitystate.edu.ng'], // duplicate name
            ['Mr. Sadiq Abubakar', 'Lecturer', '', 'existing@unitystate.edu.ng'], // duplicate email
        ], $session);

        $this->assertSame(0, $result->added);
        $this->assertSame(2, $result->skipped);
        $this->assertSame(1, User::count());
    }

    public function test_role_column_is_parsed_into_the_matching_staff_role(): void
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        (new LecturerImporter)->import([
            ['Prof. Kelechi Nnamdi', 'Head of Department', '', 'hod@unitystate.edu.ng'],
        ], $session);

        $this->assertSame(StaffRole::Hod, User::sole()->role);
    }
}

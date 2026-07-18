<?php

namespace Tests\Feature\Public;

use App\Enums\ModeOfStudy;
use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Score;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    private const PIN = '111111';

    private function seedStudentWithResult(): Student
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $student = Student::create([
            'mat_no' => 'U2022/5570001', 'entry_year' => 2022,
            'last_name' => 'Okafor', 'first_name' => 'Adaeze',
            'dob' => '2003-03-14', 'state_of_origin' => 'Anambra',
            'mode_of_study' => ModeOfStudy::FullTime,
            'access_pin' => self::PIN,
        ]);
        StudentEnrollment::create([
            'student_id' => $student->id, 'academic_session_id' => $session->id,
            'level' => 400, 'mode_of_study' => ModeOfStudy::FullTime,
        ]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        Score::create([
            'student_id' => $student->id, 'course_id' => $course->id, 'academic_session_id' => $session->id,
            'credit_units_at_entry' => 3, 'ca' => 25, 'exam' => 45,
        ]);

        return $student;
    }

    public function test_unknown_matriculation_number_is_rejected_without_starting_a_session(): void
    {
        $response = $this->post(route('public.check.store'), ['mat_no' => 'U2099/0000001', 'pin' => '000000']);

        $response->assertSessionHasErrors('mat_no');
        $this->assertNull(session('public_student_id'));
    }

    public function test_wrong_pin_is_rejected(): void
    {
        $student = $this->seedStudentWithResult();

        $response = $this->post(route('public.check.store'), [
            'mat_no' => $student->mat_no,
            'pin' => '999999',
        ]);

        $response->assertSessionHasErrors('mat_no');
        $this->assertNull(session('public_student_id'));
    }

    public function test_unknown_mat_no_and_wrong_pin_give_the_identical_generic_error(): void
    {
        $student = $this->seedStudentWithResult();

        // A distinct message per failure mode would let an attacker
        // enumerate real matriculation numbers one field at a time.
        $genericMessage = 'That matriculation number and access PIN do not match our records.';

        $this->post(route('public.check.store'), ['mat_no' => 'U2099/0000001', 'pin' => '000000'])
            ->assertSessionHasErrors(['mat_no' => $genericMessage]);

        $this->post(route('public.check.store'), ['mat_no' => $student->mat_no, 'pin' => '999999'])
            ->assertSessionHasErrors(['mat_no' => $genericMessage]);
    }

    public function test_correct_mat_no_and_pin_verifies_and_redirects_to_result(): void
    {
        $student = $this->seedStudentWithResult();

        $response = $this->post(route('public.check.store'), [
            'mat_no' => strtolower($student->mat_no),
            'pin' => self::PIN,
        ]);

        $response->assertRedirect(route('public.result'));
        $this->assertSame($student->id, session('public_student_id'));
    }

    public function test_result_redirects_to_check_without_a_verified_session(): void
    {
        $response = $this->get(route('public.result'));

        $response->assertRedirect(route('public.check'));
    }

    public function test_result_redirects_to_check_once_the_verification_window_has_expired(): void
    {
        $student = $this->seedStudentWithResult();

        $this->withSession([
            'public_student_id' => $student->id,
            'public_verified_until' => now()->subMinute(),
        ])->get(route('public.result'))->assertRedirect(route('public.check'));
    }

    public function test_result_shows_computed_grades_and_cgpa_for_a_verified_session(): void
    {
        $student = $this->seedStudentWithResult();

        $response = $this->withSession([
            'public_student_id' => $student->id,
            'public_verified_until' => now()->addMinutes(20),
        ])->get(route('public.result'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Result/Show')
            ->where('student.mat_no', 'U2022/5570001')
            ->where('rows.0.grade', 'A')
            // A whole-number float (5.0) round-trips through JSON as the
            // bare integer 5 — json_encode(5.0) === json_encode(5) — so
            // this asserts the value that actually crosses the wire, not
            // the PHP-side type, which is irrelevant to the client anyway.
            ->where('semGpa', 5)
            ->where('cgpa', 5)
        );
    }

    public function test_student_can_switch_to_a_past_session_via_the_session_selector(): void
    {
        $pastSession = AcademicSession::create(['name' => '2024/2025', 'is_current' => false]);
        $currentSession = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $student = Student::create([
            'mat_no' => 'U2022/5570002', 'entry_year' => 2022,
            'last_name' => 'Bello', 'first_name' => 'Tunde',
            'mode_of_study' => ModeOfStudy::FullTime,
            'access_pin' => self::PIN,
        ]);

        StudentEnrollment::create([
            'student_id' => $student->id, 'academic_session_id' => $pastSession->id,
            'level' => 100, 'mode_of_study' => ModeOfStudy::FullTime,
        ]);
        StudentEnrollment::create([
            'student_id' => $student->id, 'academic_session_id' => $currentSession->id,
            'level' => 200, 'mode_of_study' => ModeOfStudy::FullTime,
        ]);

        $pastCourse = Course::create(['code' => 'CSC 101', 'title' => 'Intro to CS', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 100]);
        Score::create([
            'student_id' => $student->id, 'course_id' => $pastCourse->id, 'academic_session_id' => $pastSession->id,
            'credit_units_at_entry' => 3, 'ca' => 15, 'exam' => 35, // C grade
        ]);

        $currentCourse = Course::create(['code' => 'CSC 201', 'title' => 'Data Structures', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 200]);
        Score::create([
            'student_id' => $student->id, 'course_id' => $currentCourse->id, 'academic_session_id' => $currentSession->id,
            'credit_units_at_entry' => 3, 'ca' => 28, 'exam' => 65, // A grade
        ]);

        $verifiedSession = ['public_student_id' => $student->id, 'public_verified_until' => now()->addMinutes(20)];

        // No session param — defaults to the most recent (current) session.
        $this->withSession($verifiedSession)->get(route('public.result'))
            ->assertInertia(fn ($page) => $page
                ->component('Result/Show')
                ->has('sessions', 2)
                ->where('selectedSessionName', '2025/2026')
                ->where('rows.0.code', 'CSC 201')
                ->where('rows.0.grade', 'A')
            );

        // Explicitly switching to the past session shows that session's own
        // courses/grade, not the current session's.
        $this->withSession($verifiedSession)->get(route('public.result', ['session' => $pastSession->id]))
            ->assertInertia(fn ($page) => $page
                ->component('Result/Show')
                ->where('selectedSessionName', '2024/2025')
                ->where('rows.0.code', 'CSC 101')
                ->where('rows.0.grade', 'C')
            );
    }

    public function test_result_check_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('public.check.store'), ['mat_no' => 'U2099/0000001', 'pin' => '000000']);
        }

        $response = $this->post(route('public.check.store'), ['mat_no' => 'U2099/0000001', 'pin' => '000000']);

        $response->assertStatus(429);
    }
}

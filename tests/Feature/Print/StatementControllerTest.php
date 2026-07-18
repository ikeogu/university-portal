<?php

namespace Tests\Feature\Print;

use App\Enums\ModeOfStudy;
use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Score;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StatementControllerTest extends TestCase
{
    use RefreshDatabase;

    private function seedStudentWithResult(): Student
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $student = Student::create([
            'mat_no' => 'U2022/5570001', 'entry_year' => 2022,
            'last_name' => 'Okafor', 'first_name' => 'Adaeze',
            'dob' => '2003-03-14', 'state_of_origin' => 'Anambra',
            'mode_of_study' => ModeOfStudy::FullTime,
        ]);
        StudentEnrollment::create([
            'student_id' => $student->id, 'academic_session_id' => $session->id,
            'level' => 400, 'mode_of_study' => ModeOfStudy::FullTime,
        ]);
        $courseS1 = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        $courseS2 = Course::create(['code' => 'CSC 402', 'title' => 'Networks', 'credit_units' => 3, 'semester' => Semester::Second, 'level' => 400]);
        Score::create(['student_id' => $student->id, 'course_id' => $courseS1->id, 'academic_session_id' => $session->id, 'credit_units_at_entry' => 3, 'ca' => 25, 'exam' => 45]);
        Score::create(['student_id' => $student->id, 'course_id' => $courseS2->id, 'academic_session_id' => $session->id, 'credit_units_at_entry' => 3, 'ca' => 10, 'exam' => 20]); // F

        return $student;
    }

    public function test_public_print_redirects_to_check_without_a_verified_session(): void
    {
        $this->get(route('public.result.print'))->assertRedirect(route('public.check'));
    }

    public function test_public_print_shows_both_semesters_and_cgpa_for_a_verified_session(): void
    {
        $student = $this->seedStudentWithResult();

        $response = $this->withSession([
            'public_student_id' => $student->id,
            'public_verified_until' => now()->addMinutes(20),
        ])->get(route('public.result.print'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Print/Statement')
            ->where('student.mat_no', 'U2022/5570001')
            ->where('sem1.rows.0.grade', 'A')
            ->where('sem2.rows.0.grade', 'F')
        );
    }

    public function test_admin_can_print_any_students_statement(): void
    {
        $student = $this->seedStudentWithResult();
        $admin = User::factory()->hod()->create();

        $this->actingAs($admin)
            ->get(route('admin.students.print', $student))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Print/Statement')
                ->where('student.mat_no', 'U2022/5570001')
            );
    }

    public function test_lecturer_cannot_print_a_students_statement_via_the_admin_route(): void
    {
        $student = $this->seedStudentWithResult();
        $lecturer = User::factory()->lecturer()->create();

        $this->actingAs($lecturer)
            ->get(route('admin.students.print', $student))
            ->assertForbidden();
    }

    public function test_statement_carries_the_hod_signature_url_when_configured(): void
    {
        Storage::fake('public');
        $student = $this->seedStudentWithResult();
        $admin = User::factory()->hod()->create();
        $hodPath = UploadedFile::fake()->image('hod.png')->store('signatures', 'public');
        Setting::set('hod_signature_path', $hodPath);

        $this->actingAs($admin)
            ->get(route('admin.students.print', $student))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Print/Statement')
                ->where('institution.hodSignatureUrl', Storage::disk('public')->url($hodPath))
                ->where('institution.examOfficerSignatureUrl', null)
            );
    }
}

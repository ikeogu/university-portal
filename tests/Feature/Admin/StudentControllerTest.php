<?php

namespace Tests\Feature\Admin;

use App\Enums\ModeOfStudy;
use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_students_and_stats(): void
    {
        $admin = User::factory()->hod()->create();

        Student::create([
            'mat_no' => 'U2022/5570001',
            'entry_year' => 2022,
            'last_name' => 'Okafor',
            'first_name' => 'Adaeze',
            'mode_of_study' => ModeOfStudy::FullTime,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Students/Index')
            ->has('students', 1)
            ->where('stats.students', 1)
        );
    }

    public function test_index_search_filters_by_name_or_mat_no(): void
    {
        $admin = User::factory()->hod()->create();

        Student::create([
            'mat_no' => 'U2022/5570001', 'entry_year' => 2022,
            'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime,
        ]);
        Student::create([
            'mat_no' => 'U2022/5570002', 'entry_year' => 2022,
            'last_name' => 'Musa', 'first_name' => 'Ibrahim', 'mode_of_study' => ModeOfStudy::FullTime,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.index', ['q' => 'Musa']));

        $response->assertInertia(fn ($page) => $page->has('students', 1));
    }

    public function test_store_creates_student_and_enrolls_at_entry_level_in_current_session(): void
    {
        $admin = User::factory()->hod()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $response = $this->actingAs($admin)->post(route('admin.students.store'), [
            'last_name' => 'Okoro',
            'first_name' => 'Chidera',
            'middle_name' => 'Faith',
            'mat_no' => 'u2022/5570016',
            'dob' => '2003-01-08',
            'state_of_origin' => 'Rivers',
            'marital_status' => 'single',
            'mode_of_study' => 'full_time',
        ]);

        $response->assertRedirect(route('admin.students.index'));
        $response->assertSessionHas('toast');

        $student = Student::sole();
        $this->assertSame('U2022/5570016', $student->mat_no);
        $this->assertSame(2022, $student->entry_year);

        $enrollment = StudentEnrollment::sole();
        $this->assertSame($student->id, $enrollment->student_id);
        $this->assertSame($session->id, $enrollment->academic_session_id);
        $this->assertSame(100, $enrollment->level);
    }

    public function test_store_rejects_duplicate_matriculation_number(): void
    {
        $admin = User::factory()->hod()->create();

        Student::create([
            'mat_no' => 'U2022/5570001', 'entry_year' => 2022,
            'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.students.store'), [
            'last_name' => 'Another',
            'first_name' => 'Student',
            'mat_no' => 'U2022/5570001',
            'mode_of_study' => 'full_time',
        ]);

        $response->assertSessionHasErrors('mat_no');
        $this->assertSame(1, Student::count());
    }

    public function test_lecturer_cannot_access_student_admin_routes(): void
    {
        $lecturer = User::factory()->lecturer()->create();

        $this->actingAs($lecturer)->get(route('admin.students.index'))->assertForbidden();
        $this->actingAs($lecturer)->post(route('admin.students.store'), [])->assertForbidden();
    }

    public function test_show_renders_the_shared_result_view_for_an_admin(): void
    {
        $admin = User::factory()->hod()->create();
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $student = Student::create([
            'mat_no' => 'U2022/5570001', 'entry_year' => 2022,
            'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime,
        ]);
        StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_session_id' => AcademicSession::current()->id,
            'level' => 400, 'mode_of_study' => ModeOfStudy::FullTime,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.show', $student));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Result/Show')
            ->where('student.mat_no', 'U2022/5570001')
            ->where('backHref', route('admin.students.index'))
        );
    }
}

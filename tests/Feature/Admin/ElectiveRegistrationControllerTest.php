<?php

namespace Tests\Feature\Admin;

use App\Enums\CourseCategory;
use App\Enums\ModeOfStudy;
use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ElectiveRegistrationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(string $matNo, string $lastName): Student
    {
        return Student::create([
            'mat_no' => $matNo, 'entry_year' => 2025,
            'last_name' => $lastName, 'first_name' => 'Test',
            'mode_of_study' => ModeOfStudy::FullTime,
        ]);
    }

    public function test_index_without_query_params_shows_only_the_picker(): void
    {
        $admin = User::factory()->hod()->create();

        $response = $this->actingAs($admin)->get(route('admin.electives.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Electives/Index')
            ->where('grid', null)
        );
    }

    public function test_index_builds_a_grid_with_groups_students_and_existing_selections(): void
    {
        $admin = User::factory()->hod()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $fad = Course::create([
            'code' => 'FAD 100.1', 'title' => 'Fundamentals of Visual Arts', 'credit_units' => 3,
            'semester' => Semester::First, 'level' => 100,
            'category' => CourseCategory::Elective, 'elective_group' => 'Y1S1 Arts Choice', 'choose_count' => 1,
        ]);
        $tha = Course::create([
            'code' => 'THA 100.1', 'title' => 'Fundamentals of Theatre Arts', 'credit_units' => 3,
            'semester' => Semester::First, 'level' => 100,
            'category' => CourseCategory::Elective, 'elective_group' => 'Y1S1 Arts Choice', 'choose_count' => 1,
        ]);

        $student = $this->makeStudent('CSC/2025/0001', 'Okafor');
        StudentEnrollment::create([
            'student_id' => $student->id, 'academic_session_id' => $session->id,
            'level' => 100, 'mode_of_study' => ModeOfStudy::FullTime,
        ]);

        CourseRegistration::create([
            'student_id' => $student->id, 'course_id' => $fad->id, 'academic_session_id' => $session->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.electives.index', [
            'session_id' => $session->id, 'level' => 100, 'semester' => 1,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Electives/Index')
            ->has('grid.groups', 1)
            ->where('grid.groups.0.key', 'Y1S1 Arts Choice')
            ->where('grid.groups.0.choose_count', 1)
            ->has('grid.groups.0.courses', 2)
            ->has('grid.students', 1)
            ->where('grid.students.0.mat_no', 'CSC/2025/0001')
            ->has('grid.selections', 1)
            ->where('grid.selections.0.course_id', $fad->id)
        );

        // Sanity: THA exists in the group too, just not yet selected.
        $this->assertTrue(Course::whereKey($tha->id)->exists());
    }

    public function test_update_saves_valid_selections(): void
    {
        $admin = User::factory()->hod()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $fad = Course::create([
            'code' => 'FAD 100.1', 'title' => 'Fundamentals of Visual Arts', 'credit_units' => 3,
            'semester' => Semester::First, 'level' => 100,
            'category' => CourseCategory::Elective, 'elective_group' => 'Y1S1 Arts Choice', 'choose_count' => 1,
        ]);
        Course::create([
            'code' => 'THA 100.1', 'title' => 'Fundamentals of Theatre Arts', 'credit_units' => 3,
            'semester' => Semester::First, 'level' => 100,
            'category' => CourseCategory::Elective, 'elective_group' => 'Y1S1 Arts Choice', 'choose_count' => 1,
        ]);

        $student = $this->makeStudent('CSC/2025/0001', 'Okafor');

        $response = $this->actingAs($admin)->post(route('admin.electives.update'), [
            'session_id' => $session->id,
            'level' => 100,
            'semester' => 1,
            'selections' => [
                ['student_id' => $student->id, 'course_id' => $fad->id],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.electives.index', [
            'session_id' => $session->id, 'level' => 100, 'semester' => 1,
        ]));

        $this->assertSame(1, CourseRegistration::count());
        $registration = CourseRegistration::sole();
        $this->assertSame($student->id, $registration->student_id);
        $this->assertSame($fad->id, $registration->course_id);
        $this->assertSame($admin->id, $registration->registered_by);
    }

    public function test_update_rejects_a_student_picking_more_than_choose_count(): void
    {
        $admin = User::factory()->hod()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $fad = Course::create([
            'code' => 'FAD 100.1', 'title' => 'Fundamentals of Visual Arts', 'credit_units' => 3,
            'semester' => Semester::First, 'level' => 100,
            'category' => CourseCategory::Elective, 'elective_group' => 'Y1S1 Arts Choice', 'choose_count' => 1,
        ]);
        $tha = Course::create([
            'code' => 'THA 100.1', 'title' => 'Fundamentals of Theatre Arts', 'credit_units' => 3,
            'semester' => Semester::First, 'level' => 100,
            'category' => CourseCategory::Elective, 'elective_group' => 'Y1S1 Arts Choice', 'choose_count' => 1,
        ]);

        $student = $this->makeStudent('CSC/2025/0001', 'Okafor');

        $response = $this->actingAs($admin)->post(route('admin.electives.update'), [
            'session_id' => $session->id,
            'level' => 100,
            'semester' => 1,
            'selections' => [
                ['student_id' => $student->id, 'course_id' => $fad->id],
                ['student_id' => $student->id, 'course_id' => $tha->id],
            ],
        ]);

        $response->assertSessionHasErrors('selections');
        $this->assertSame(0, CourseRegistration::count());
    }

    public function test_update_allows_a_student_with_no_selections_yet(): void
    {
        $admin = User::factory()->hod()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        Course::create([
            'code' => 'FAD 100.1', 'title' => 'Fundamentals of Visual Arts', 'credit_units' => 3,
            'semester' => Semester::First, 'level' => 100,
            'category' => CourseCategory::Elective, 'elective_group' => 'Y1S1 Arts Choice', 'choose_count' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.electives.update'), [
            'session_id' => $session->id,
            'level' => 100,
            'semester' => 1,
            'selections' => [],
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(0, CourseRegistration::count());
    }

    public function test_update_replaces_previous_registrations_wholesale(): void
    {
        $admin = User::factory()->hod()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $fad = Course::create([
            'code' => 'FAD 100.1', 'title' => 'Fundamentals of Visual Arts', 'credit_units' => 3,
            'semester' => Semester::First, 'level' => 100,
            'category' => CourseCategory::Elective, 'elective_group' => 'Y1S1 Arts Choice', 'choose_count' => 1,
        ]);
        $tha = Course::create([
            'code' => 'THA 100.1', 'title' => 'Fundamentals of Theatre Arts', 'credit_units' => 3,
            'semester' => Semester::First, 'level' => 100,
            'category' => CourseCategory::Elective, 'elective_group' => 'Y1S1 Arts Choice', 'choose_count' => 1,
        ]);

        $student = $this->makeStudent('CSC/2025/0001', 'Okafor');

        CourseRegistration::create([
            'student_id' => $student->id, 'course_id' => $fad->id, 'academic_session_id' => $session->id,
        ]);

        $this->actingAs($admin)->post(route('admin.electives.update'), [
            'session_id' => $session->id,
            'level' => 100,
            'semester' => 1,
            'selections' => [
                ['student_id' => $student->id, 'course_id' => $tha->id],
            ],
        ]);

        $registration = CourseRegistration::sole();
        $this->assertSame($tha->id, $registration->course_id);
    }

    public function test_lecturers_cannot_access_electives_routes(): void
    {
        $lecturer = User::factory()->lecturer()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $this->actingAs($lecturer)->get(route('admin.electives.index'))->assertForbidden();

        $this->actingAs($lecturer)->post(route('admin.electives.update'), [
            'session_id' => $session->id, 'level' => 100, 'semester' => 1, 'selections' => [],
        ])->assertForbidden();
    }
}

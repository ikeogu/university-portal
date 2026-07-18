<?php

namespace Tests\Feature\Admin;

use App\Enums\Semester;
use App\Enums\StaffRole;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LecturerControllerTest extends TestCase
{
    use RefreshDatabase;

    private function currentSession(string $name = '2025/2026'): AcademicSession
    {
        return AcademicSession::create([
            'name' => $name,
            'is_current' => true,
        ]);
    }

    private function course(string $code): Course
    {
        return Course::create([
            'code' => $code,
            'title' => 'Test course '.$code,
            'credit_units' => 3,
            'semester' => Semester::First,
            'level' => 400,
            'is_active' => true,
        ]);
    }

    public function test_index_returns_ok_and_includes_seeded_courses_and_users(): void
    {
        $this->currentSession();
        $this->course('CSC401');

        // Deterministic names so ordering (index orders users by name) is predictable.
        $admin = User::factory()->hod()->create(['name' => 'Zubairu Admin']);
        User::factory()->lecturer()->create(['name' => 'Aisha Balogun']);

        $response = $this->actingAs($admin)->get(route('admin.lecturers.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Lecturers/Index')
            ->has('lecturers', 2)
            ->where('lecturers.0.name', 'Aisha Balogun')
            ->where('lecturers.0.role', StaffRole::Lecturer->label())
            ->has('lecturers.0.chips', 1)
            ->where('lecturers.0.chips.0.code', 'CSC401')
            ->where('lecturers.0.chips.0.assigned', false)
            ->where('lecturers.1.name', 'Zubairu Admin')
            ->where('lecturers.1.role', StaffRole::Hod->label())
        );
    }

    public function test_index_reflects_existing_allocations_for_the_current_session(): void
    {
        $session = $this->currentSession();
        $course = $this->course('CSC401');
        $admin = User::factory()->hod()->create();
        $lecturer = User::factory()->lecturer()->create(['name' => 'Aisha Balogun']);

        $course->allocations()->create([
            'user_id' => $lecturer->id,
            'academic_session_id' => $session->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.lecturers.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('lecturers.0.chips.0.assigned', true)
        );
    }

    public function test_index_treats_nothing_as_assigned_without_a_current_session(): void
    {
        // No AcademicSession at all — must not error.
        $this->course('CSC401');
        $admin = User::factory()->hod()->create();

        $response = $this->actingAs($admin)->get(route('admin.lecturers.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('lecturers.0.chips.0.assigned', false)
        );
    }

    public function test_store_creates_a_lecturer_pending_credential_setup(): void
    {
        $admin = User::factory()->examOfficer()->create();

        $response = $this->actingAs($admin)->post(route('admin.lecturers.store'), [
            'name' => 'Dr. Chidi Okafor',
            'email' => 'chidi.okafor@unitystate.edu.ng',
        ]);

        $response->assertRedirect(route('admin.lecturers.index'));
        $response->assertSessionHas('toast');

        $lecturer = User::where('email', 'chidi.okafor@unitystate.edu.ng')->sole();

        $this->assertSame('Dr. Chidi Okafor', $lecturer->name);
        $this->assertSame(StaffRole::Lecturer, $lecturer->role);
        $this->assertNull($lecturer->password_set_at);
        $this->assertFalse(Hash::check('password', $lecturer->password));
    }

    public function test_store_rejects_a_duplicate_email(): void
    {
        $admin = User::factory()->examOfficer()->create();
        User::factory()->create(['email' => 'taken@unitystate.edu.ng']);

        $response = $this->actingAs($admin)->post(route('admin.lecturers.store'), [
            'name' => 'Someone Else',
            'email' => 'taken@unitystate.edu.ng',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', 'taken@unitystate.edu.ng')->count());
    }

    public function test_toggle_course_creates_then_removes_an_allocation(): void
    {
        $session = $this->currentSession();
        $course = $this->course('CSC401');
        $admin = User::factory()->hod()->create();
        $lecturer = User::factory()->lecturer()->create(['name' => 'Aisha Balogun']);

        $this->assertDatabaseMissing('course_allocations', [
            'course_id' => $course->id,
            'user_id' => $lecturer->id,
            'academic_session_id' => $session->id,
        ]);

        $assign = $this->actingAs($admin)->post(
            route('admin.lecturers.toggle-course', [$lecturer, $course]),
        );

        $assign->assertRedirect(route('admin.lecturers.index'));
        $assign->assertSessionHas('toast', 'CSC401 assigned to Aisha Balogun');
        $this->assertDatabaseHas('course_allocations', [
            'course_id' => $course->id,
            'user_id' => $lecturer->id,
            'academic_session_id' => $session->id,
        ]);

        $remove = $this->actingAs($admin)->post(
            route('admin.lecturers.toggle-course', [$lecturer, $course]),
        );

        $remove->assertSessionHas('toast', 'CSC401 removed from Aisha Balogun');
        $this->assertDatabaseMissing('course_allocations', [
            'course_id' => $course->id,
            'user_id' => $lecturer->id,
            'academic_session_id' => $session->id,
        ]);
    }

    public function test_toggle_course_without_a_current_session_does_not_error(): void
    {
        $course = $this->course('CSC401');
        $admin = User::factory()->hod()->create();
        $lecturer = User::factory()->lecturer()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.lecturers.toggle-course', [$lecturer, $course]),
        );

        $response->assertRedirect(route('admin.lecturers.index'));
        $response->assertSessionHas('toast');
        $this->assertDatabaseCount('course_allocations', 0);
    }

    public function test_lecturer_role_is_forbidden_from_all_lecturer_admin_routes(): void
    {
        $lecturer = User::factory()->lecturer()->create();
        $course = $this->course('CSC401');
        $target = User::factory()->lecturer()->create();

        $this->actingAs($lecturer)
            ->get(route('admin.lecturers.index'))
            ->assertForbidden();

        $this->actingAs($lecturer)
            ->post(route('admin.lecturers.store'), [
                'name' => 'Someone New',
                'email' => 'someone.new@unitystate.edu.ng',
            ])
            ->assertForbidden();

        $this->actingAs($lecturer)
            ->post(route('admin.lecturers.toggle-course', [$target, $course]))
            ->assertForbidden();
    }
}

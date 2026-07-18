<?php

namespace Tests\Feature\Admin;

use App\Enums\ModeOfStudy;
use App\Models\AcademicSession;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private function seedSettings(): void
    {
        Setting::set('bioUpdateOpen', false);
        Setting::set('masterSort', 'cgpa');
        Setting::set('programme_duration_years', 4);
        Setting::set('institution_name', 'Unity State University');
        Setting::set('faculty_name', 'Faculty of Computing');
        Setting::set('department_name', 'Department of Computer Science');
        Setting::set('programme_name', 'B.Sc Computer Science');
    }

    public function test_index_returns_settings_and_sessions(): void
    {
        $this->seedSettings();
        $admin = User::factory()->hod()->create();
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Settings/Index')
            ->where('settings.institution_name', 'Unity State University')
            ->where('hodSignatureUrl', null)
            ->where('examOfficerSignatureUrl', null)
            ->has('sessions', 1)
        );
    }

    public function test_update_persists_settings(): void
    {
        $this->seedSettings();
        $admin = User::factory()->hod()->create();

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'bioUpdateOpen' => true,
            'masterSort' => 'matno',
            'programme_duration_years' => 5,
            'institution_name' => 'New University',
            'faculty_name' => 'Faculty of Computing',
            'department_name' => 'Department of Computer Science',
            'programme_name' => 'B.Sc Computer Science',
        ])->assertRedirect(route('admin.settings.index'));

        $this->assertTrue(Setting::get('bioUpdateOpen'));
        $this->assertSame('matno', Setting::get('masterSort'));
        $this->assertSame(5, Setting::get('programme_duration_years'));
        $this->assertSame('New University', Setting::get('institution_name'));
    }

    public function test_update_stores_signature_uploads_and_index_exposes_their_urls(): void
    {
        Storage::fake('public');
        $this->seedSettings();
        $admin = User::factory()->hod()->create();

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'bioUpdateOpen' => true,
            'masterSort' => 'matno',
            'programme_duration_years' => 5,
            'institution_name' => 'Unity State University',
            'faculty_name' => 'Faculty of Computing',
            'department_name' => 'Department of Computer Science',
            'programme_name' => 'B.Sc Computer Science',
            'hod_signature' => UploadedFile::fake()->image('hod.png'),
            'exam_officer_signature' => UploadedFile::fake()->image('eo.png'),
        ])->assertRedirect(route('admin.settings.index'));

        $hodPath = Setting::get('hod_signature_path');
        $examOfficerPath = Setting::get('exam_officer_signature_path');

        $this->assertNotNull($hodPath);
        $this->assertNotNull($examOfficerPath);
        Storage::disk('public')->assertExists($hodPath);
        Storage::disk('public')->assertExists($examOfficerPath);

        $this->actingAs($admin)->get(route('admin.settings.index'))
            ->assertInertia(fn ($page) => $page
                ->where('hodSignatureUrl', Storage::disk('public')->url($hodPath))
                ->where('examOfficerSignatureUrl', Storage::disk('public')->url($examOfficerPath))
            );
    }

    public function test_update_without_new_signatures_leaves_existing_ones_untouched(): void
    {
        Storage::fake('public');
        $this->seedSettings();
        $admin = User::factory()->hod()->create();
        Setting::set('hod_signature_path', UploadedFile::fake()->image('hod.png')->store('signatures', 'public'));

        $this->actingAs($admin)->patch(route('admin.settings.update'), [
            'bioUpdateOpen' => true,
            'masterSort' => 'matno',
            'programme_duration_years' => 5,
            'institution_name' => 'Unity State University',
            'faculty_name' => 'Faculty of Computing',
            'department_name' => 'Department of Computer Science',
            'programme_name' => 'B.Sc Computer Science',
        ])->assertRedirect(route('admin.settings.index'));

        $this->assertNotNull(Setting::get('hod_signature_path'));
    }

    public function test_storing_a_new_current_session_unsets_the_previous_one(): void
    {
        $admin = User::factory()->hod()->create();
        $old = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $this->actingAs($admin)->post(route('admin.settings.sessions.store'), [
            'name' => '2026/2027',
            'is_current' => true,
        ])->assertRedirect(route('admin.settings.index'));

        $this->assertFalse($old->fresh()->is_current);
        $this->assertTrue(AcademicSession::where('name', '2026/2027')->first()->is_current);
    }

    public function test_set_current_session_flips_the_current_flag(): void
    {
        $admin = User::factory()->hod()->create();
        $a = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $b = AcademicSession::create(['name' => '2026/2027', 'is_current' => false]);

        $this->actingAs($admin)->post(route('admin.settings.sessions.set-current', $b->id))
            ->assertRedirect(route('admin.settings.index'));

        $this->assertFalse($a->fresh()->is_current);
        $this->assertTrue($b->fresh()->is_current);
    }

    public function test_advance_cohort_moves_active_students_up_one_level_and_skips_already_enrolled_and_inactive(): void
    {
        $admin = User::factory()->hod()->create();
        $from = AcademicSession::create(['name' => '2025/2026', 'is_current' => false]);
        $to = AcademicSession::create(['name' => '2026/2027', 'is_current' => true]);

        $active = Student::create([
            'mat_no' => 'U2025/0001', 'entry_year' => 2025,
            'last_name' => 'Okafor', 'first_name' => 'Adaeze',
            'mode_of_study' => ModeOfStudy::FullTime, 'is_active' => true,
        ]);
        $inactive = Student::create([
            'mat_no' => 'U2025/0002', 'entry_year' => 2025,
            'last_name' => 'Musa', 'first_name' => 'Ibrahim',
            'mode_of_study' => ModeOfStudy::FullTime, 'is_active' => false,
        ]);
        $alreadyEnrolled = Student::create([
            'mat_no' => 'U2025/0003', 'entry_year' => 2025,
            'last_name' => 'Eze', 'first_name' => 'Victor',
            'mode_of_study' => ModeOfStudy::FullTime, 'is_active' => true,
        ]);

        foreach ([$active, $inactive, $alreadyEnrolled] as $student) {
            StudentEnrollment::create([
                'student_id' => $student->id,
                'academic_session_id' => $from->id,
                'level' => 100,
                'mode_of_study' => ModeOfStudy::FullTime,
            ]);
        }

        // Already has a (different-level) enrollment in the target session.
        StudentEnrollment::create([
            'student_id' => $alreadyEnrolled->id,
            'academic_session_id' => $to->id,
            'level' => 300,
            'mode_of_study' => ModeOfStudy::FullTime,
        ]);

        $this->actingAs($admin)->post(route('admin.settings.advance-cohort'), [
            'from_session_id' => $from->id,
            'from_level' => 100,
            'to_session_id' => $to->id,
        ])->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('student_enrollments', [
            'student_id' => $active->id,
            'academic_session_id' => $to->id,
            'level' => 200,
        ]);
        $this->assertDatabaseMissing('student_enrollments', [
            'student_id' => $inactive->id,
            'academic_session_id' => $to->id,
        ]);
        // Unchanged: still only the pre-existing level-300 row, no new level-200 row added.
        $this->assertSame(
            1,
            StudentEnrollment::where('student_id', $alreadyEnrolled->id)
                ->where('academic_session_id', $to->id)
                ->count(),
        );
    }

    public function test_lecturer_cannot_access_settings_routes(): void
    {
        $lecturer = User::factory()->lecturer()->create();

        $this->actingAs($lecturer)->get(route('admin.settings.index'))->assertForbidden();
    }
}

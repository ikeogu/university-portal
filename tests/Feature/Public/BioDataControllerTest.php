<?php

namespace Tests\Feature\Public;

use App\Enums\ModeOfStudy;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BioDataControllerTest extends TestCase
{
    use RefreshDatabase;

    private const PIN = '222222';

    private function seedStudent(): Student
    {
        return Student::create([
            'mat_no' => 'U2022/5570001', 'entry_year' => 2022,
            'last_name' => 'Okafor', 'first_name' => 'Adaeze',
            'dob' => '2003-03-14', 'state_of_origin' => 'Anambra',
            'mode_of_study' => ModeOfStudy::FullTime,
            'access_pin' => self::PIN,
        ]);
    }

    private function verifiedSession(Student $student): array
    {
        return [
            'public_student_id' => $student->id,
            'public_verified_until' => now()->addMinutes(20),
        ];
    }

    public function test_edit_shows_the_verify_form_without_a_verified_session(): void
    {
        $response = $this->get(route('public.bio.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/BioData')
            ->where('verified', false)
        );
    }

    public function test_verify_rejects_an_unknown_mat_no_or_wrong_pin(): void
    {
        $student = $this->seedStudent();

        $this->post(route('public.bio.verify'), ['mat_no' => 'U2099/0000001', 'pin' => '000000'])
            ->assertSessionHasErrors('mat_no');
        $this->assertNull(session('public_student_id'));

        $this->post(route('public.bio.verify'), ['mat_no' => $student->mat_no, 'pin' => '999999'])
            ->assertSessionHasErrors('mat_no');
        $this->assertNull(session('public_student_id'));
    }

    public function test_verify_establishes_a_session_and_redirects_to_edit(): void
    {
        $student = $this->seedStudent();

        $response = $this->post(route('public.bio.verify'), [
            'mat_no' => strtolower($student->mat_no),
            'pin' => self::PIN,
        ]);

        $response->assertRedirect(route('public.bio.edit'));
        $this->assertSame($student->id, session('public_student_id'));
    }

    public function test_verify_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('public.bio.verify'), ['mat_no' => 'U2099/0000001', 'pin' => '000000']);
        }

        $response = $this->post(route('public.bio.verify'), ['mat_no' => 'U2099/0000001', 'pin' => '000000']);

        $response->assertStatus(429);
    }

    public function test_edit_shows_a_closed_notice_when_bio_updates_are_closed(): void
    {
        Setting::set('bioUpdateOpen', false);
        $student = $this->seedStudent();

        $response = $this->withSession($this->verifiedSession($student))
            ->get(route('public.bio.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/BioData')
            ->where('verified', true)
            ->where('closed', true)
        );
    }

    public function test_edit_shows_the_students_current_bio_data_when_open(): void
    {
        Setting::set('bioUpdateOpen', true);
        $student = $this->seedStudent();

        $response = $this->withSession($this->verifiedSession($student))
            ->get(route('public.bio.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/BioData')
            ->where('verified', true)
            ->where('closed', false)
            ->where('student.mat_no', 'U2022/5570001')
            ->where('student.last_name', 'Okafor')
            ->where('student.dob', '2003-03-14')
        );
    }

    public function test_update_is_rejected_when_bio_updates_are_closed(): void
    {
        Setting::set('bioUpdateOpen', false);
        $student = $this->seedStudent();

        $response = $this->withSession($this->verifiedSession($student))
            ->patch(route('public.bio.update'), [
                'last_name' => 'Changed', 'first_name' => 'Adaeze',
                'mode_of_study' => 'full_time',
            ]);

        $response->assertRedirect(route('public.bio.edit'));
        $this->assertSame('Okafor', $student->fresh()->last_name);
    }

    public function test_update_saves_corrected_bio_data_and_uploaded_photo(): void
    {
        Storage::fake('public');
        Setting::set('bioUpdateOpen', true);
        $student = $this->seedStudent();

        $response = $this->withSession($this->verifiedSession($student))
            ->patch(route('public.bio.update'), [
                'last_name' => 'Okafor',
                'first_name' => 'Adaeze',
                'middle_name' => 'Chioma',
                'dob' => '2003-03-15',
                'state_of_origin' => 'Anambra',
                'marital_status' => 'single',
                'mode_of_study' => 'full_time',
                'photo' => UploadedFile::fake()->image('me.jpg'),
            ]);

        $response->assertRedirect(route('public.bio.edit'));

        $student->refresh();
        $this->assertSame('Chioma', $student->middle_name);
        $this->assertSame('2003-03-15', $student->dob->format('Y-m-d'));
        $this->assertNotNull($student->photo_path);
        Storage::disk('public')->assertExists($student->photo_path);
    }

    public function test_update_deletes_the_old_photo_when_replaced(): void
    {
        Storage::fake('public');
        Setting::set('bioUpdateOpen', true);
        $student = $this->seedStudent();
        $student->update(['photo_path' => 'students/old.jpg']);
        Storage::disk('public')->put('students/old.jpg', 'old-bytes');

        $this->withSession($this->verifiedSession($student))
            ->patch(route('public.bio.update'), [
                'last_name' => 'Okafor', 'first_name' => 'Adaeze',
                'mode_of_study' => 'full_time',
                'photo' => UploadedFile::fake()->image('new.jpg'),
            ]);

        Storage::disk('public')->assertMissing('students/old.jpg');
    }

    public function test_update_redirects_back_to_edit_once_the_verification_window_has_expired(): void
    {
        $student = $this->seedStudent();

        $response = $this->withSession([
            'public_student_id' => $student->id,
            'public_verified_until' => now()->subMinute(),
        ])->patch(route('public.bio.update'), [
            'last_name' => 'Okafor', 'first_name' => 'Adaeze',
            'mode_of_study' => 'full_time',
        ]);

        $response->assertRedirect(route('public.bio.edit'));
    }
}

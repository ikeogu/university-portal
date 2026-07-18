<?php

namespace Tests\Feature\Console;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateFirstAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_first_admin_account(): void
    {
        $this->artisan('admin:create-first')
            ->expectsQuestion('Full name', 'Prof. Kelechi Nnamdi')
            ->expectsQuestion('Email address', 'hod@unitystate.edu.ng')
            ->expectsQuestion('Password', 'password123')
            ->expectsChoice('Role', 'exam_officer', [
                'exam_officer' => StaffRole::ExamOfficer->label(),
                'hod' => StaffRole::Hod->label(),
            ])
            ->assertExitCode(0);

        $user = User::sole();

        $this->assertSame('Prof. Kelechi Nnamdi', $user->name);
        $this->assertSame('hod@unitystate.edu.ng', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertSame(StaffRole::ExamOfficer, $user->role);
        $this->assertNotNull($user->password_set_at);
    }

    public function test_it_refuses_to_run_once_a_staff_account_exists(): void
    {
        User::factory()->create();

        $this->artisan('admin:create-first')->assertExitCode(1);

        $this->assertSame(1, User::count());
    }
}

<?php

namespace App\Console\Commands;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[Signature('admin:create-first')]
#[Description('Create the first Exam Officer/HoD account (only works while the users table is empty)')]
class CreateFirstAdmin extends Command
{
    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->error('There is already at least one staff account. Use the admin panel to add more staff.');

            return self::FAILURE;
        }

        $name = text(
            label: 'Full name',
            required: true,
        );

        $email = text(
            label: 'Email address',
            required: true,
            validate: fn (string $value) => Validator::make(['email' => $value], ['email' => 'email'])
                ->errors()->first('email'),
        );

        $password = password(
            label: 'Password',
            required: true,
            validate: fn (string $value) => strlen($value) < 8 ? 'Password must be at least 8 characters.' : null,
        );

        $role = select(
            label: 'Role',
            options: [
                StaffRole::ExamOfficer->value => StaffRole::ExamOfficer->label(),
                StaffRole::Hod->value => StaffRole::Hod->label(),
            ],
            default: StaffRole::ExamOfficer->value,
        );

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => StaffRole::from($role),
            'password_set_at' => now(),
        ]);

        $this->info("Created {$role} account for {$name} <{$email}>.");

        return self::SUCCESS;
    }
}

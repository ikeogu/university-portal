<?php

namespace Database\Factories;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => StaffRole::Lecturer,
            'password_set_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function lecturer(): static
    {
        return $this->state(fn (array $attributes) => ['role' => StaffRole::Lecturer]);
    }

    public function examOfficer(): static
    {
        return $this->state(fn (array $attributes) => ['role' => StaffRole::ExamOfficer]);
    }

    public function hod(): static
    {
        return $this->state(fn (array $attributes) => ['role' => StaffRole::Hod]);
    }
}

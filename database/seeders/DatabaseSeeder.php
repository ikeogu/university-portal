<?php

namespace Database\Seeders;

use App\Models\AcademicSession;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SettingsSeeder::class);

        if (AcademicSession::current() === null) {
            AcademicSession::query()->firstOrCreate(
                ['name' => '2025/2026'],
                ['is_current' => true],
            );
        }
    }
}

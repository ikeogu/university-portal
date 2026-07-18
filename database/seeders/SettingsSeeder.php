<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            'bioUpdateOpen' => false,
            'masterSort' => 'cgpa',
            'programme_duration_years' => 4,
            'institution_name' => 'University of Port Harcourt',
            'faculty_name' => 'Faculty of Humanities',
            'department_name' => 'Department of Eglish',
            'programme_name' => 'Bachelor of Arts in English',
        ];

        foreach ($defaults as $key => $value) {
            if (Setting::find($key) === null) {
                Setting::set($key, $value);
            }
        }
    }
}
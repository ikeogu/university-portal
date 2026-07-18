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
            'institution_name' => 'Unity State University',
            'faculty_name' => 'Faculty of Computing',
            'department_name' => 'Department of Computer Science',
            'programme_name' => 'B.Sc Computer Science',
        ];

        foreach ($defaults as $key => $value) {
            if (Setting::find($key) === null) {
                Setting::set($key, $value);
            }
        }
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Enums\ModeOfStudy;
use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Score;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterSheetControllerTest extends TestCase
{
    use RefreshDatabase;

    private function seedGraduate(string $matNo, int $mark): Student
    {
        $session = AcademicSession::firstOrCreate(['name' => '2025/2026'], ['is_current' => true]);
        $student = Student::create([
            'mat_no' => $matNo, 'entry_year' => 2022,
            'last_name' => 'Test', 'first_name' => $matNo, 'mode_of_study' => ModeOfStudy::FullTime,
        ]);
        StudentEnrollment::create(['student_id' => $student->id, 'academic_session_id' => $session->id, 'level' => 400, 'mode_of_study' => ModeOfStudy::FullTime]);
        $course = Course::create(['code' => "C-{$matNo}", 'title' => 'Course', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        Score::create(['student_id' => $student->id, 'course_id' => $course->id, 'academic_session_id' => $session->id, 'credit_units_at_entry' => 3, 'ca' => 0, 'exam' => $mark]);

        return $student;
    }

    public function test_index_shows_the_selected_sets_ranked_students(): void
    {
        Setting::set('programme_duration_years', 4);
        $admin = User::factory()->hod()->create();
        $this->seedGraduate('U2022/0001', 90);

        $response = $this->actingAs($admin)->get(route('admin.master.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Master/Index')
            ->where('selectedSet', 2022)
            ->has('rows', 1)
            ->where('rows.0.class', 'First Class')
        );
    }

    public function test_lecturer_cannot_access_master_sheet(): void
    {
        $lecturer = User::factory()->lecturer()->create();

        $this->actingAs($lecturer)->get(route('admin.master.index'))->assertForbidden();
    }

    public function test_print_view_batches_all_sets(): void
    {
        Setting::set('programme_duration_years', 4);
        $admin = User::factory()->hod()->create();
        $this->seedGraduate('U2022/0001', 90);

        $response = $this->actingAs($admin)->get(route('admin.master.print'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Print/MasterSheet')
            ->has('groups', 1)
            ->where('groups.0.label', '2022 SET')
            ->where('total', 1)
        );
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ModeOfStudy;
use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\Score;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreEntryControllerTest extends TestCase
{
    use RefreshDatabase;

    private function setUpCourseAndStudent(): array
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        $student = Student::create([
            'mat_no' => 'U2022/0001', 'entry_year' => 2022,
            'last_name' => 'Okafor', 'first_name' => 'Adaeze',
            'mode_of_study' => ModeOfStudy::FullTime,
        ]);
        StudentEnrollment::create([
            'student_id' => $student->id, 'academic_session_id' => $session->id,
            'level' => 400, 'mode_of_study' => ModeOfStudy::FullTime,
        ]);

        return [$session, $course, $student];
    }

    public function test_allocated_lecturer_can_view_the_roster_with_computed_grades(): void
    {
        [$session, $course, $student] = $this->setUpCourseAndStudent();
        $lecturer = User::factory()->lecturer()->create();
        CourseAllocation::create(['course_id' => $course->id, 'user_id' => $lecturer->id, 'academic_session_id' => $session->id]);

        Score::create([
            'student_id' => $student->id, 'course_id' => $course->id, 'academic_session_id' => $session->id,
            'credit_units_at_entry' => 3, 'ca' => 25, 'exam' => 45,
        ]);

        $response = $this->actingAs($lecturer)->get(route('scores.show', $course));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('ScoreEntry/Show')
            ->where('rows.0.mat_no', 'U2022/0001')
            ->where('rows.0.grade', 'A') // 25+45=70
        );
    }

    public function test_unallocated_lecturer_is_forbidden(): void
    {
        [, $course] = $this->setUpCourseAndStudent();
        $lecturer = User::factory()->lecturer()->create();

        $this->actingAs($lecturer)->get(route('scores.show', $course))->assertForbidden();
    }

    public function test_admin_can_view_and_update_any_course_without_an_allocation(): void
    {
        [$session, $course, $student] = $this->setUpCourseAndStudent();
        $admin = User::factory()->hod()->create();

        $this->actingAs($admin)->get(route('scores.show', $course))->assertOk();

        $this->actingAs($admin)->put(route('scores.update', $course), [
            'scores' => [$student->id => ['ca' => 20, 'exam' => 50]],
        ])->assertRedirect(route('scores.show', $course));

        $score = Score::where('student_id', $student->id)->where('course_id', $course->id)->sole();
        $this->assertSame(20, $score->ca);
        $this->assertSame(50, $score->exam);
        $this->assertSame($admin->id, $score->updated_by);
    }

    public function test_update_clamps_out_of_range_values(): void
    {
        [$session, $course, $student] = $this->setUpCourseAndStudent();
        $admin = User::factory()->hod()->create();

        // Visiting the entry screen is what materializes the Score roster
        // row in real usage — update() never runs without show() first.
        $this->actingAs($admin)->get(route('scores.show', $course));

        $this->actingAs($admin)->put(route('scores.update', $course), [
            'scores' => [$student->id => ['ca' => 999, 'exam' => -10]],
        ])->assertRedirect();

        $score = Score::where('student_id', $student->id)->sole();
        $this->assertSame(30, $score->ca);
        $this->assertSame(0, $score->exam);
    }
}

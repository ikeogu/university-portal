<?php

namespace Tests\Feature\Services;

use App\Enums\ModeOfStudy;
use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Score;
use App\Models\ScoreAuditLog;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\Academic\ScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(string $matNo, bool $active = true): Student
    {
        return Student::create([
            'mat_no' => $matNo, 'entry_year' => 2022,
            'last_name' => 'Test', 'first_name' => $matNo,
            'mode_of_study' => ModeOfStudy::FullTime, 'is_active' => $active,
        ]);
    }

    private function enroll(Student $student, AcademicSession $session, int $level): void
    {
        StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'level' => $level,
            'mode_of_study' => ModeOfStudy::FullTime,
        ]);
    }

    public function test_roster_creates_missing_score_rows_snapshotting_credit_units(): void
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        $student = $this->makeStudent('U2022/0001');
        $this->enroll($student, $session, 400);

        $roster = (new ScoreService)->rosterFor($course, $session);

        $this->assertCount(1, $roster);
        $this->assertSame($student->id, $roster->first()->student_id);
        $this->assertSame(3, $roster->first()->credit_units_at_entry);
        $this->assertNull($roster->first()->ca);
    }

    public function test_roster_excludes_inactive_students_and_students_at_a_different_level(): void
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);

        $eligible = $this->makeStudent('U2022/0001');
        $this->enroll($eligible, $session, 400);

        $inactive = $this->makeStudent('U2022/0002', active: false);
        $this->enroll($inactive, $session, 400);

        $wrongLevel = $this->makeStudent('U2022/0003');
        $this->enroll($wrongLevel, $session, 300);

        $roster = (new ScoreService)->rosterFor($course, $session);

        $this->assertCount(1, $roster);
        $this->assertSame($eligible->id, $roster->first()->student_id);
    }

    public function test_roster_does_not_duplicate_existing_score_rows(): void
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        $student = $this->makeStudent('U2022/0001');
        $this->enroll($student, $session, 400);

        $service = new ScoreService;
        $service->rosterFor($course, $session);
        $service->rosterFor($course, $session);

        $this->assertSame(1, Score::count());
    }

    public function test_save_scores_clamps_values_logs_changes_and_records_the_actor(): void
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        $student = $this->makeStudent('U2022/0001');
        $this->enroll($student, $session, 400);
        $lecturer = User::factory()->lecturer()->create();

        $service = new ScoreService;
        $roster = $service->rosterFor($course, $session);
        $score = $roster->first();

        $service->saveScores($course, $session, [
            $student->id => ['ca' => 999, 'exam' => -5], // out-of-range, should clamp to 30 / 0
        ], $lecturer);

        $score->refresh();
        $this->assertSame(30, $score->ca);
        $this->assertSame(0, $score->exam);
        $this->assertSame($lecturer->id, $score->updated_by);

        $log = ScoreAuditLog::sole();
        $this->assertSame($score->id, $log->score_id);
        $this->assertSame($lecturer->id, $log->changed_by);
        $this->assertNull($log->old_ca);
        $this->assertNull($log->old_exam);
        $this->assertSame(30, $log->new_ca);
        $this->assertSame(0, $log->new_exam);
    }

    public function test_save_scores_does_not_log_when_values_are_unchanged(): void
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        $student = $this->makeStudent('U2022/0001');
        $this->enroll($student, $session, 400);
        $lecturer = User::factory()->lecturer()->create();

        $service = new ScoreService;
        $service->rosterFor($course, $session);

        $service->saveScores($course, $session, [$student->id => ['ca' => 20, 'exam' => 50]], $lecturer);
        $this->assertSame(1, ScoreAuditLog::count());

        $service->saveScores($course, $session, [$student->id => ['ca' => 20, 'exam' => 50]], $lecturer);
        $this->assertSame(1, ScoreAuditLog::count());
    }

    public function test_save_scores_ignores_entries_for_students_outside_the_course_roster(): void
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        $student = $this->makeStudent('U2022/0001');
        $this->enroll($student, $session, 400);
        $lecturer = User::factory()->lecturer()->create();

        $stranger = $this->makeStudent('U2022/9999');

        $service = new ScoreService;
        $service->rosterFor($course, $session);

        $service->saveScores($course, $session, [
            $stranger->id => ['ca' => 10, 'exam' => 10],
        ], $lecturer);

        $this->assertSame(0, Score::where('student_id', $stranger->id)->count());
    }
}

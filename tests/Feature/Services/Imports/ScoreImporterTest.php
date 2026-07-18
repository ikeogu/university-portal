<?php

namespace Tests\Feature\Services\Imports;

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
use App\Services\Imports\ScoreImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ScoreImporterTest extends TestCase
{
    use RefreshDatabase;

    private function setUpFixtures(): array
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $courseA = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        $courseB = Course::create(['code' => 'CSC 403', 'title' => 'OS II', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);

        $student1 = Student::create(['mat_no' => 'U2022/0001', 'entry_year' => 2022, 'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime]);
        $student2 = Student::create(['mat_no' => 'U2022/0002', 'entry_year' => 2022, 'last_name' => 'Musa', 'first_name' => 'Ibrahim', 'mode_of_study' => ModeOfStudy::FullTime]);

        foreach ([$student1, $student2] as $student) {
            StudentEnrollment::create(['student_id' => $student->id, 'academic_session_id' => $session->id, 'level' => 400, 'mode_of_study' => ModeOfStudy::FullTime]);
        }

        return [$session, $courseA, $courseB, $student1, $student2];
    }

    private function importer(): ScoreImporter
    {
        return new ScoreImporter(new ScoreService);
    }

    public function test_imports_scores_for_multiple_courses_per_row(): void
    {
        [$session, $courseA, $courseB, $student1, $student2] = $this->setUpFixtures();
        $lecturer = User::factory()->lecturer()->create();

        $result = $this->importer()->import([
            ['U2022/0001', 'OKAFOR, Adaeze', 25, 45, 20, 40], // CSC401 ca25 exam45, CSC403 ca20 exam40
            ['U2022/0002', 'MUSA, Ibrahim', 10, 20, 15, 30],
        ], $session, new Collection([$courseA, $courseB]), $lecturer);

        $this->assertSame(2, $result->added);

        $score1a = Score::where('student_id', $student1->id)->where('course_id', $courseA->id)->sole();
        $this->assertSame(25, $score1a->ca);
        $this->assertSame(45, $score1a->exam);

        $score1b = Score::where('student_id', $student1->id)->where('course_id', $courseB->id)->sole();
        $this->assertSame(20, $score1b->ca);
        $this->assertSame(40, $score1b->exam);

        $score2a = Score::where('student_id', $student2->id)->where('course_id', $courseA->id)->sole();
        $this->assertSame(10, $score2a->ca);
        $this->assertSame(20, $score2a->exam);
    }

    public function test_unknown_matriculation_number_is_reported_as_an_error_and_never_auto_onboarded(): void
    {
        [$session, $courseA, $courseB] = $this->setUpFixtures();
        $lecturer = User::factory()->lecturer()->create();

        $result = $this->importer()->import([
            ['U2099/9999', 'GHOST, Student', 25, 45, 20, 40],
        ], $session, new Collection([$courseA, $courseB]), $lecturer);

        $this->assertSame(0, $result->added);
        $this->assertCount(1, $result->errors);
        $this->assertSame(2, Student::count()); // the 2 fixture students only — no new student created
    }

    public function test_reimporting_upserts_rather_than_skips_and_audit_logs_the_change(): void
    {
        [$session, $courseA, $courseB, $student1] = $this->setUpFixtures();
        $lecturer = User::factory()->lecturer()->create();
        $courses = new Collection([$courseA, $courseB]);

        $this->importer()->import([
            ['U2022/0001', 'OKAFOR, Adaeze', 25, 45, 20, 40],
        ], $session, $courses, $lecturer);

        // Re-upload with a correction to CSC401's CA.
        $this->importer()->import([
            ['U2022/0001', 'OKAFOR, Adaeze', 30, 45, 20, 40],
        ], $session, $courses, $lecturer);

        $score = Score::where('student_id', $student1->id)->where('course_id', $courseA->id)->sole();
        $this->assertSame(30, $score->ca);
        // 2, not 1: the first import already logs a real change (null -> 25/45
        // on an empty roster row), then the re-upload logs the 25 -> 30 correction.
        $this->assertSame(2, ScoreAuditLog::where('score_id', $score->id)->count());
    }
}

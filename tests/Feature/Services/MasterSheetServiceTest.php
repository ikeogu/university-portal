<?php

namespace Tests\Feature\Services;

use App\Domain\Grading\ClassOfDegree;
use App\Enums\ModeOfStudy;
use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Score;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\Academic\CumulativeResultCalculator;
use App\Services\Academic\MasterSheetService;
use App\Services\Academic\SemesterResultCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterSheetServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): MasterSheetService
    {
        return new MasterSheetService(new CumulativeResultCalculator(new SemesterResultCalculator));
    }

    /** Enroll $student at $level in a freshly created session and score one course there. */
    private function enrollAndScore(Student $student, int $level, int $mark, int $cu = 3, int $daysAgo = 0): void
    {
        $session = AcademicSession::create(['name' => "session-L{$level}-{$student->id}", 'is_current' => false]);
        $session->created_at = now()->subDays($daysAgo);
        $session->save();

        StudentEnrollment::create([
            'student_id' => $student->id, 'academic_session_id' => $session->id,
            'level' => $level, 'mode_of_study' => ModeOfStudy::FullTime,
        ]);

        $course = Course::create([
            'code' => "CRS-{$level}-{$student->id}", 'title' => 'Course', 'credit_units' => $cu,
            'semester' => Semester::First, 'level' => $level,
        ]);

        Score::create([
            'student_id' => $student->id, 'course_id' => $course->id, 'academic_session_id' => $session->id,
            'credit_units_at_entry' => $cu, 'ca' => (int) ($mark * 0.3), 'exam' => $mark - (int) ($mark * 0.3),
        ]);
    }

    public function test_terminal_level_derives_from_the_programme_duration_setting(): void
    {
        Setting::set('programme_duration_years', 4);
        $this->assertSame(400, $this->service()->terminalLevel());

        Setting::set('programme_duration_years', 5);
        $this->assertSame(500, $this->service()->terminalLevel());
    }

    public function test_graduating_sets_only_includes_students_who_reached_the_terminal_level(): void
    {
        Setting::set('programme_duration_years', 4);

        $graduate = Student::create(['mat_no' => 'U2022/0001', 'entry_year' => 2022, 'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime]);
        $this->enrollAndScore($graduate, 400, 70);

        $stillStudying = Student::create(['mat_no' => 'U2023/0001', 'entry_year' => 2023, 'last_name' => 'Musa', 'first_name' => 'Ibrahim', 'mode_of_study' => ModeOfStudy::FullTime]);
        $this->enrollAndScore($stillStudying, 200, 70);

        $sets = $this->service()->graduatingSets();

        $this->assertSame([2022], $sets->all());
    }

    public function test_for_set_computes_cgpa_and_class_and_sorts_by_cgpa_descending_by_default(): void
    {
        Setting::set('programme_duration_years', 4);

        $topStudent = Student::create(['mat_no' => 'U2022/0001', 'entry_year' => 2022, 'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime]);
        $this->enrollAndScore($topStudent, 400, 90); // high mark -> high CGPA

        $weakerStudent = Student::create(['mat_no' => 'U2022/0002', 'entry_year' => 2022, 'last_name' => 'Bello', 'first_name' => 'Yusuf', 'mode_of_study' => ModeOfStudy::FullTime]);
        $this->enrollAndScore($weakerStudent, 400, 42); // -> Pass-range CGPA

        $rows = $this->service()->forSet(2022);

        $this->assertSame('U2022/0001', $rows[0]['mat_no']);
        $this->assertSame(ClassOfDegree::FirstClass, $rows[0]['class']);
        $this->assertSame('U2022/0002', $rows[1]['mat_no']);
    }

    public function test_for_set_sorts_by_mat_no_when_requested(): void
    {
        Setting::set('programme_duration_years', 4);

        $b = Student::create(['mat_no' => 'U2022/0002', 'entry_year' => 2022, 'last_name' => 'Bello', 'first_name' => 'Yusuf', 'mode_of_study' => ModeOfStudy::FullTime]);
        $this->enrollAndScore($b, 400, 90);
        $a = Student::create(['mat_no' => 'U2022/0001', 'entry_year' => 2022, 'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime]);
        $this->enrollAndScore($a, 400, 42);

        $rows = $this->service()->forSet(2022, 'matno');

        $this->assertSame('U2022/0001', $rows[0]['mat_no']);
        $this->assertSame('U2022/0002', $rows[1]['mat_no']);
    }

    public function test_class_distribution_counts_each_class(): void
    {
        Setting::set('programme_duration_years', 4);

        $first = Student::create(['mat_no' => 'U2022/0001', 'entry_year' => 2022, 'last_name' => 'A', 'first_name' => 'A', 'mode_of_study' => ModeOfStudy::FullTime]);
        $this->enrollAndScore($first, 400, 90);
        $pass = Student::create(['mat_no' => 'U2022/0002', 'entry_year' => 2022, 'last_name' => 'B', 'first_name' => 'B', 'mode_of_study' => ModeOfStudy::FullTime]);
        $this->enrollAndScore($pass, 400, 42);

        $distribution = $this->service()->classDistribution($this->service()->forSet(2022));

        $this->assertSame(1, $distribution[ClassOfDegree::FirstClass->value]);
        $this->assertSame(1, $distribution[ClassOfDegree::Pass->value]);
        $this->assertSame(0, $distribution[ClassOfDegree::SecondUpper->value]);
    }

    /**
     * Real-world regression: the same 4-year University of Port Harcourt
     * sample used in CumulativeResultCalculatorTest, now run through the
     * full Master Sheet pipeline (per-level enrollment + real Score rows,
     * not raw ScoreRow objects) to confirm the published CGPA of 4.79 /
     * First Class comes out the same way end-to-end.
     */
    public function test_matches_the_real_reference_students_final_cgpa_and_class(): void
    {
        Setting::set('programme_duration_years', 4);

        $student = Student::create(['mat_no' => 'U2015/1825004', 'entry_year' => 2015, 'last_name' => 'ABDU', 'first_name' => 'ALHERI', 'middle_name' => 'THOMAS', 'mode_of_study' => ModeOfStudy::FullTime]);

        // [level, [ [cu, mark], ... ]] — combined semesters per level, LCS 102/206/412
        // omitted as in the original fixture (the sample's own "not used in
        // computation" rows; this system doesn't model that inclusion flag).
        $byLevel = [
            100 => [[3, 64], [3, 73], [2, 78], [2, 76], [3, 77], [3, 77], [3, 70], [3, 66], [3, 62], [2, 92], [3, 87], [3, 76], [3, 72]],
            200 => [[3, 68], [3, 93], [3, 80], [3, 73], [3, 70], [3, 80], [1, 71], [2, 64], [3, 82], [3, 65], [3, 83], [3, 72], [3, 80], [3, 75]],
            300 => [[3, 80], [3, 87], [3, 84], [3, 79], [3, 79], [3, 75], [2, 58], [3, 85], [3, 72], [3, 73], [3, 76], [3, 81], [3, 67]],
            400 => [[3, 80], [3, 64], [3, 71], [3, 82], [3, 76], [3, 85], [3, 73], [3, 89], [3, 69], [6, 70]],
        ];

        foreach ($byLevel as $level => $courseMarks) {
            $session = AcademicSession::create(['name' => "{$level}-session", 'is_current' => false]);
            $session->created_at = now()->subDays(1000 - $level);
            $session->save();

            StudentEnrollment::create([
                'student_id' => $student->id, 'academic_session_id' => $session->id,
                'level' => $level, 'mode_of_study' => ModeOfStudy::FullTime,
            ]);

            foreach ($courseMarks as $i => [$cu, $mark]) {
                $course = Course::create([
                    'code' => "L{$level}-{$i}", 'title' => 'Course', 'credit_units' => $cu,
                    'semester' => Semester::First, 'level' => $level,
                ]);
                Score::create([
                    'student_id' => $student->id, 'course_id' => $course->id, 'academic_session_id' => $session->id,
                    'credit_units_at_entry' => $cu, 'ca' => 0, 'exam' => $mark,
                ]);
            }
        }

        $row = $this->service()->forSet(2015)->sole();

        $this->assertSame(146, $row['tcu']);
        $this->assertSame(700, $row['tqp']);
        $this->assertEqualsWithDelta(4.7945205479452055, $row['cgpa'], 0.0000001);
        $this->assertSame(ClassOfDegree::FirstClass, $row['class']);
    }
}

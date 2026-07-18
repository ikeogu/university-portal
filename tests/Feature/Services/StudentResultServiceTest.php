<?php

namespace Tests\Feature\Services;

use App\Enums\ModeOfStudy;
use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Score;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\Academic\SemesterResultCalculator;
use App\Services\Academic\StudentResultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentResultServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeSession(string $name, int $daysAgo): AcademicSession
    {
        $session = AcademicSession::create(['name' => $name, 'is_current' => false]);
        $session->created_at = now()->subDays($daysAgo);
        $session->save();

        return $session;
    }

    public function test_sessions_for_are_ordered_chronologically_with_level(): void
    {
        $student = Student::create([
            'mat_no' => 'U2022/0001', 'entry_year' => 2022,
            'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime,
        ]);

        $y2 = $this->makeSession('2023/2024', 30);
        $y1 = $this->makeSession('2022/2023', 60);

        StudentEnrollment::create(['student_id' => $student->id, 'academic_session_id' => $y1->id, 'level' => 100, 'mode_of_study' => ModeOfStudy::FullTime]);
        StudentEnrollment::create(['student_id' => $student->id, 'academic_session_id' => $y2->id, 'level' => 200, 'mode_of_study' => ModeOfStudy::FullTime]);

        $sessions = (new StudentResultService(new SemesterResultCalculator))->sessionsFor($student);

        $this->assertSame(['2022/2023', '2023/2024'], $sessions->pluck('name')->all());
        $this->assertSame([100, 200], $sessions->pluck('level')->all());
    }

    public function test_semester_result_computes_only_the_given_session_and_semester(): void
    {
        $student = Student::create([
            'mat_no' => 'U2022/0001', 'entry_year' => 2022,
            'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime,
        ]);
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $courseS1 = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        $courseS2 = Course::create(['code' => 'CSC 402', 'title' => 'Networks', 'credit_units' => 3, 'semester' => Semester::Second, 'level' => 400]);

        Score::create(['student_id' => $student->id, 'course_id' => $courseS1->id, 'academic_session_id' => $session->id, 'credit_units_at_entry' => 3, 'ca' => 25, 'exam' => 45]); // mark70 -> A
        Score::create(['student_id' => $student->id, 'course_id' => $courseS2->id, 'academic_session_id' => $session->id, 'credit_units_at_entry' => 3, 'ca' => 10, 'exam' => 20]); // mark30 -> F, different semester

        $result = (new StudentResultService(new SemesterResultCalculator))->semesterResult($student, $session, Semester::First);

        $this->assertCount(1, $result['rows']);
        $this->assertSame('CSC 401', $result['rows'][0]['code']);
        $this->assertSame('A', $result['rows'][0]['grade']);
        $this->assertSame(3, $result['tcu']);
        $this->assertSame(5.0, $result['gpa']);
    }

    public function test_cumulative_as_of_excludes_sessions_after_the_selected_one(): void
    {
        $student = Student::create([
            'mat_no' => 'U2022/0001', 'entry_year' => 2022,
            'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime,
        ]);

        $y1 = $this->makeSession('2022/2023', 60);
        $y2 = $this->makeSession('2023/2024', 30);

        StudentEnrollment::create(['student_id' => $student->id, 'academic_session_id' => $y1->id, 'level' => 100, 'mode_of_study' => ModeOfStudy::FullTime]);
        StudentEnrollment::create(['student_id' => $student->id, 'academic_session_id' => $y2->id, 'level' => 200, 'mode_of_study' => ModeOfStudy::FullTime]);

        $courseY1 = Course::create(['code' => 'CSC 101', 'title' => 'Intro', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 100]);
        $courseY2 = Course::create(['code' => 'CSC 201', 'title' => 'Data Structures', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 200]);

        Score::create(['student_id' => $student->id, 'course_id' => $courseY1->id, 'academic_session_id' => $y1->id, 'credit_units_at_entry' => 3, 'ca' => 25, 'exam' => 45]); // A, qp15
        Score::create(['student_id' => $student->id, 'course_id' => $courseY2->id, 'academic_session_id' => $y2->id, 'credit_units_at_entry' => 3, 'ca' => 10, 'exam' => 20]); // F, qp0

        $asOfY1 = (new StudentResultService(new SemesterResultCalculator))->cumulativeAsOf($student, $y1);
        $this->assertSame(3, $asOfY1['tcu']);
        $this->assertSame(15, $asOfY1['tqp']);
        $this->assertSame(5.0, $asOfY1['cgpa']);

        $asOfY2 = (new StudentResultService(new SemesterResultCalculator))->cumulativeAsOf($student, $y2);
        $this->assertSame(6, $asOfY2['tcu']);
        $this->assertSame(15, $asOfY2['tqp']);
        $this->assertEqualsWithDelta(2.5, $asOfY2['cgpa'], 0.0001);
    }
}

<?php

namespace Tests\Feature\Services\Imports;

use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\Academic\EnrollmentService;
use App\Services\Imports\StudentImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentImporterTest extends TestCase
{
    use RefreshDatabase;

    private function importer(): StudentImporter
    {
        return new StudentImporter(new EnrollmentService);
    }

    public function test_imports_new_students_and_enrolls_them_at_entry_level(): void
    {
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $result = $this->importer()->import([
            ['U2022/5570013', 'CHUKWU, Somtochukwu Henry', '2003-03-07', 'Ebonyi', 'Single', 'Full time'],
            ['U2022/5570014', 'LAWAL, Kafayat Omowunmi', '2002-06-29', 'Kwara', 'Married', 'Part time'],
        ]);

        $this->assertSame(2, $result->added);
        $this->assertSame(0, $result->skipped);
        $this->assertEmpty($result->errors);

        $student = Student::where('mat_no', 'U2022/5570013')->sole();
        $this->assertSame('CHUKWU', $student->last_name);
        $this->assertSame('Somtochukwu', $student->first_name);
        $this->assertSame('Henry', $student->middle_name);
        $this->assertSame(2022, $student->entry_year);
        $this->assertTrue($student->dob->isSameDay('2003-03-07'));

        $enrollment = StudentEnrollment::where('student_id', $student->id)->sole();
        $this->assertSame($session->id, $enrollment->academic_session_id);
        $this->assertSame(100, $enrollment->level);
    }

    public function test_skips_duplicate_matriculation_numbers_both_existing_and_within_the_same_file(): void
    {
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        Student::create([
            'mat_no' => 'U2022/5570013', 'entry_year' => 2022,
            'last_name' => 'Chukwu', 'first_name' => 'Henry', 'mode_of_study' => \App\Enums\ModeOfStudy::FullTime,
        ]);

        $result = $this->importer()->import([
            ['U2022/5570013', 'CHUKWU, Somtochukwu Henry', '2003-03-07', 'Ebonyi', 'Single', 'Full time'], // already exists
            ['U2022/5570014', 'LAWAL, Kafayat Omowunmi', '2002-06-29', 'Kwara', 'Married', 'Part time'],
            ['U2022/5570014', 'LAWAL, Kafayat Omowunmi', '2002-06-29', 'Kwara', 'Married', 'Part time'], // duplicate within file
        ]);

        $this->assertSame(1, $result->added);
        $this->assertSame(2, $result->skipped);
        $this->assertSame(2, Student::count());
    }

    public function test_reports_rows_missing_a_matriculation_number_or_name_without_aborting_the_batch(): void
    {
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $result = $this->importer()->import([
            ['', 'CHUKWU, Somtochukwu Henry', '2003-03-07', 'Ebonyi', 'Single', 'Full time'],
            ['U2022/5570014', 'LAWAL, Kafayat Omowunmi', '2002-06-29', 'Kwara', 'Married', 'Part time'],
        ]);

        $this->assertSame(1, $result->added);
        $this->assertCount(1, $result->errors);
        $this->assertSame(2, $result->errors[0]['row']); // 1-indexed data row + header row
    }
}

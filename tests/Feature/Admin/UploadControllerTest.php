<?php

namespace Tests\Feature\Admin;

use App\Enums\ModeOfStudy;
use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class UploadControllerTest extends TestCase
{
    use RefreshDatabase;

    private function realXlsx(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows);

        $path = tempnam(sys_get_temp_dir(), 'upload_test').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'upload.xlsx', null, null, true);
    }

    /**
     * Builds a file matching the university's real "COURSE EXAMINATION MARK
     * SHEET" layout that lecturers actually send: header metadata in fixed
     * cells (rows 1-8), data from row 9, columns B=Mat No, F-L=Q1-Q7,
     * M=Penalty, N=Moderation, T=CA(30%). TOTAL SCORE/GRADE/EXAM(70%) are
     * left out here exactly as they're formulas in the real file — this app
     * never reads them, it recomputes from the raw Q/Penalty/Moderation/CA
     * columns instead.
     *
     * @param  array<int, array{mat_no: string, q?: array<int, int>, penalty?: int, moderation?: int, ca?: int}>  $students
     */
    private function lecturerSheetXlsx(string $courseCode, string $session, array $students): UploadedFile
    {
        $rows = [
            ['UNIVERSITY OF PORT HARCOURT', null, null, null, null, 'COURSE EXAMINATION MARK SHEET'],
            [],
            ['DEPARTMENT:TEST DEPARTMENT', null, null, null, 'Program: FULL-TIME', null, null, "SESSION:{$session}"],
            ["COURSE CODE: {$courseCode}", null, null, null, 'D. TYPE: UNDER-GRADUATE'],
            ['COURSE TITLE: Test Course', null, null, null, 'Student Set: 2022 '],
            ['COURSE UNIT: 3'],
            [],
            ['S/NO', 'MAT NO', 'TOTAL SCORE', 'GRADE', 'REMARKS', 'Q1', 'Q2', 'Q3', 'Q4', 'Q5', 'Q6', 'Q7', 'Penalty', 'Moderation', null, null, null, null, 'EXAM (70%)', 'CA (30%)', 'CREGID'],
        ];

        foreach ($students as $index => $student) {
            $row = array_fill(0, 21, null);
            $row[0] = $index + 1;
            $row[1] = $student['mat_no'];

            foreach ($student['q'] ?? [] as $qIndex => $qValue) {
                $row[5 + $qIndex] = $qValue;
            }

            $row[12] = $student['penalty'] ?? null;
            $row[13] = $student['moderation'] ?? null;
            $row[19] = $student['ca'] ?? null;

            $rows[] = $row;
        }

        // A real sheet pre-builds hundreds of blank template rows beyond the
        // real class list — reproduce that so the importer proves it skips them.
        for ($i = 0; $i < 5; $i++) {
            $rows[] = array_fill(0, 21, null);
        }

        return $this->realXlsx($rows);
    }

    public function test_index_returns_sessions_for_the_scores_target_selector(): void
    {
        $admin = User::factory()->hod()->create();
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $response = $this->actingAs($admin)->get(route('admin.upload.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Upload/Index')
            ->has('sessions', 1)
        );
    }

    public function test_preview_stores_the_file_and_reports_row_count_and_column_map(): void
    {
        Storage::fake('local');
        $admin = User::factory()->hod()->create();

        $file = $this->realXlsx([
            ['Mat No', 'Full name', 'DOB', 'State', 'Marital status', 'Mode'],
            ['U2022/0001', 'OKORO, Chidera Faith', '2003-01-08', 'Rivers', 'Single', 'Full time'],
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.upload.preview'), [
            'type' => 'students',
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonPath('rowCount', 1);
        $response->assertJsonPath('columnMap.0', 'A → Mat No');
        Storage::disk('local')->assertExists('imports/tmp/'.$response->json('token'));
    }

    public function test_process_runs_the_student_importer_and_deletes_the_temp_file(): void
    {
        Storage::fake('local');
        $admin = User::factory()->hod()->create();
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $file = $this->realXlsx([
            ['Mat No', 'Full name', 'DOB', 'State', 'Marital status', 'Mode'],
            ['U2022/0001', 'OKORO, Chidera Faith', '2003-01-08', 'Rivers', 'Single', 'Full time'],
        ]);

        $preview = $this->actingAs($admin)->postJson(route('admin.upload.preview'), [
            'type' => 'students',
            'file' => $file,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.upload.process'), [
            'type' => 'students',
            'token' => $preview->json('token'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('added', 1);
        $this->assertSame(1, Student::count());
        Storage::disk('local')->assertMissing('imports/tmp/'.$preview->json('token'));
    }

    public function test_process_for_scores_targets_the_selected_session_level_and_semester(): void
    {
        Storage::fake('local');
        $admin = User::factory()->hod()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);
        $student = Student::create(['mat_no' => 'U2022/0001', 'entry_year' => 2022, 'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime]);
        StudentEnrollment::create(['student_id' => $student->id, 'academic_session_id' => $session->id, 'level' => 400, 'mode_of_study' => ModeOfStudy::FullTime]);

        $file = $this->realXlsx([
            ['Mat No', 'Name', 'CA', 'Exam'],
            ['U2022/0001', 'OKAFOR, Adaeze', 25, 45],
        ]);

        $preview = $this->actingAs($admin)->postJson(route('admin.upload.preview'), [
            'type' => 'scores',
            'file' => $file,
            'level' => 400,
            'semester' => 1,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.upload.process'), [
            'type' => 'scores',
            'token' => $preview->json('token'),
            'session_id' => $session->id,
            'level' => 400,
            'semester' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonPath('added', 1);

        $score = \App\Models\Score::where('student_id', $student->id)->where('course_id', $course->id)->sole();
        $this->assertSame(25, $score->ca);
        $this->assertSame(45, $score->exam);
    }

    public function test_process_with_an_expired_token_returns_404(): void
    {
        $admin = User::factory()->hod()->create();

        $this->actingAs($admin)->postJson(route('admin.upload.process'), [
            'type' => 'students',
            'token' => 'does-not-exist.xlsx',
        ])->assertNotFound();
    }

    public function test_lecturer_cannot_access_upload_routes(): void
    {
        $lecturer = User::factory()->lecturer()->create();

        $this->actingAs($lecturer)->get(route('admin.upload.index'))->assertForbidden();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sampleTypes(): array
    {
        return [
            'students' => ['students'],
            'lecturers' => ['lecturers'],
            'courses' => ['courses'],
            'scores' => ['scores'],
            'lecturer_sheet' => ['lecturer_sheet'],
        ];
    }

    #[DataProvider('sampleTypes')]
    public function test_sample_downloads_the_real_template_for_each_upload_type(string $type): void
    {
        $admin = User::factory()->hod()->create();

        $this->actingAs($admin)
            ->get(route('admin.upload.sample', $type))
            ->assertOk()
            ->assertDownload();
    }

    public function test_sample_rejects_an_unknown_type(): void
    {
        $admin = User::factory()->hod()->create();

        $this->actingAs($admin)
            ->get(route('admin.upload.sample', 'not-a-real-type'))
            ->assertNotFound();
    }

    public function test_lecturer_cannot_download_upload_samples(): void
    {
        $lecturer = User::factory()->lecturer()->create();

        $this->actingAs($lecturer)
            ->get(route('admin.upload.sample', 'students'))
            ->assertForbidden();
    }

    public function test_lecturer_sheet_preview_detects_course_and_session_and_ignores_blank_template_rows(): void
    {
        Storage::fake('local');
        $admin = User::factory()->hod()->create();
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);

        $file = $this->lecturerSheetXlsx('CSC 401.1', '2025/2026', [
            ['mat_no' => 'U2022/0001', 'q' => [10, 7, 5], 'ca' => 18],
            ['mat_no' => 'U2022/0002', 'q' => [25, 17, 10], 'ca' => 25],
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.upload.preview'), [
            'type' => 'lecturer_sheet',
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonPath('rowCount', 2);
        $response->assertJsonPath('detectedCourse.code', 'CSC 401');
        $response->assertJsonPath('detectedCourse.semester', 'First');
        $response->assertJsonPath('detectedSession.name', '2025/2026');
    }

    public function test_lecturer_sheet_preview_errors_when_course_is_not_found(): void
    {
        Storage::fake('local');
        $admin = User::factory()->hod()->create();
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);

        $file = $this->lecturerSheetXlsx('CSC 999.1', '2025/2026', [
            ['mat_no' => 'U2022/0001', 'q' => [10], 'ca' => 18],
        ]);

        $this->actingAs($admin)->postJson(route('admin.upload.preview'), [
            'type' => 'lecturer_sheet',
            'file' => $file,
        ])->assertUnprocessable();
    }

    public function test_lecturer_sheet_preview_errors_when_session_is_not_found(): void
    {
        Storage::fake('local');
        $admin = User::factory()->hod()->create();
        Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);

        $file = $this->lecturerSheetXlsx('CSC 401.1', '2099/2100', [
            ['mat_no' => 'U2022/0001', 'q' => [10], 'ca' => 18],
        ]);

        $this->actingAs($admin)->postJson(route('admin.upload.preview'), [
            'type' => 'lecturer_sheet',
            'file' => $file,
        ])->assertUnprocessable();
    }

    public function test_lecturer_sheet_process_saves_scores_folding_in_moderation_and_penalty(): void
    {
        Storage::fake('local');
        $admin = User::factory()->hod()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);

        $moderated = Student::create(['mat_no' => 'U2022/0001', 'entry_year' => 2022, 'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime]);
        $plain = Student::create(['mat_no' => 'U2022/0002', 'entry_year' => 2022, 'last_name' => 'Musa', 'first_name' => 'Ibrahim', 'mode_of_study' => ModeOfStudy::FullTime]);
        $unknownMatNo = 'U2022/9999';

        foreach ([$moderated, $plain] as $student) {
            StudentEnrollment::create(['student_id' => $student->id, 'academic_session_id' => $session->id, 'level' => 400, 'mode_of_study' => ModeOfStudy::FullTime]);
        }

        $file = $this->lecturerSheetXlsx('CSC 401.1', '2025/2026', [
            ['mat_no' => 'U2022/0001', 'q' => [10, 7, 5], 'moderation' => 2, 'ca' => 18],
            ['mat_no' => 'U2022/0002', 'q' => [25, 17, 10], 'ca' => 25],
            ['mat_no' => $unknownMatNo, 'q' => [5, 5, 5], 'ca' => 10],
        ]);

        $preview = $this->actingAs($admin)->postJson(route('admin.upload.preview'), [
            'type' => 'lecturer_sheet',
            'file' => $file,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.upload.process'), [
            'type' => 'lecturer_sheet',
            'token' => $preview->json('token'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('added', 2);
        $response->assertJsonPath('errors.0.message', "No student found with matriculation number {$unknownMatNo}.");
        $response->assertJsonPath('warnings.0.message', 'U2022/0001: moderation/penalty adjustment of 2 applied to the exam mark.');

        $moderatedScore = \App\Models\Score::where('student_id', $moderated->id)->where('course_id', $course->id)->sole();
        $this->assertSame(18, $moderatedScore->ca);
        $this->assertSame(24, $moderatedScore->exam); // 10+7+5 + 2 moderation

        $plainScore = \App\Models\Score::where('student_id', $plain->id)->where('course_id', $course->id)->sole();
        $this->assertSame(25, $plainScore->ca);
        $this->assertSame(52, $plainScore->exam); // 25+17+10
    }

    public function test_lecturer_sheet_process_uses_the_later_row_when_a_student_appears_twice(): void
    {
        Storage::fake('local');
        $admin = User::factory()->hod()->create();
        $session = AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        $course = Course::create(['code' => 'CSC 401', 'title' => 'Algorithms', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);

        $student = Student::create(['mat_no' => 'U2022/0001', 'entry_year' => 2022, 'last_name' => 'Okafor', 'first_name' => 'Adaeze', 'mode_of_study' => ModeOfStudy::FullTime]);
        StudentEnrollment::create(['student_id' => $student->id, 'academic_session_id' => $session->id, 'level' => 400, 'mode_of_study' => ModeOfStudy::FullTime]);

        $file = $this->lecturerSheetXlsx('CSC 401.1', '2025/2026', [
            ['mat_no' => 'U2022/0001', 'q' => [10], 'ca' => 18], // first (stale) occurrence: only Q1
            ['mat_no' => 'U2022/0001', 'q' => [15, 7], 'ca' => 20], // corrected occurrence: Q1 changed, Q2 added
        ]);

        $preview = $this->actingAs($admin)->postJson(route('admin.upload.preview'), [
            'type' => 'lecturer_sheet',
            'file' => $file,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.upload.process'), [
            'type' => 'lecturer_sheet',
            'token' => $preview->json('token'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('added', 1);
        $response->assertJsonPath('warnings.0.message', "U2022/0001 also appears in row 9 — using this row's values instead.");

        $score = \App\Models\Score::where('student_id', $student->id)->where('course_id', $course->id)->sole();
        $this->assertSame(20, $score->ca);
        $this->assertSame(22, $score->exam); // 15+7 from the later (row 10) occurrence, not 10 from row 9
    }

    public function test_the_shipped_lecturer_sheet_template_actually_imports_through_the_real_flow(): void
    {
        Storage::fake('local');
        $admin = User::factory()->hod()->create();
        AcademicSession::create(['name' => '2025/2026', 'is_current' => true]);
        Course::create(['code' => 'CSC 401', 'title' => 'Operating Systems', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);

        $templatePath = storage_path('app/private/samples/lecturer_sheet.xlsx');
        $file = new UploadedFile($templatePath, 'lecturer_sheet.xlsx', null, null, true);

        $preview = $this->actingAs($admin)->postJson(route('admin.upload.preview'), [
            'type' => 'lecturer_sheet',
            'file' => $file,
        ]);

        $preview->assertOk();
        $preview->assertJsonPath('detectedCourse.code', 'CSC 401');
        $preview->assertJsonPath('detectedCourse.semester', 'First');
        $preview->assertJsonPath('detectedSession.name', '2025/2026');
        $preview->assertJsonPath('rowCount', 8);
    }
}

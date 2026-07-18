<?php

namespace App\Services\Imports;

use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use App\Services\Academic\ScoreService;

class CourseMarkSheetImporter
{
    /**
     * Positional (0-based) row/column indexes matching the university's own
     * "COURSE EXAMINATION MARK SHEET" template — the same layout every
     * lecturer already uses to send scores to the exam officer, header
     * metadata and all. Column B carries MAT NO; F–L are up to seven raw
     * exam sub-question scores (Q1–Q7); M is a Penalty deduction, N a
     * Moderation addition; T is the CA (30%) mark, manually entered.
     *
     * TOTAL SCORE/GRADE/EXAM(70%) are formulas in the source file — never
     * read here. This app recomputes ca/exam/grade itself from the raw
     * inputs, same as every other entry path (see ScoreService::saveScores).
     */
    private const COURSE_CODE_ROW = 3;

    private const SESSION_ROW = 2;

    private const FIRST_DATA_ROW = 8;

    private const MAT_NO_COL = 1;

    private const QUESTION_COLS = [5, 6, 7, 8, 9, 10, 11];

    private const PENALTY_COL = 12;

    private const MODERATION_COL = 13;

    private const CA_COL = 19;

    private const SESSION_COL = 7;

    public function __construct(private ScoreService $scoreService) {}

    /**
     * Resolve the course/session this sheet is for and count how many real
     * (non-template-padding) student rows it has, without writing anything —
     * used to show the admin what was detected before they commit.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{error: ?string, course: ?Course, session: ?AcademicSession, rowCount: int}
     */
    public function preview(array $rows): array
    {
        ['course' => $course, 'session' => $session, 'error' => $error] = $this->resolveTarget($rows);

        if ($error !== null) {
            return ['error' => $error, 'course' => null, 'session' => null, 'rowCount' => 0];
        }

        $rowCount = 0;

        foreach ($rows as $index => $row) {
            if ($index >= self::FIRST_DATA_ROW && trim((string) ($row[self::MAT_NO_COL] ?? '')) !== '') {
                $rowCount++;
            }
        }

        return ['error' => null, 'course' => $course, 'session' => $session, 'rowCount' => $rowCount];
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function import(array $rows, User $actor): ImportResult
    {
        ['course' => $course, 'session' => $session, 'error' => $error] = $this->resolveTarget($rows);

        if ($error !== null) {
            return new ImportResult(0, 0, errors: [['row' => 0, 'message' => $error]]);
        }

        $this->scoreService->rosterFor($course, $session);

        $entries = [];
        $lastRowByStudent = [];
        $errors = [];
        $warnings = [];

        foreach ($rows as $index => $row) {
            if ($index < self::FIRST_DATA_ROW) {
                continue;
            }

            $matNo = strtoupper(trim((string) ($row[self::MAT_NO_COL] ?? '')));

            if ($matNo === '') {
                continue; // blank template padding row — the sheet pre-builds hundreds of these
            }

            $student = Student::where('mat_no', $matNo)->first();

            if (! $student) {
                $errors[] = ['row' => $index + 1, 'message' => "No student found with matriculation number {$matNo}."];

                continue;
            }

            $examRaw = array_sum(array_map(fn ($col) => (int) ($row[$col] ?? 0), self::QUESTION_COLS));
            $penalty = (int) ($row[self::PENALTY_COL] ?? 0);
            $moderation = (int) ($row[self::MODERATION_COL] ?? 0);
            $ca = (int) ($row[self::CA_COL] ?? 0);

            if ($penalty !== 0 || $moderation !== 0) {
                $warnings[] = [
                    'row' => $index + 1,
                    'message' => "{$matNo}: moderation/penalty adjustment of ".($moderation - $penalty).' applied to the exam mark.',
                ];
            }

            if (isset($lastRowByStudent[$student->id])) {
                $warnings[] = [
                    'row' => $index + 1,
                    'message' => "{$matNo} also appears in row {$lastRowByStudent[$student->id]} — using this row's values instead.",
                ];
            }

            $lastRowByStudent[$student->id] = $index + 1;

            $entries[$student->id] = [
                'ca' => $ca,
                'exam' => $examRaw + $moderation - $penalty,
            ];
        }

        if (! empty($entries)) {
            $this->scoreService->saveScores($course, $session, $entries, $actor);
        }

        return new ImportResult(count($entries), 0, $errors, $warnings);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{course: ?Course, session: ?AcademicSession, error: ?string}
     */
    private function resolveTarget(array $rows): array
    {
        $courseCodeRaw = $this->afterColon($rows[self::COURSE_CODE_ROW][0] ?? null);

        if (! $courseCodeRaw) {
            return ['course' => null, 'session' => null, 'error' => 'Could not find a "COURSE CODE" header in this file — make sure this is the official lecturer mark sheet template.'];
        }

        [$code, $semesterNumber] = $this->splitCourseCode($courseCodeRaw);
        $semester = $semesterNumber ? Semester::tryFrom($semesterNumber) : null;

        $course = Course::query()
            ->where('code', $code)
            ->when($semester, fn ($query) => $query->where('semester', $semester))
            ->first();

        if (! $course) {
            $semesterHint = $semester ? " ({$semester->label()} semester)" : '';

            return ['course' => null, 'session' => null, 'error' => "No course found matching \"{$code}\"{$semesterHint}. Add it under Courses first, or check the course code."];
        }

        $sessionRaw = $this->afterColon($rows[self::SESSION_ROW][self::SESSION_COL] ?? null);

        if (! $sessionRaw) {
            return ['course' => null, 'session' => null, 'error' => 'Could not find a "SESSION" header in this file.'];
        }

        $session = AcademicSession::where('name', $sessionRaw)->first();

        if (! $session) {
            return ['course' => null, 'session' => null, 'error' => "No academic session found matching \"{$sessionRaw}\". Add it under Settings first."];
        }

        return ['course' => $course, 'session' => $session, 'error' => null];
    }

    private function afterColon(mixed $value): ?string
    {
        if (! is_string($value) || ! str_contains($value, ':')) {
            return null;
        }

        $after = trim(substr($value, strpos($value, ':') + 1));

        return $after !== '' ? $after : null;
    }

    /**
     * @return array{0: string, 1: ?int} [code, semesterNumber]
     */
    private function splitCourseCode(string $raw): array
    {
        if (preg_match('/^(.*)\.(\d)$/', trim($raw), $matches)) {
            return [trim($matches[1]), (int) $matches[2]];
        }

        return [trim($raw), null];
    }
}

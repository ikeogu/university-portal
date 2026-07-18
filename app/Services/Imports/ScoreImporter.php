<?php

namespace App\Services\Imports;

use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\User;
use App\Services\Academic\ScoreService;
use Illuminate\Support\Collection;

class ScoreImporter
{
    public function __construct(private ScoreService $scoreService) {}

    /**
     * Column mapping: A Mat No, B Name (display-only cross-check, not
     * authoritative), then a CA/Exam column pair per course in $courses, in
     * that exact order. Unlike the other importers, a repeated mat_no is an
     * upsert (a corrected re-upload), never a skip — the department's own
     * workflow is "fix a few marks, re-upload the whole sheet," and every
     * write still goes through ScoreService, so it's clamped and audited
     * exactly like a manual save.
     *
     * @param  iterable<int, array<int, mixed>>  $rows
     * @param  Collection<int, \App\Models\Course>  $courses  ordered, defines which column pair is which course
     */
    public function import(iterable $rows, AcademicSession $session, Collection $courses, User $actor): ImportResult
    {
        $courses = $courses->values();

        foreach ($courses as $course) {
            $this->scoreService->rosterFor($course, $session);
        }

        $entriesByCourse = [];
        $added = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $matNo = strtoupper(trim((string) ($row[0] ?? '')));

            if ($matNo === '') {
                $errors[] = ['row' => $index + 2, 'message' => 'Missing matriculation number.'];

                continue;
            }

            $student = Student::where('mat_no', $matNo)->first();

            if (! $student) {
                $errors[] = ['row' => $index + 2, 'message' => "No student found with matriculation number {$matNo} — scores are never used to onboard a new student."];

                continue;
            }

            foreach ($courses as $courseIndex => $course) {
                $caColumn = 2 + $courseIndex * 2;
                $examColumn = 3 + $courseIndex * 2;

                if (! array_key_exists($caColumn, $row) && ! array_key_exists($examColumn, $row)) {
                    continue;
                }

                $entriesByCourse[$course->id][$student->id] = [
                    'ca' => (int) ($row[$caColumn] ?? 0),
                    'exam' => (int) ($row[$examColumn] ?? 0),
                ];
            }

            $added++;
        }

        foreach ($courses as $course) {
            if (! empty($entriesByCourse[$course->id])) {
                $this->scoreService->saveScores($course, $session, $entriesByCourse[$course->id], $actor);
            }
        }

        return new ImportResult($added, 0, $errors);
    }
}

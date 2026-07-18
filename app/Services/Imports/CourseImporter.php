<?php

namespace App\Services\Imports;

use App\Enums\Semester;
use App\Models\Course;
use Illuminate\Support\Str;

class CourseImporter
{
    /**
     * Column mapping: A Code, B Title, C Credit units, D Semester, E Level.
     * The Level column is an addition beyond the README's literal A-D
     * mapping, matching the same Level field added to the manual "Add a
     * course" form — the real system supports multiple levels, so every
     * course must declare one; defaults to 100 if the column is omitted.
     *
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function import(iterable $rows): ImportResult
    {
        $seen = Course::query()->pluck('code')
            ->map(fn (string $code) => Str::upper($code))
            ->flip();

        $added = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $code = Str::upper(trim((string) ($row[0] ?? '')));
            $title = trim((string) ($row[1] ?? ''));

            if ($code === '' || $title === '') {
                $errors[] = ['row' => $index + 2, 'message' => 'Missing course code or title.'];

                continue;
            }

            if ($seen->has($code)) {
                $skipped++;

                continue;
            }

            $semesterValue = trim((string) ($row[3] ?? '1'));
            $semester = Str::lower($semesterValue) === 'second' || $semesterValue === '2'
                ? Semester::Second
                : Semester::First;

            Course::create([
                'code' => $code,
                'title' => $title,
                'credit_units' => (int) ($row[2] ?? 1),
                'semester' => $semester,
                'level' => (int) ($row[4] ?? 100),
            ]);

            $seen->put($code, true);
            $added++;
        }

        return new ImportResult($added, $skipped, $errors);
    }
}

<?php

namespace App\Services\Imports;

use App\Enums\CourseCategory;
use App\Enums\Semester;
use App\Models\Course;
use Illuminate\Support\Str;

class CourseImporter
{
    /**
     * Column mapping: A Code, B Title, C Credit units, D Semester, E Level,
     * F Category (Required/Core/Elective, optional — defaults to Core), G
     * Elective group, H Choose how many (both optional, only meaningful
     * when F is Elective; every alternative in the same "choose N of M"
     * decision must share the identical group label in column G).
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

            $category = $this->matchCategory($row[5] ?? null);
            $electiveGroup = trim((string) ($row[6] ?? ''));
            $chooseCount = trim((string) ($row[7] ?? ''));

            Course::create([
                'code' => $code,
                'title' => $title,
                'credit_units' => (int) ($row[2] ?? 1),
                'semester' => $semester,
                'level' => (int) ($row[4] ?? 100),
                'category' => $category,
                'elective_group' => $category === CourseCategory::Elective && $electiveGroup !== '' ? $electiveGroup : null,
                'choose_count' => $category === CourseCategory::Elective && $chooseCount !== '' ? (int) $chooseCount : null,
            ]);

            $seen->put($code, true);
            $added++;
        }

        return new ImportResult($added, $skipped, $errors);
    }

    private function matchCategory(mixed $value): CourseCategory
    {
        $value = trim((string) $value);

        if ($value === '') {
            return CourseCategory::Core;
        }

        foreach (CourseCategory::cases() as $case) {
            if (Str::lower($case->label()) === Str::lower($value)) {
                return $case;
            }
        }

        return CourseCategory::Core;
    }
}

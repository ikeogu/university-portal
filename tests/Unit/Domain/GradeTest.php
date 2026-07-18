<?php

namespace Tests\Unit\Domain;

use App\Domain\Grading\Grade;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GradeTest extends TestCase
{
    /**
     * Shared with resources/js/lib/__tests__/grading.test.js via
     * tests/Fixtures/grade_boundaries.json, so the PHP source of truth and
     * its client-side live-feedback mirror are tested against identical
     * boundary data and can't silently drift apart.
     */
    public static function markProvider(): array
    {
        $fixture = json_decode(
            file_get_contents(__DIR__.'/../../Fixtures/grade_boundaries.json'),
            true,
        );

        return collect($fixture)
            ->mapWithKeys(fn (array $row) => [
                $row['case'] => [$row['mark'], Grade::from($row['grade']), $row['point']],
            ])
            ->all();
    }

    #[DataProvider('markProvider')]
    public function test_from_mark_resolves_the_correct_grade_and_point(int $mark, Grade $expectedGrade, int $expectedPoint): void
    {
        $grade = Grade::fromMark($mark);

        $this->assertSame($expectedGrade, $grade);
        $this->assertSame($expectedPoint, $grade->gradePoint());
    }
}

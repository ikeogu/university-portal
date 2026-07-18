<?php

namespace Tests\Unit\Services\Academic;

use App\Domain\Grading\ScoreRow;
use App\Services\Academic\CumulativeResultCalculator;
use App\Services\Academic\SemesterResultCalculator;
use PHPUnit\Framework\TestCase;

class CumulativeResultCalculatorTest extends TestCase
{
    private function calculator(): CumulativeResultCalculator
    {
        return new CumulativeResultCalculator(new SemesterResultCalculator);
    }

    /** @param array<int, array{int, int}> $tuples of [creditUnits, mark] */
    private function rows(array $tuples): array
    {
        return array_map(
            fn (array $t) => new ScoreRow(ca: 0, exam: $t[1], creditUnitsAtEntry: $t[0]),
            $tuples,
        );
    }

    public function test_empty_input_yields_zero_without_dividing_by_zero(): void
    {
        $result = $this->calculator()->calculate([]);

        $this->assertSame(0, $result['tcu']);
        $this->assertSame(0, $result['tqp']);
        $this->assertSame(0.0, $result['cgpa']);
        $this->assertSame([], $result['byLevel']);
    }

    public function test_single_level_matches_the_semester_calculator_directly(): void
    {
        $rows = $this->rows([[3, 70], [3, 60]]); // qp: 5*3=15, 4*3=12 -> tcu6 tqp27

        $result = $this->calculator()->calculate([100 => $rows]);

        $this->assertSame(['tcu' => 6, 'tqp' => 27, 'gpa' => 4.5], $result['byLevel'][100]);
        $this->assertSame(6, $result['tcu']);
        $this->assertSame(27, $result['tqp']);
        $this->assertSame(4.5, $result['cgpa']);
    }

    /**
     * Real-world regression fixture: the FULL 4-year University of Port
     * Harcourt sample statement referenced by the project README. Every
     * (creditUnits, mark) pair below is transcribed directly from that PDF.
     * Three courses (LCS 102, LCS 206, LCS 412) are the sample's own
     * "not used in computation" rows (marked with no compulsory/required/
     * elective/waived symbol in the source) and are deliberately omitted —
     * our system doesn't model that per-course inclusion flag, so leaving
     * the row out entirely reproduces the sample's published per-level
     * totals without inventing functionality that isn't in scope.
     *
     * Expected, taken directly from the sample's own final-page summary:
     * Final TCU = 146, Final TQP = 700, Final CGPA = 4.79 (4.7945...).
     */
    public function test_matches_the_real_reference_statements_full_four_year_history(): void
    {
        $level100 = $this->rows([
            [3, 64], [3, 73], [2, 78], [2, 76], [3, 77], [3, 77], [3, 70], // Year 1, Sem I
            [3, 66], [3, 62], [2, 92], [3, 87], [3, 76], [3, 72], // Year 1, Sem II (LCS 102 omitted)
        ]);

        $level200 = $this->rows([
            [3, 68], [3, 93], [3, 80], [3, 73], [3, 70], [3, 80], // Year 2, Sem I (LCS 206 omitted)
            [1, 71], [2, 64], [3, 82], [3, 65], [3, 83], [3, 72], [3, 80], [3, 75], // Year 2, Sem II
        ]);

        $level300 = $this->rows([
            [3, 80], [3, 87], [3, 84], [3, 79], [3, 79], [3, 75], // Year 3, Sem I
            [2, 58], [3, 85], [3, 72], [3, 73], [3, 76], [3, 81], [3, 67], // Year 3, Sem II
        ]);

        $level400 = $this->rows([
            [3, 80], [3, 64], [3, 71], [3, 82], [3, 76], // Year 4, Sem I (LCS 412 omitted)
            [3, 85], [3, 73], [3, 89], [3, 69], [6, 70], // Year 4, Sem II
        ]);

        $result = $this->calculator()->calculate([
            100 => $level100,
            200 => $level200,
            300 => $level300,
            400 => $level400,
        ]);

        $this->assertSame(36, $result['byLevel'][100]['tcu']);
        $this->assertSame(171, $result['byLevel'][100]['tqp']);
        $this->assertSame(39, $result['byLevel'][200]['tcu']);
        $this->assertSame(187, $result['byLevel'][200]['tqp']);
        $this->assertSame(38, $result['byLevel'][300]['tcu']);
        $this->assertSame(183, $result['byLevel'][300]['tqp']);
        $this->assertSame(33, $result['byLevel'][400]['tcu']);
        $this->assertSame(159, $result['byLevel'][400]['tqp']);

        $this->assertSame(146, $result['tcu']);
        $this->assertSame(700, $result['tqp']);
        $this->assertEqualsWithDelta(4.7945205479452055, $result['cgpa'], 0.0000001);
        $this->assertSame('4.79', number_format($result['cgpa'], 2));
    }
}

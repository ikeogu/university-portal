<?php

namespace Tests\Unit\Services\Academic;

use App\Domain\Grading\ScoreRow;
use App\Services\Academic\SemesterResultCalculator;
use PHPUnit\Framework\TestCase;

class SemesterResultCalculatorTest extends TestCase
{
    public function test_empty_rows_yield_zero_without_dividing_by_zero(): void
    {
        $result = (new SemesterResultCalculator)->calculate([]);

        $this->assertSame(0, $result['tcu']);
        $this->assertSame(0, $result['tqp']);
        $this->assertSame(0.0, $result['gpa']);
    }

    public function test_unscored_rows_are_excluded_from_totals(): void
    {
        $rows = [
            new ScoreRow(ca: null, exam: null, creditUnitsAtEntry: 3),
            new ScoreRow(ca: 20, exam: null, creditUnitsAtEntry: 3),
        ];

        $result = (new SemesterResultCalculator)->calculate($rows);

        $this->assertSame(0, $result['tcu']);
        $this->assertSame(0, $result['tqp']);
        $this->assertSame(0.0, $result['gpa']);
    }

    /**
     * Real-world regression fixture: Semester I of the University of Port
     * Harcourt sample statement referenced by the project README (7 courses,
     * TCU 19, TQP 92, GPA 4.84). CA/exam split is fabricated (ca=0) since the
     * source document only shows the combined mark, but the totals below are
     * the sample's actual published figures.
     */
    public function test_matches_the_real_reference_statement_semester_one(): void
    {
        $rows = [
            new ScoreRow(ca: 0, exam: 64, creditUnitsAtEntry: 3), // FAD 100 -> B -> gp4 -> qp12
            new ScoreRow(ca: 0, exam: 73, creditUnitsAtEntry: 3), // FLL 111 -> A -> gp5 -> qp15
            new ScoreRow(ca: 0, exam: 78, creditUnitsAtEntry: 2), // GES 103 -> A -> gp5 -> qp10
            new ScoreRow(ca: 0, exam: 76, creditUnitsAtEntry: 2), // GES 104 -> A -> gp5 -> qp10
            new ScoreRow(ca: 0, exam: 77, creditUnitsAtEntry: 3), // LCS 100 -> A -> gp5 -> qp15
            new ScoreRow(ca: 0, exam: 77, creditUnitsAtEntry: 3), // LCS 111 -> A -> gp5 -> qp15
            new ScoreRow(ca: 0, exam: 70, creditUnitsAtEntry: 3), // LCS 112 -> A -> gp5 -> qp15
        ];

        $result = (new SemesterResultCalculator)->calculate($rows);

        $this->assertSame(19, $result['tcu']);
        $this->assertSame(92, $result['tqp']);
        $this->assertEqualsWithDelta(4.8421052631578947, $result['gpa'], 0.0000001);
    }

    public function test_partially_scored_semester_only_totals_the_scored_rows(): void
    {
        $rows = [
            new ScoreRow(ca: 25, exam: 45, creditUnitsAtEntry: 3), // mark 70 -> A -> gp5 -> qp15
            new ScoreRow(ca: null, exam: null, creditUnitsAtEntry: 4), // not yet entered
        ];

        $result = (new SemesterResultCalculator)->calculate($rows);

        $this->assertSame(3, $result['tcu']);
        $this->assertSame(15, $result['tqp']);
        $this->assertSame(5.0, $result['gpa']);
    }
}

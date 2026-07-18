<?php

namespace App\Services\Academic;

use App\Domain\Grading\ScoreRow;

class CumulativeResultCalculator
{
    public function __construct(private SemesterResultCalculator $semesterCalculator) {}

    /**
     * @param  array<int, ScoreRow[]>  $rowsByLevel  every scored/unscored row the
     *         student has, grouped by academic level (100, 200, ...) — this is
     *         the real replacement for the prototype's fabricated yr()/fin()
     *         history: CGPA is always a true aggregate of rows actually entered.
     * @return array{byLevel: array<int, array{tcu:int,tqp:int,gpa:float}>, tcu:int, tqp:int, cgpa:float}
     */
    public function calculate(array $rowsByLevel): array
    {
        $byLevel = [];
        $allRows = [];

        foreach ($rowsByLevel as $level => $rows) {
            $byLevel[$level] = $this->semesterCalculator->calculate($rows);
            array_push($allRows, ...$rows);
        }

        $overall = $this->semesterCalculator->calculate($allRows);

        return [
            'byLevel' => $byLevel,
            'tcu' => $overall['tcu'],
            'tqp' => $overall['tqp'],
            'cgpa' => $overall['gpa'],
        ];
    }
}

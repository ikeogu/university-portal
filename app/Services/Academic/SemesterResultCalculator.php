<?php

namespace App\Services\Academic;

use App\Domain\Grading\ScoreRow;

class SemesterResultCalculator
{
    /**
     * @param  iterable<ScoreRow>  $rows
     * @return array{tcu: int, tqp: int, gpa: float}
     */
    public function calculate(iterable $rows): array
    {
        $scored = [];

        foreach ($rows as $row) {
            if ($row->isScored()) {
                $scored[] = $row;
            }
        }

        $tcu = array_sum(array_map(fn (ScoreRow $row) => $row->creditUnitsAtEntry, $scored));
        $tqp = array_sum(array_map(fn (ScoreRow $row) => $row->qualityPoints(), $scored));

        return [
            'tcu' => $tcu,
            'tqp' => $tqp,
            'gpa' => $tcu > 0 ? (float) $tqp / $tcu : 0.0,
        ];
    }
}

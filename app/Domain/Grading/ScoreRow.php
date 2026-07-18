<?php

namespace App\Domain\Grading;

readonly class ScoreRow
{
    public function __construct(
        public ?int $ca,
        public ?int $exam,
        public int $creditUnitsAtEntry,
    ) {}

    /**
     * A row only counts towards GPA/CGPA once both components are entered.
     * Null means "not yet scored" — never treat it as a mark of zero.
     */
    public function isScored(): bool
    {
        return $this->ca !== null && $this->exam !== null;
    }

    public function mark(): int
    {
        return ($this->ca ?? 0) + ($this->exam ?? 0);
    }

    public function grade(): Grade
    {
        return Grade::fromMark($this->mark());
    }

    public function gradePoint(): int
    {
        return $this->grade()->gradePoint();
    }

    public function qualityPoints(): int
    {
        return $this->gradePoint() * $this->creditUnitsAtEntry;
    }
}

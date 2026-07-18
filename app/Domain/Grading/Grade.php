<?php

namespace App\Domain\Grading;

enum Grade: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';
    case F = 'F';

    public static function fromMark(int $mark): self
    {
        return match (true) {
            $mark >= 70 => self::A,
            $mark >= 60 => self::B,
            $mark >= 50 => self::C,
            $mark >= 45 => self::D,
            $mark >= 40 => self::E,
            default => self::F,
        };
    }

    public function gradePoint(): int
    {
        return match ($this) {
            self::A => 5,
            self::B => 4,
            self::C => 3,
            self::D => 2,
            self::E => 1,
            self::F => 0,
        };
    }
}

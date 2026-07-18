<?php

namespace App\Domain\Grading;

enum ClassOfDegree: string
{
    case FirstClass = 'first_class';
    case SecondUpper = 'second_upper';
    case SecondLower = 'second_lower';
    case Third = 'third';
    case Pass = 'pass';
    case Fail = 'fail';

    public static function fromCgpa(float $cgpa): self
    {
        return match (true) {
            $cgpa >= 4.50 => self::FirstClass,
            $cgpa >= 3.50 => self::SecondUpper,
            $cgpa >= 2.40 => self::SecondLower,
            $cgpa >= 1.50 => self::Third,
            $cgpa >= 1.00 => self::Pass,
            default => self::Fail,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::FirstClass => 'First Class',
            self::SecondUpper => '2nd Class Upper',
            self::SecondLower => '2nd Class Lower',
            self::Third => '3rd Class',
            self::Pass => 'Pass',
            self::Fail => 'Fail',
        };
    }

    public function abbreviation(): string
    {
        return match ($this) {
            self::FirstClass => '1st',
            self::SecondUpper => '2:1',
            self::SecondLower => '2:2',
            self::Third => '3rd',
            self::Pass => 'Pass',
            self::Fail => 'Fail',
        };
    }
}

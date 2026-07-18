<?php

namespace App\Enums;

enum CourseCategory: string
{
    case Required = 'required';
    case Core = 'core';
    case Elective = 'elective';

    public function label(): string
    {
        return match ($this) {
            self::Required => 'Required',
            self::Core => 'Core',
            self::Elective => 'Elective',
        };
    }

    /**
     * Required and Core courses apply to every actively-enrolled student at
     * that level automatically. Elective courses require a CourseRegistration
     * row — see ScoreService::rosterFor().
     */
    public function isElective(): bool
    {
        return $this === self::Elective;
    }
}

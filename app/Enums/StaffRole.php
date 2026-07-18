<?php

namespace App\Enums;

enum StaffRole: string
{
    case Lecturer = 'lecturer';
    case ExamOfficer = 'exam_officer';
    case Hod = 'hod';

    public function label(): string
    {
        return match ($this) {
            self::Lecturer => 'Lecturer',
            self::ExamOfficer => 'Exam Officer',
            self::Hod => 'Head of Department',
        };
    }

    /**
     * HoD and Exam Officer are functionally one admin tier — kept as two
     * enum values purely for display labels and audit-log readability.
     */
    public function isAdmin(): bool
    {
        return $this === self::ExamOfficer || $this === self::Hod;
    }
}

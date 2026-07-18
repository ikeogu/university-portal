<?php

namespace App\Services\Academic;

use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\StudentEnrollment;

class EnrollmentService
{
    public const ENTRY_LEVEL = 100;

    /**
     * Enroll a newly onboarded student at the entry level in the current
     * academic session. Returns null if there is no current session to
     * enroll into (the caller should surface this as a validation error).
     */
    public function enrollNewStudent(Student $student): ?StudentEnrollment
    {
        $session = AcademicSession::current();

        if (! $session) {
            return null;
        }

        return StudentEnrollment::create([
            'student_id' => $student->id,
            'academic_session_id' => $session->id,
            'level' => self::ENTRY_LEVEL,
            'mode_of_study' => $student->mode_of_study,
        ]);
    }

    /**
     * Advance every active student enrolled at $fromLevel in $fromSession
     * into a new enrollment one level up in $toSession. Students already
     * enrolled in $toSession (regardless of level) are skipped rather than
     * erroring, matching the app's duplicate-skip convention elsewhere.
     */
    public function advanceCohort(AcademicSession $fromSession, int $fromLevel, AcademicSession $toSession): int
    {
        $enrollments = StudentEnrollment::query()
            ->where('academic_session_id', $fromSession->id)
            ->where('level', $fromLevel)
            ->whereHas('student', fn ($query) => $query->where('is_active', true))
            ->get();

        $advanced = 0;

        foreach ($enrollments as $enrollment) {
            $alreadyEnrolled = StudentEnrollment::query()
                ->where('student_id', $enrollment->student_id)
                ->where('academic_session_id', $toSession->id)
                ->exists();

            if ($alreadyEnrolled) {
                continue;
            }

            StudentEnrollment::create([
                'student_id' => $enrollment->student_id,
                'academic_session_id' => $toSession->id,
                'level' => $fromLevel + 100,
                'mode_of_study' => $enrollment->mode_of_study,
            ]);

            $advanced++;
        }

        return $advanced;
    }
}

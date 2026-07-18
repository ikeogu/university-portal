<?php

namespace App\Policies;

use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\User;

class ScorePolicy
{
    /**
     * True if $user may enter/correct scores for $course in $session: an
     * admin (HoD/Exam Officer) may override any course, otherwise the user
     * must be allocated to teach that specific course in that session.
     */
    public function manageCourse(User $user, Course $course, AcademicSession $session): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return CourseAllocation::query()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('academic_session_id', $session->id)
            ->exists();
    }
}

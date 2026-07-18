<?php

namespace App\Http\Controllers\Public\Concerns;

use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Shared identity gate for every public, session-verified screen (on-screen
 * result, print statement, bio-data self-service) that follows a mat_no +
 * access PIN check. One rolling 20-minute window set at verification time,
 * checked fresh on every request.
 */
trait VerifiesPublicSession
{
    private const SESSION_KEY_STUDENT = 'public_student_id';

    private const SESSION_KEY_VERIFIED_UNTIL = 'public_verified_until';

    /**
     * For screens that assume the student already verified elsewhere (the
     * result checker) and should bounce back there if the session has
     * lapsed.
     */
    protected function verifiedStudentOrRedirect(Request $request): Student|RedirectResponse
    {
        $student = $this->currentlyVerifiedStudent($request);

        if (! $student) {
            $request->session()->forget([self::SESSION_KEY_STUDENT, self::SESSION_KEY_VERIFIED_UNTIL]);

            return redirect()->route('public.check')->withErrors([
                'mat_no' => 'Your session has expired. Please check your result again.',
            ]);
        }

        return $student;
    }

    /**
     * For screens that are their own standalone entry point (a shareable
     * link) and want to show an inline verify-or-continue state instead of
     * bouncing elsewhere.
     */
    protected function currentlyVerifiedStudent(Request $request): ?Student
    {
        $studentId = $request->session()->get(self::SESSION_KEY_STUDENT);
        $verifiedUntil = $request->session()->get(self::SESSION_KEY_VERIFIED_UNTIL);

        if (! $studentId || ! $verifiedUntil || now()->greaterThan($verifiedUntil)) {
            return null;
        }

        return Student::find($studentId);
    }

    /**
     * Verify mat_no + PIN and, on success, start the shared 20-minute
     * session window. Every entry point into the public area (result
     * checker, bio-data self-service) funnels through this one check.
     */
    protected function verifyAndEstablishSession(Request $request, string $matNo, string $pin): ?Student
    {
        $student = Student::where('mat_no', $matNo)->first();

        if (! $student || ! $student->verifyPin($pin)) {
            return null;
        }

        $request->session()->put(self::SESSION_KEY_STUDENT, $student->id);
        $request->session()->put(self::SESSION_KEY_VERIFIED_UNTIL, now()->addMinutes(20));

        return $student;
    }
}

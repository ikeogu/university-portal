<?php

namespace App\Http\Controllers\Public;

use App\Enums\Semester;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\VerifiesPublicSession;
use App\Http\Requests\Public\CheckResultRequest;
use App\Models\Setting;
use App\Services\Academic\StudentResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResultCheckController extends Controller
{
    use VerifiesPublicSession;

    public function create(): Response
    {
        return Inertia::render('Public/Check', [
            'bioDataHref' => Setting::get('bioUpdateOpen', false) ? route('public.bio.edit') : null,
        ]);
    }

    public function store(CheckResultRequest $request): RedirectResponse
    {
        $student = $this->verifyAndEstablishSession($request, $request->validated('mat_no'), $request->validated('pin'));

        // Same generic error whether the mat_no doesn't exist or the PIN is
        // wrong — a distinct "mat_no not found" message would let an
        // attacker enumerate real matriculation numbers one field at a time.
        if (! $student) {
            return back()->withErrors([
                'mat_no' => 'That matriculation number and access PIN do not match our records.',
            ])->withInput('mat_no');
        }

        return redirect()->route('public.result');
    }

    public function result(Request $request, StudentResultService $resultService): Response|RedirectResponse
    {
        $student = $this->verifiedStudentOrRedirect($request);

        if ($student instanceof RedirectResponse) {
            return $student;
        }

        $semester = Semester::from((int) $request->query('semester', Semester::First->value));

        return Inertia::render('Result/Show', [
            ...$resultService->viewProps($student, $request->query('session'), $semester),
            'programmeName' => Setting::get('programme_name', 'B.Sc Computer Science'),
            'backHref' => route('landing'),
            'backLabel' => 'Academic result',
            'resultHref' => route('public.result'),
            'printHref' => route('public.result.print'),
            'bioDataHref' => Setting::get('bioUpdateOpen', false) ? route('public.bio.edit') : null,
        ]);
    }
}

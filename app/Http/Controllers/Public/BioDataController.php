<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\VerifiesPublicSession;
use App\Http\Requests\Public\CheckResultRequest;
use App\Http\Requests\Public\UpdateBioDataRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A standalone, shareable entry point ("update your bio data & photo") that
 * verifies mat_no + access PIN itself rather than assuming the student
 * already checked their result first — the department can hand this link
 * out on its own for photo/bio corrections.
 */
class BioDataController extends Controller
{
    use VerifiesPublicSession;

    public function edit(Request $request): Response
    {
        $student = $this->currentlyVerifiedStudent($request);

        if (! $student) {
            return Inertia::render('Public/BioData', ['verified' => false]);
        }

        if (! Setting::get('bioUpdateOpen', false)) {
            return Inertia::render('Public/BioData', ['verified' => true, 'closed' => true]);
        }

        return Inertia::render('Public/BioData', [
            'verified' => true,
            'closed' => false,
            'student' => [
                'last_name' => $student->last_name,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'dob' => $student->dob?->format('Y-m-d'),
                'state_of_origin' => $student->state_of_origin,
                'marital_status' => $student->marital_status?->value,
                'mode_of_study' => $student->mode_of_study->value,
                'photo_url' => $student->photo_url,
                'mat_no' => $student->mat_no,
            ],
        ]);
    }

    public function verify(CheckResultRequest $request): RedirectResponse
    {
        $student = $this->verifyAndEstablishSession($request, $request->validated('mat_no'), $request->validated('pin'));

        if (! $student) {
            return back()->withErrors([
                'mat_no' => 'That matriculation number and access PIN do not match our records.',
            ])->withInput('mat_no');
        }

        return redirect()->route('public.bio.edit');
    }

    public function update(UpdateBioDataRequest $request): RedirectResponse
    {
        $student = $this->currentlyVerifiedStudent($request);

        if (! $student || ! Setting::get('bioUpdateOpen', false)) {
            return redirect()->route('public.bio.edit');
        }

        $data = $request->validated();
        unset($data['photo']);

        if ($request->hasFile('photo')) {
            if ($student->photo_path) {
                Storage::disk('public')->delete($student->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('students', 'public');
        }

        $student->update($data);

        return redirect()->route('public.bio.edit')
            ->with('toast', 'Your bio data has been updated.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdvanceCohortRequest;
use App\Http\Requests\Admin\StoreAcademicSessionRequest;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\AcademicSession;
use App\Models\Setting;
use App\Models\StudentEnrollment;
use App\Services\Academic\EnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    private const SETTINGS_KEYS = [
        'bioUpdateOpen', 'masterSort', 'programme_duration_years',
        'institution_name', 'faculty_name', 'department_name', 'programme_name',
    ];

    public function index(): Response
    {
        $settings = collect(self::SETTINGS_KEYS)
            ->mapWithKeys(fn (string $key) => [$key => Setting::get($key)]);

        $sessions = AcademicSession::query()
            ->orderByDesc('name')
            ->get()
            ->map(fn (AcademicSession $session) => [
                'id' => $session->id,
                'name' => $session->name,
                'is_current' => $session->is_current,
                'levels' => StudentEnrollment::query()
                    ->where('academic_session_id', $session->id)
                    ->selectRaw('level, count(*) as count')
                    ->groupBy('level')
                    ->orderBy('level')
                    ->get()
                    ->map(fn ($row) => ['level' => $row->level, 'count' => $row->count])
                    ->values(),
            ]);

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'sessions' => $sessions,
            'hodSignatureUrl' => Setting::fileUrl('hod_signature_path'),
            'examOfficerSignatureUrl' => Setting::fileUrl('exam_officer_signature_path'),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('hod_signature')) {
            Setting::set('hod_signature_path', $request->file('hod_signature')->store('signatures', 'public'));
        }

        if ($request->hasFile('exam_officer_signature')) {
            Setting::set('exam_officer_signature_path', $request->file('exam_officer_signature')->store('signatures', 'public'));
        }

        unset($data['hod_signature'], $data['exam_officer_signature']);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('admin.settings.index')
            ->with('toast', 'Settings updated');
    }

    public function storeSession(StoreAcademicSessionRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            if ($data['is_current'] ?? false) {
                AcademicSession::query()->where('is_current', true)->update(['is_current' => false]);
            }

            AcademicSession::create($data);
        });

        return redirect()->route('admin.settings.index')
            ->with('toast', "{$data['name']} session created");
    }

    public function setCurrentSession(AcademicSession $session): RedirectResponse
    {
        DB::transaction(function () use ($session) {
            AcademicSession::query()->where('is_current', true)->update(['is_current' => false]);
            $session->update(['is_current' => true]);
        });

        return redirect()->route('admin.settings.index')
            ->with('toast', "{$session->name} is now the current session");
    }

    public function advanceCohort(AdvanceCohortRequest $request, EnrollmentService $enrollmentService): RedirectResponse
    {
        $data = $request->validated();

        $advanced = $enrollmentService->advanceCohort(
            AcademicSession::findOrFail($data['from_session_id']),
            (int) $data['from_level'],
            AcademicSession::findOrFail($data['to_session_id']),
        );

        return redirect()->route('admin.settings.index')
            ->with('toast', "{$advanced} student(s) advanced to level ".($data['from_level'] + 100));
    }
}

<?php

namespace App\Http\Controllers\Print;

use App\Enums\Semester;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\VerifiesPublicSession;
use App\Models\Setting;
use App\Models\Student;
use App\Services\Academic\StudentResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatementController extends Controller
{
    use VerifiesPublicSession;

    public function forPublic(Request $request, StudentResultService $resultService): Response|RedirectResponse
    {
        $student = $this->verifiedStudentOrRedirect($request);

        if ($student instanceof RedirectResponse) {
            return $student;
        }

        return $this->render($student, $request, $resultService);
    }

    public function forAdmin(Request $request, Student $student, StudentResultService $resultService): Response
    {
        return $this->render($student, $request, $resultService);
    }

    private function render(Student $student, Request $request, StudentResultService $resultService): Response
    {
        ['sessions' => $sessions, 'academicSession' => $academicSession] =
            $resultService->resolveSelectedSession($student, $request->query('session'));

        $sem1 = $resultService->semesterResult($student, $academicSession, Semester::First);
        $sem2 = $resultService->semesterResult($student, $academicSession, Semester::Second);
        $cumulative = $resultService->cumulativeAsOf($student, $academicSession);

        return Inertia::render('Print/Statement', [
            'student' => [
                'name' => $student->full_name,
                'mat_no' => $student->mat_no,
                'photo_url' => $student->photo_url,
                'dob' => $student->dob?->format('d-M-Y') ?? '—',
                'gender' => $student->gender?->label() ?? '—',
                'state_of_origin' => $student->state_of_origin ?? '—',
                'mode_of_study' => $student->mode_of_study->label(),
                'marital_status' => $student->marital_status?->label() ?? '—',
            ],
            'session' => ['name' => $academicSession->name],
            'institution' => [
                'name' => Setting::get('institution_name', 'Unity State University'),
                'faculty' => Setting::get('faculty_name', 'Faculty of Computing'),
                'department' => Setting::get('department_name', 'Department of Computer Science'),
                'programme' => Setting::get('programme_name', 'B.Sc Computer Science'),
                'examOfficerSignatureUrl' => Setting::fileUrl('exam_officer_signature_path'),
                'hodSignatureUrl' => Setting::fileUrl('hod_signature_path'),
            ],
            'sem1' => $sem1,
            'sem2' => $sem2,
            'cgpa' => $cumulative['cgpa'],
            'sessions' => $sessions,
            'selectedSessionId' => $academicSession->id,
        ]);
    }
}

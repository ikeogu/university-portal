<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Semester;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Models\Course;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\Academic\EnrollmentService;
use App\Services\Academic\MatriculationNumberParser;
use App\Services\Academic\StudentResultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));

        $students = Student::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('last_name', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('mat_no', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Student $student) => [
                'id' => $student->id,
                'name' => $student->full_name,
                'last_name' => $student->last_name,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'photo_url' => $student->photo_url,
                'mat_no' => $student->mat_no,
                'mode_of_study' => $student->mode_of_study->label(),
                'state_of_origin' => $student->state_of_origin,
                'entry_year' => $student->entry_year,
            ]);

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'search' => $search,
            'stats' => [
                'students' => Student::count(),
                'courses' => Course::count(),
                'lecturers' => User::count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Students/Onboard');
    }

    public function show(Request $request, Student $student, StudentResultService $resultService): Response
    {
        $semester = Semester::from((int) $request->query('semester', Semester::First->value));

        return Inertia::render('Result/Show', [
            ...$resultService->viewProps($student, $request->query('session'), $semester),
            'programmeName' => Setting::get('programme_name', 'B.Sc Computer Science'),
            'backHref' => route('admin.students.index'),
            'backLabel' => $student->full_name,
            'resultHref' => route('admin.students.show', $student),
            'printHref' => route('admin.students.print', $student),
            'regeneratePinHref' => route('admin.students.regenerate-pin', $student),
        ]);
    }

    public function store(StoreStudentRequest $request, EnrollmentService $enrollmentService)
    {
        $data = $request->validated();
        $data['entry_year'] = MatriculationNumberParser::parseEntryYear($data['mat_no']) ?? now()->year;

        unset($data['photo']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('students', 'public');
        }

        $student = Student::create($data);
        $enrollmentService->enrollNewStudent($student);

        return redirect()->route('admin.students.index')
            ->with('toast', $student->last_name.' onboarded successfully')
            ->with('pinReveal', [
                'name' => $student->full_name,
                'mat_no' => $student->mat_no,
                'pin' => $student->plainAccessPin,
            ]);
    }

    public function regeneratePin(Student $student)
    {
        $pin = $student->regeneratePin();

        return redirect()->route('admin.students.show', $student)
            ->with('toast', 'New access PIN generated')
            ->with('pinReveal', [
                'name' => $student->full_name,
                'mat_no' => $student->mat_no,
                'pin' => $pin,
            ]);
    }
}

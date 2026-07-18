<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Semester;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\User;
use App\Services\Academic\ScoreService;
use App\Services\Imports\CourseImporter;
use App\Services\Imports\CourseMarkSheetImporter;
use App\Services\Imports\ImportResult;
use App\Services\Imports\LecturerImporter;
use App\Services\Imports\ScoreImporter;
use App\Services\Imports\SpreadsheetReader;
use App\Services\Imports\StudentImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UploadController extends Controller
{
    private const TYPES = ['students', 'lecturers', 'courses', 'scores', 'lecturer_sheet'];

    private const TEMP_DIR = 'imports/tmp';

    private const SAMPLES_DIR = 'samples';

    private const SAMPLE_LABELS = [
        'students' => 'Bulk Upload Sample - Students.xlsx',
        'lecturers' => 'Bulk Upload Sample - Lecturers.xlsx',
        'courses' => 'Bulk Upload Sample - Courses.xlsx',
        'scores' => 'Bulk Upload Sample - Scores.xlsx',
        'lecturer_sheet' => 'Lecturer Mark Sheet Template.xlsx',
    ];

    public function index(): Response
    {
        return Inertia::render('Admin/Upload/Index', [
            'sessions' => AcademicSession::query()->orderByDesc('created_at')->get(['id', 'name']),
        ]);
    }

    public function sample(string $type): StreamedResponse
    {
        abort_unless(array_key_exists($type, self::SAMPLE_LABELS), 404);

        $path = self::SAMPLES_DIR."/{$type}.xlsx";

        abort_unless(Storage::exists($path), 404);

        return Storage::download($path, self::SAMPLE_LABELS[$type]);
    }

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $token = Str::uuid()->toString().'.'.$request->file('file')->getClientOriginalExtension();
        $path = $request->file('file')->storeAs(self::TEMP_DIR, $token);
        $type = $request->input('type');

        $extra = $type === 'lecturer_sheet'
            ? $this->previewLecturerSheet(SpreadsheetReader::read(Storage::path($path))->all())
            : [
                'rowCount' => max(SpreadsheetReader::read(Storage::path($path))->count() - 1, 0),
                'columnMap' => $this->columnMapFor($type, $request),
            ];

        return response()->json([
            'token' => $token,
            'fileName' => $request->file('file')->getClientOriginalName(),
            ...$extra,
        ]);
    }

    public function process(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
            'token' => ['required', 'string'],
        ]);

        $path = self::TEMP_DIR.'/'.$request->input('token');

        abort_unless(Storage::exists($path), 404, 'Upload has expired — please choose the file again.');

        $type = $request->input('type');

        $result = $type === 'lecturer_sheet'
            ? $this->processLecturerSheet($request, Storage::path($path))
            : $this->processSimpleImport($type, $request, SpreadsheetReader::read(Storage::path($path))->skip(1)->values());

        Storage::delete($path);

        return response()->json([
            'added' => $result->added,
            'skipped' => $result->skipped,
            'errors' => $result->errors,
            'warnings' => $result->warnings,
            'pins' => $result->pins,
        ]);
    }

    private function processSimpleImport(string $type, Request $request, $rows): ImportResult
    {
        return match ($type) {
            'students' => app(StudentImporter::class)->import($rows),
            'lecturers' => app(LecturerImporter::class)->import($rows, $this->currentSessionOrFail()),
            'courses' => app(CourseImporter::class)->import($rows),
            'scores' => $this->processScores($request, $rows),
        };
    }

    private function previewLecturerSheet(array $rows): array
    {
        $preview = app(CourseMarkSheetImporter::class)->preview($rows);

        abort_if($preview['error'] !== null, 422, $preview['error']);

        return [
            'rowCount' => $preview['rowCount'],
            'columnMap' => [
                'B → Mat No',
                'F–L → Q1–Q7 (raw exam sub-scores, summed)',
                'M → Penalty', 'N → Moderation',
                'T → CA (30%)',
            ],
            'detectedCourse' => [
                'code' => $preview['course']->code,
                'title' => $preview['course']->title,
                'semester' => $preview['course']->semester->label(),
            ],
            'detectedSession' => ['name' => $preview['session']->name],
        ];
    }

    private function processLecturerSheet(Request $request, string $path): ImportResult
    {
        $rows = SpreadsheetReader::read($path)->all();

        /** @var User $actor */
        $actor = $request->user();

        return app(CourseMarkSheetImporter::class)->import($rows, $actor);
    }

    private function processScores(Request $request, $rows): ImportResult
    {
        $request->validate([
            'session_id' => ['required', 'exists:academic_sessions,id'],
            'level' => ['required', 'integer'],
            'semester' => ['required', 'integer'],
        ]);

        $session = AcademicSession::findOrFail($request->input('session_id'));
        $courses = Course::query()
            ->where('level', (int) $request->input('level'))
            ->where('semester', (int) $request->input('semester'))
            ->orderBy('code')
            ->get();

        /** @var User $actor */
        $actor = $request->user();

        return app(ScoreImporter::class)->import($rows, $session, $courses, $actor);
    }

    private function columnMapFor(string $type, Request $request): array
    {
        return match ($type) {
            'students' => ['A → Mat No', 'B → Full name', 'C → Date of birth', 'D → State of origin', 'E → Marital status', 'F → Mode of study'],
            'lecturers' => ['A → Full name', 'B → Role', 'C → Course codes (comma-separated)', 'D → Email'],
            'courses' => ['A → Course code', 'B → Title', 'C → Credit units', 'D → Semester', 'E → Level'],
            'scores' => $this->scoreColumnMap($request),
        };
    }

    private function scoreColumnMap(Request $request): array
    {
        $map = ['A → Mat No', 'B → Name'];

        $level = (int) $request->input('level');
        $semester = (int) $request->input('semester');

        if (! $level || ! $semester) {
            return $map;
        }

        $courses = Course::query()->where('level', $level)->where('semester', $semester)->orderBy('code')->get(['code']);

        foreach ($courses as $index => $course) {
            $caCol = $this->columnLetter(2 + $index * 2);
            $examCol = $this->columnLetter(3 + $index * 2);
            $map[] = "{$caCol}/{$examCol} → {$course->code} CA/Exam";
        }

        return $map;
    }

    private function columnLetter(int $index): string
    {
        $letter = '';

        for ($index++; $index > 0; $index = intdiv($index - 1, 26)) {
            $letter = chr((($index - 1) % 26) + 65).$letter;
        }

        return $letter;
    }

    private function currentSessionOrFail(): AcademicSession
    {
        $session = AcademicSession::current();

        abort_if(! $session, 422, 'Set a current academic session before importing lecturers.');

        return $session;
    }
}

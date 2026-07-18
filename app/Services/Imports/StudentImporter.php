<?php

namespace App\Services\Imports;

use App\Enums\MaritalStatus;
use App\Enums\ModeOfStudy;
use App\Models\Student;
use App\Services\Academic\EnrollmentService;
use App\Services\Academic\MatriculationNumberParser;
use Carbon\Carbon;
use Illuminate\Support\Str;

class StudentImporter
{
    public function __construct(private EnrollmentService $enrollmentService) {}

    /**
     * Column mapping: A Mat No, B Full name, C DOB, D State of origin,
     * E Marital status, F Mode of study. $rows excludes the header row.
     *
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function import(iterable $rows): ImportResult
    {
        $seen = Student::query()->pluck('mat_no')
            ->map(fn (string $matNo) => strtoupper($matNo))
            ->flip();

        $added = 0;
        $skipped = 0;
        $errors = [];
        $pins = [];

        foreach ($rows as $index => $row) {
            $matNo = strtoupper(trim((string) ($row[0] ?? '')));
            $fullName = trim((string) ($row[1] ?? ''));

            if ($matNo === '' || $fullName === '') {
                $errors[] = ['row' => $index + 2, 'message' => 'Missing matriculation number or name.'];

                continue;
            }

            if ($seen->has($matNo)) {
                $skipped++;

                continue;
            }

            $name = FullNameParser::parse($fullName);

            $student = Student::create([
                'mat_no' => $matNo,
                'entry_year' => MatriculationNumberParser::parseEntryYear($matNo) ?? now()->year,
                'last_name' => $name['last_name'],
                'first_name' => $name['first_name'],
                'middle_name' => $name['middle_name'],
                'dob' => $this->parseDate($row[2] ?? null),
                'state_of_origin' => $this->nullableString($row[3] ?? null),
                'marital_status' => $this->matchEnum(MaritalStatus::class, $row[4] ?? null),
                'mode_of_study' => $this->matchEnum(ModeOfStudy::class, $row[5] ?? null) ?? ModeOfStudy::FullTime,
            ]);

            $this->enrollmentService->enrollNewStudent($student);

            $pins[] = ['mat_no' => $student->mat_no, 'name' => $student->full_name, 'pin' => $student->plainAccessPin];

            $seen->put($matNo, true);
            $added++;
        }

        return new ImportResult($added, $skipped, $errors, pins: $pins);
    }

    private function parseDate(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    /** @param class-string<MaritalStatus|ModeOfStudy> $enumClass */
    private function matchEnum(string $enumClass, mixed $value): MaritalStatus|ModeOfStudy|null
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach ($enumClass::cases() as $case) {
            if (Str::lower($case->label()) === Str::lower($value)) {
                return $case;
            }
        }

        return null;
    }
}

<?php

namespace App\Services\Imports;

use App\Enums\StaffRole;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LecturerImporter
{
    /**
     * Column mapping: A Full name, B Role, C Course codes (comma-separated,
     * auto-assign), D Email. The Email column is an addition beyond the
     * README's literal A/B/C mapping — a User can't sign in without one,
     * the same gap the single quick-add lecturer form resolves the same
     * way. Imported accounts get no usable password yet (password_set_at
     * stays null) — credential provisioning is a separate, deliberately
     * unbuilt step (see the plan's open items).
     *
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function import(iterable $rows, AcademicSession $session): ImportResult
    {
        $seenNames = User::query()->pluck('name')->map(fn (string $n) => Str::lower($n))->flip();
        $seenEmails = User::query()->pluck('email')->map(fn (string $e) => Str::lower($e))->flip();

        $courses = Course::query()->get()->keyBy(fn (Course $c) => Str::upper($c->code));

        $added = 0;
        $skipped = 0;
        $errors = [];
        $warnings = [];

        foreach ($rows as $index => $row) {
            $name = trim((string) ($row[0] ?? ''));
            $roleValue = trim((string) ($row[1] ?? ''));
            $codesValue = trim((string) ($row[2] ?? ''));
            $email = Str::lower(trim((string) ($row[3] ?? '')));

            if ($name === '' || $email === '') {
                $errors[] = ['row' => $index + 2, 'message' => 'Missing name or email.'];

                continue;
            }

            if ($seenNames->has(Str::lower($name)) || $seenEmails->has($email)) {
                $skipped++;

                continue;
            }

            $role = collect(StaffRole::cases())
                ->first(fn (StaffRole $r) => Str::lower($r->label()) === Str::lower($roleValue))
                ?? StaffRole::Lecturer;

            $lecturer = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'role' => $role,
                'password_set_at' => null,
            ]);

            $codes = array_filter(array_map('trim', explode(',', $codesValue)));
            $unmatched = [];

            foreach ($codes as $code) {
                $course = $courses->get(Str::upper($code));

                if (! $course) {
                    $unmatched[] = $code;

                    continue;
                }

                CourseAllocation::create([
                    'course_id' => $course->id,
                    'user_id' => $lecturer->id,
                    'academic_session_id' => $session->id,
                ]);
            }

            if ($unmatched) {
                $warnings[] = [
                    'row' => $index + 2,
                    'message' => "{$name}: course code(s) not found — ".implode(', ', $unmatched),
                ];
            }

            $seenNames->put(Str::lower($name), true);
            $seenEmails->put($email, true);
            $added++;
        }

        return new ImportResult($added, $skipped, $errors, $warnings);
    }
}

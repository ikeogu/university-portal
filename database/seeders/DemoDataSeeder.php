<?php

namespace Database\Seeders;

use App\Enums\MaritalStatus;
use App\Enums\ModeOfStudy;
use App\Enums\Semester;
use App\Enums\StaffRole;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\Score;
use App\Models\Student;
use App\Models\User;
use App\Services\Academic\EnrollmentService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Local/staging test-run fixtures: 5 students, 5 lecturers, 1 Exam Officer,
 * 1 HoD, two academic sessions (100L then 200L, cohort-advanced) with 6
 * courses per session (3 per semester), course allocations, and scores
 * spanning A-to-F so the Pass/Fail split and grading bands are all
 * exercised — and so a student checking their result has a second, past
 * session to switch to via Result/Show's session dropdown. Not part of the
 * production deploy flow (see DEPLOYMENT.md, which seeds only
 * SettingsSeeder) — run explicitly:
 *
 *   php artisan db:seed --class="Database\Seeders\DemoDataSeeder"
 */
class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    private const PASSWORD = 'password';

    private const STAFF = [
        ['name' => 'Prof. Ngozi Adeyemi', 'email' => 'hod@example.test', 'role' => StaffRole::Hod],
        ['name' => 'Mr. Tunde Bakare', 'email' => 'examofficer@example.test', 'role' => StaffRole::ExamOfficer],
        ['name' => 'Dr. Femi Alabi', 'email' => 'lecturer1@example.test', 'role' => StaffRole::Lecturer],
        ['name' => 'Dr. Grace Udo', 'email' => 'lecturer2@example.test', 'role' => StaffRole::Lecturer],
        ['name' => 'Dr. Kunle Ojo', 'email' => 'lecturer3@example.test', 'role' => StaffRole::Lecturer],
        ['name' => 'Dr. Halima Sani', 'email' => 'lecturer4@example.test', 'role' => StaffRole::Lecturer],
        ['name' => 'Dr. Emeka Obi', 'email' => 'lecturer5@example.test', 'role' => StaffRole::Lecturer],
    ];

    private const COURSES_100L = [
        ['code' => 'CSC 101', 'title' => 'Introduction to Computer Science', 'credit_units' => 3, 'semester' => Semester::First],
        ['code' => 'MTH 101', 'title' => 'Elementary Mathematics I', 'credit_units' => 3, 'semester' => Semester::First],
        ['code' => 'GST 101', 'title' => 'Use of English', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'CSC 102', 'title' => 'Introduction to Programming', 'credit_units' => 3, 'semester' => Semester::Second],
        ['code' => 'MTH 102', 'title' => 'Elementary Mathematics II', 'credit_units' => 3, 'semester' => Semester::Second],
        ['code' => 'PHY 102', 'title' => 'General Physics II', 'credit_units' => 2, 'semester' => Semester::Second],
    ];

    private const COURSES_200L = [
        ['code' => 'CSC 201', 'title' => 'Data Structures and Algorithms', 'credit_units' => 3, 'semester' => Semester::First],
        ['code' => 'MTH 201', 'title' => 'Elementary Mathematics III', 'credit_units' => 3, 'semester' => Semester::First],
        ['code' => 'STA 201', 'title' => 'Probability I', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'CSC 202', 'title' => 'Object-Oriented Programming', 'credit_units' => 3, 'semester' => Semester::Second],
        ['code' => 'MTH 202', 'title' => 'Elementary Mathematics IV', 'credit_units' => 3, 'semester' => Semester::Second],
        ['code' => 'PHY 202', 'title' => 'Electromagnetism', 'credit_units' => 2, 'semester' => Semester::Second],
    ];

    /**
     * Each student's ca/exam is a fixed base plus a small per-course
     * wobble, spread deliberately across grade bands: A, B, C, a low
     * "Pass" (1.00-1.49 CGPA), and a "Fail" (<1.00 CGPA). The same profile
     * is reused for both sessions, so CGPA carries a consistent story as
     * the cohort advances from 100L to 200L.
     *
     * access_pin is set explicitly (rather than relying on Student's
     * auto-generate-on-create hook) because this seeder uses
     * WithoutModelEvents, which suppresses that hook along with every
     * other dispatched model event.
     */
    private const STUDENTS = [
        [
            'mat_no' => 'CSC/2025/0001', 'last_name' => 'Okafor', 'first_name' => 'Chidinma', 'middle_name' => 'Grace',
            'dob' => '2005-04-12', 'state_of_origin' => 'Anambra', 'marital_status' => MaritalStatus::Single,
            'base_ca' => 27, 'base_exam' => 63, 'access_pin' => '100001',
        ],
        [
            'mat_no' => 'CSC/2025/0002', 'last_name' => 'Musa', 'first_name' => 'Ibrahim', 'middle_name' => null,
            'dob' => '2004-11-03', 'state_of_origin' => 'Kano', 'marital_status' => MaritalStatus::Single,
            'base_ca' => 21, 'base_exam' => 49, 'access_pin' => '100002',
        ],
        [
            'mat_no' => 'CSC/2025/0003', 'last_name' => 'Eze', 'first_name' => 'Victor', 'middle_name' => 'Chukwuemeka',
            'dob' => '2005-01-20', 'state_of_origin' => 'Enugu', 'marital_status' => MaritalStatus::Married,
            'base_ca' => 17, 'base_exam' => 38, 'access_pin' => '100003',
        ],
        [
            'mat_no' => 'CSC/2025/0004', 'last_name' => 'Nwosu', 'first_name' => 'Amaka', 'middle_name' => null,
            'dob' => '2005-07-08', 'state_of_origin' => 'Imo', 'marital_status' => MaritalStatus::Single,
            'base_ca' => 12, 'base_exam' => 30, 'access_pin' => '100004',
        ],
        [
            'mat_no' => 'CSC/2025/0005', 'last_name' => 'Bello', 'first_name' => 'Tunde', 'middle_name' => 'Ayo',
            'dob' => '2004-09-15', 'state_of_origin' => 'Oyo', 'marital_status' => MaritalStatus::Single,
            'base_ca' => 7, 'base_exam' => 18, 'access_pin' => '100005',
        ],
    ];

    public function run(EnrollmentService $enrollmentService): void
    {
        $this->call(SettingsSeeder::class);

        $session100 = AcademicSession::query()->firstOrCreate(
            ['name' => '2025/2026'],
            ['is_current' => true],
        );

        $session200 = AcademicSession::query()->firstOrCreate(
            ['name' => '2026/2027'],
            ['is_current' => false],
        );

        $staff = collect(self::STAFF)->map(fn (array $row) => User::query()->firstOrCreate(
            ['email' => $row['email']],
            [
                'name' => $row['name'],
                'password' => Hash::make(self::PASSWORD),
                'role' => $row['role'],
                'password_set_at' => now(),
            ],
        ));

        $examOfficer = $staff->first(fn (User $user) => $user->role === StaffRole::ExamOfficer);
        $lecturers = $staff->filter(fn (User $user) => $user->role === StaffRole::Lecturer)->values();

        $courses100 = $this->seedCourses(self::COURSES_100L, 100, $session100, $lecturers);
        $courses200 = $this->seedCourses(self::COURSES_200L, 200, $session200, $lecturers);

        $students = collect(self::STUDENTS)->map(function (array $row) use ($session100, $enrollmentService) {
            $student = Student::query()->firstOrCreate(
                ['mat_no' => $row['mat_no']],
                [
                    'entry_year' => 2025,
                    'last_name' => $row['last_name'],
                    'first_name' => $row['first_name'],
                    'middle_name' => $row['middle_name'],
                    'dob' => $row['dob'],
                    'state_of_origin' => $row['state_of_origin'],
                    'marital_status' => $row['marital_status'],
                    'mode_of_study' => ModeOfStudy::FullTime,
                    'access_pin' => $row['access_pin'],
                ],
            );

            // enrollNewStudent() enrolls into AcademicSession::current(), so
            // this must run while $session100 is still current — before it
            // gets flipped to $session200 below.
            if ($student->enrollments()->where('academic_session_id', $session100->id)->doesntExist()) {
                $enrollmentService->enrollNewStudent($student);
            }

            return $student;
        });

        AcademicSession::query()->where('id', '!=', $session200->id)->update(['is_current' => false]);
        $session200->update(['is_current' => true]);

        $enrollmentService->advanceCohort($session100, 100, $session200);

        $this->seedScores($students, $session100, $courses100, $examOfficer);
        $this->seedScores($students, $session200, $courses200, $examOfficer);

        $this->command?->info('Demo data ready — sign in at /login with any of these (password: '.self::PASSWORD.'):');
        $staff->each(fn (User $user) => $this->command?->line("  {$user->role->label()}: {$user->email}"));

        $this->command?->info('Check a result at /check with mat_no + access PIN (200L is current, 100L is the past session — switch via the session dropdown):');
        collect(self::STUDENTS)->each(fn (array $row) => $this->command
            ?->line("  {$row['mat_no']} · PIN {$row['access_pin']} · {$row['last_name']}, {$row['first_name']}"));
    }

    /** @return Collection<int, Course> */
    private function seedCourses(array $defs, int $level, AcademicSession $session, Collection $lecturers): Collection
    {
        $courses = collect($defs)->map(fn (array $row) => Course::query()->firstOrCreate(
            ['code' => $row['code']],
            [
                'title' => $row['title'],
                'credit_units' => $row['credit_units'],
                'semester' => $row['semester'],
                'level' => $level,
            ],
        ));

        $courses->each(function (Course $course, int $index) use ($session, $lecturers) {
            CourseAllocation::query()->firstOrCreate([
                'course_id' => $course->id,
                'user_id' => $lecturers[$index % $lecturers->count()]->id,
                'academic_session_id' => $session->id,
            ]);
        });

        return $courses;
    }

    /**
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, Course>  $courses
     */
    private function seedScores(Collection $students, AcademicSession $session, Collection $courses, User $examOfficer): void
    {
        $studentsByMatNo = $students->keyBy('mat_no');

        collect(self::STUDENTS)->each(function (array $row) use ($session, $courses, $examOfficer, $studentsByMatNo) {
            $student = $studentsByMatNo->get($row['mat_no']);

            $courses->each(function (Course $course, int $courseIndex) use ($session, $examOfficer, $student, $row) {
                $wobble = ($courseIndex % 3) - 1;

                Score::query()->firstOrCreate(
                    [
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                        'academic_session_id' => $session->id,
                    ],
                    [
                        'credit_units_at_entry' => $course->credit_units,
                        'ca' => max(0, min(30, $row['base_ca'] + $wobble)),
                        'exam' => max(0, min(70, $row['base_exam'] + $wobble)),
                        'entered_by' => $examOfficer->id,
                    ],
                );
            });
        });
    }
}

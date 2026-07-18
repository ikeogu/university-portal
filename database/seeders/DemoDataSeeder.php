<?php

namespace Database\Seeders;

use App\Enums\CourseCategory;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\ModeOfStudy;
use App\Enums\Semester;
use App\Enums\StaffRole;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\CourseRegistration;
use App\Models\Student;
use App\Models\User;
use App\Services\Academic\EnrollmentService;
use App\Services\Academic\ScoreService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Local/staging test-run fixtures: 5 students, 5 lecturers, 1 Exam Officer,
 * 1 HoD, two academic sessions (100L then 200L, cohort-advanced), course
 * allocations, and scores spanning A-to-F so the Pass/Fail split and
 * grading bands are all exercised — and so a student checking their result
 * has a second, past session to switch to via Result/Show's session
 * dropdown. Courses are the real B.A Linguistics and Language Arts
 * curriculum (100L-400L); only 100L/200L are wired up with allocations and
 * scores — 300L/400L are catalog-only (see the note on COURSES_300L). Score
 * seeding goes through the real ScoreService (rosterFor + saveScores), not
 * hand-crafted rows, so elective gating is exercised exactly as production
 * uses it: the 100L "choose 1 of 2" Arts elective (FAD 100.1 / THA 100.1)
 * is pre-registered per student (3 pick FAD, 2 pick THA) before scores are
 * seeded, so each student is only scored on the one they're registered
 * for. Not part of the production deploy flow (see DEPLOYMENT.md, which
 * seeds only SettingsSeeder) — run explicitly:
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

    /**
     * Real B.A Linguistics and Language Arts curriculum (Dept. of
     * Linguistics and Language Arts). Every "OR" alternative and "select N
     * of M" elective option is seeded as its own course, tagged with a
     * category (default Core when omitted below) and, for electives, an
     * elective_group + choose_count — see ELECTIVE_CHOICES/registerElectives()
     * for how the one live elective decision (Y1S1 Arts) is actually
     * registered per student.
     */
    private const COURSES_100L = [
        ['code' => 'GES 111.1', 'title' => 'Communication Skills in English', 'credit_units' => 2, 'semester' => Semester::First, 'category' => CourseCategory::Required],
        ['code' => 'FLL 111.1', 'title' => 'Fundamental French I', 'credit_units' => 3, 'semester' => Semester::First],
        ['code' => 'FAD 100.1', 'title' => 'Fundamentals of Visual Arts', 'credit_units' => 3, 'semester' => Semester::First, 'category' => CourseCategory::Elective, 'elective_group' => 'Y1S1 Arts Choice', 'choose_count' => 1],
        ['code' => 'THA 100.1', 'title' => 'Fundamentals of Theatre Arts', 'credit_units' => 3, 'semester' => Semester::First, 'category' => CourseCategory::Elective, 'elective_group' => 'Y1S1 Arts Choice', 'choose_count' => 1],
        ['code' => 'LLA 100.1', 'title' => 'Linguistics, Language and Communication', 'credit_units' => 3, 'semester' => Semester::First],
        ['code' => 'LLA 101.1', 'title' => 'Basic English Grammar', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 102.1', 'title' => 'Introduction to Phonetics & Phonology', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'GES 112.2', 'title' => 'Nigerian People and Culture', 'credit_units' => 2, 'semester' => Semester::Second, 'category' => CourseCategory::Required],
        ['code' => 'EST 120.2', 'title' => 'Introduction to the Study of Literature', 'credit_units' => 2, 'semester' => Semester::Second, 'category' => CourseCategory::Required],
        ['code' => 'LLA 111.2', 'title' => 'Study of a Nigerian Language', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 112.2', 'title' => 'Sign Language & the Semiotics of Communication', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 113.2', 'title' => 'Teaching and Learning Vocabulary and Spelling', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 114.2', 'title' => 'Linguistics in Digital Communication', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 115.2', 'title' => 'Communication and Human Psychology', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 116.2', 'title' => 'Language & Literacy Education', 'credit_units' => 2, 'semester' => Semester::Second],
    ];

    private const COURSES_200L = [
        ['code' => 'GES 211.1', 'title' => 'Entrepreneurship and Business Innovation', 'credit_units' => 2, 'semester' => Semester::First, 'category' => CourseCategory::Required],
        ['code' => 'FAC 201.1', 'title' => 'Digital Humanities', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 200.1', 'title' => 'Speech Production & Voice Training Techniques', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 201.1', 'title' => 'Introduction to Syntax', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 202.1', 'title' => 'Introduction to Communication Arts', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 203.1', 'title' => 'Grammatical Systems', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 204.1', 'title' => 'Lexicology & Lexicography', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 205.1', 'title' => 'Language Development in Children', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 206.1', 'title' => 'Morphology', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 207.1', 'title' => 'Teaching Reading through Literary Genres', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 208.1', 'title' => 'Expressive and Receptive Communication', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'GES 212.2', 'title' => 'Philosophy, Logic and Human Existence', 'credit_units' => 2, 'semester' => Semester::Second, 'category' => CourseCategory::Required],
        ['code' => 'FAC 202.2', 'title' => 'The Arts and Other Disciplines', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 211.2', 'title' => 'Grammar in the Media Class', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 212.2', 'title' => 'Phonological Analysis', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 213.2', 'title' => 'Instrumental Phonetics', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 214.2', 'title' => 'Introduction to Communication Disorders', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 215.2', 'title' => 'Language and Communication for Specific Purposes', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 216.2', 'title' => 'Public Speaking and Oratory', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 218.2', 'title' => 'Pidgins & Creoles', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 219.2', 'title' => 'Communication in Nigeria', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 2C1.2', 'title' => 'Community Service', 'credit_units' => 1, 'semester' => Semester::Second],
    ];

    /**
     * 300L/400L courses are seeded into the catalog only (no course
     * allocations, no scores) — the demo only models two live sessions
     * (100L then 200L), and inventing two more academic sessions/cohort
     * advances just to attach scores here is out of scope for "seed the
     * course catalog."
     */
    private const COURSES_300L = [
        ['code' => 'GES 312.1', 'title' => 'Peace and Conflict Resolution', 'credit_units' => 2, 'semester' => Semester::First, 'category' => CourseCategory::Required],
        ['code' => 'FAC 301.1', 'title' => 'Research Methods in the Arts and Humanities', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 300.1', 'title' => 'Historical Linguistics', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 301.1', 'title' => 'Research Methods', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 302.1', 'title' => 'Semantics', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 303.1', 'title' => 'Linguistic Inquiry', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 304.1', 'title' => 'Language Use in Digital Space', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 305.1', 'title' => 'Language Planning & Language Development', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 306.1', 'title' => 'Language & Style in Literature and the Media', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 307.1', 'title' => 'Ethnography of Communication', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 308.1', 'title' => 'Text-Writing and Text-Evaluation', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'GES 300.2', 'title' => 'Venture Creation', 'credit_units' => 2, 'semester' => Semester::Second, 'category' => CourseCategory::Required],
        ['code' => 'FAC 302.2', 'title' => 'Theories in the Arts and Humanities', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 311.2', 'title' => 'Generative Phonology', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 312.2', 'title' => 'Sociolinguistics', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 313.2', 'title' => 'Communication in Human Relations & Peace Building', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 314.2', 'title' => 'Language Documentation', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 315.2', 'title' => 'Linguistics & Publishing', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 316.2', 'title' => 'Language Teaching & Technology', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 317.2', 'title' => 'Language and the Law', 'credit_units' => 2, 'semester' => Semester::Second, 'category' => CourseCategory::Elective, 'elective_group' => 'Y3S2 Elective', 'choose_count' => 1],
        ['code' => 'LLA 318.2', 'title' => 'Forensic Linguistics', 'credit_units' => 2, 'semester' => Semester::Second, 'category' => CourseCategory::Elective, 'elective_group' => 'Y3S2 Elective', 'choose_count' => 1],
        ['code' => 'LLA 319.2', 'title' => 'Applied English Phonology', 'credit_units' => 2, 'semester' => Semester::Second, 'category' => CourseCategory::Elective, 'elective_group' => 'Y3S2 Elective', 'choose_count' => 1],
    ];

    private const COURSES_400L = [
        ['code' => 'LLA 400.1', 'title' => 'Advanced Syntax', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 401.1', 'title' => 'Principles & Practice of Translation', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 402.1', 'title' => 'Pragmatics & Discourse Analysis', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 403.1', 'title' => 'Applied Grammar & Composition', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 404.1', 'title' => 'Indigenous Knowledge Systems', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 405.1', 'title' => 'Language, Globalisation and Globalisation', 'credit_units' => 2, 'semester' => Semester::First],
        ['code' => 'LLA 406.1', 'title' => 'Computer Applications in Linguistics', 'credit_units' => 2, 'semester' => Semester::First, 'category' => CourseCategory::Elective, 'elective_group' => 'Y4S1 CompLing Choice', 'choose_count' => 1],
        ['code' => 'LLA 407.1', 'title' => 'Computational Linguistics', 'credit_units' => 2, 'semester' => Semester::First, 'category' => CourseCategory::Elective, 'elective_group' => 'Y4S1 CompLing Choice', 'choose_count' => 1],
        ['code' => 'LLA 408.1', 'title' => 'Cognitive Linguistics', 'credit_units' => 2, 'semester' => Semester::First, 'category' => CourseCategory::Elective, 'elective_group' => 'Y4S1 Elective', 'choose_count' => 1],
        ['code' => 'LLA 409.1', 'title' => 'Advanced Stylistics', 'credit_units' => 2, 'semester' => Semester::First, 'category' => CourseCategory::Elective, 'elective_group' => 'Y4S1 Elective', 'choose_count' => 1],
        ['code' => 'LLA 411.2', 'title' => 'Topics in General Linguistics', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 412.2', 'title' => 'Topics in Applied Communication & Language Arts', 'credit_units' => 2, 'semester' => Semester::Second],
        ['code' => 'LLA 413.2', 'title' => 'Project', 'credit_units' => 6, 'semester' => Semester::Second],
        ['code' => 'LLA 414.2', 'title' => 'Topics in African Linguistics', 'credit_units' => 2, 'semester' => Semester::Second, 'category' => CourseCategory::Elective, 'elective_group' => 'Y4S2 Elective', 'choose_count' => 3],
        ['code' => 'LLA 415.2', 'title' => 'Topics in Language & Intercultural Communication', 'credit_units' => 2, 'semester' => Semester::Second, 'category' => CourseCategory::Elective, 'elective_group' => 'Y4S2 Elective', 'choose_count' => 3],
        ['code' => 'LLA 416.2', 'title' => 'Topics in Deviant Language Development & Management', 'credit_units' => 2, 'semester' => Semester::Second, 'category' => CourseCategory::Elective, 'elective_group' => 'Y4S2 Elective', 'choose_count' => 3],
        ['code' => 'LLA 417.2', 'title' => 'Topics in Applied Linguistics', 'credit_units' => 2, 'semester' => Semester::Second, 'category' => CourseCategory::Elective, 'elective_group' => 'Y4S2 Elective', 'choose_count' => 3],
        ['code' => 'LLA 418.2', 'title' => 'Topics in Writing for Specific/General Academic Purpose', 'credit_units' => 2, 'semester' => Semester::Second, 'category' => CourseCategory::Elective, 'elective_group' => 'Y4S2 Elective', 'choose_count' => 3],
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
            'dob' => '2005-04-12', 'gender' => Gender::Female, 'state_of_origin' => 'Anambra', 'marital_status' => MaritalStatus::Single,
            'base_ca' => 27, 'base_exam' => 63, 'access_pin' => '100001',
        ],
        [
            'mat_no' => 'CSC/2025/0002', 'last_name' => 'Musa', 'first_name' => 'Ibrahim', 'middle_name' => null,
            'dob' => '2004-11-03', 'gender' => Gender::Male, 'state_of_origin' => 'Kano', 'marital_status' => MaritalStatus::Single,
            'base_ca' => 21, 'base_exam' => 49, 'access_pin' => '100002',
        ],
        [
            'mat_no' => 'CSC/2025/0003', 'last_name' => 'Eze', 'first_name' => 'Victor', 'middle_name' => 'Chukwuemeka',
            'dob' => '2005-01-20', 'gender' => Gender::Male, 'state_of_origin' => 'Enugu', 'marital_status' => MaritalStatus::Married,
            'base_ca' => 17, 'base_exam' => 38, 'access_pin' => '100003',
        ],
        [
            'mat_no' => 'CSC/2025/0004', 'last_name' => 'Nwosu', 'first_name' => 'Amaka', 'middle_name' => null,
            'dob' => '2005-07-08', 'gender' => Gender::Female, 'state_of_origin' => 'Imo', 'marital_status' => MaritalStatus::Single,
            'base_ca' => 12, 'base_exam' => 30, 'access_pin' => '100004',
        ],
        [
            'mat_no' => 'CSC/2025/0005', 'last_name' => 'Bello', 'first_name' => 'Tunde', 'middle_name' => 'Ayo',
            'dob' => '2004-09-15', 'gender' => Gender::Male, 'state_of_origin' => 'Oyo', 'marital_status' => MaritalStatus::Single,
            'base_ca' => 7, 'base_exam' => 18, 'access_pin' => '100005',
        ],
    ];

    public function run(EnrollmentService $enrollmentService, ScoreService $scoreService): void
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
        $this->seedCatalogOnly(self::COURSES_300L, 300);
        $this->seedCatalogOnly(self::COURSES_400L, 400);

        $students = collect(self::STUDENTS)->map(function (array $row) use ($session100, $enrollmentService) {
            $student = Student::query()->firstOrCreate(
                ['mat_no' => $row['mat_no']],
                [
                    'entry_year' => 2025,
                    'last_name' => $row['last_name'],
                    'first_name' => $row['first_name'],
                    'middle_name' => $row['middle_name'],
                    'dob' => $row['dob'],
                    'gender' => $row['gender'],
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

        $this->registerElectives($students, $courses100, $session100, $examOfficer);

        AcademicSession::query()->where('id', '!=', $session200->id)->update(['is_current' => false]);
        $session200->update(['is_current' => true]);

        $enrollmentService->advanceCohort($session100, 100, $session200);

        $this->seedScores($scoreService, $students, $session100, $courses100, $examOfficer);
        $this->seedScores($scoreService, $students, $session200, $courses200, $examOfficer);

        $this->command?->info('Demo data ready — sign in at /login with any of these (password: '.self::PASSWORD.'):');
        $staff->each(fn (User $user) => $this->command?->line("  {$user->role->label()}: {$user->email}"));

        $this->command?->info('Check a result at /check with mat_no + access PIN (200L is current, 100L is the past session — switch via the session dropdown):');
        collect(self::STUDENTS)->each(fn (array $row) => $this->command
            ?->line("  {$row['mat_no']} · PIN {$row['access_pin']} · {$row['last_name']}, {$row['first_name']}"));
    }

    /**
     * The Y1S1 Arts elective (choose 1 of FAD 100.1 / THA 100.1) is
     * pre-registered per student — the app has no other electives wired
     * to a live session (300L/400L are catalog-only), so this is the one
     * place the demo actually exercises registration-gated scoring.
     */
    private const ELECTIVE_CHOICES = [
        'CSC/2025/0001' => 'FAD 100.1',
        'CSC/2025/0002' => 'FAD 100.1',
        'CSC/2025/0003' => 'FAD 100.1',
        'CSC/2025/0004' => 'THA 100.1',
        'CSC/2025/0005' => 'THA 100.1',
    ];

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
                'category' => $row['category'] ?? CourseCategory::Core,
                'elective_group' => $row['elective_group'] ?? null,
                'choose_count' => $row['choose_count'] ?? null,
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
     * Course-catalog rows only — no session to allocate a lecturer or
     * attach scores against (see the class-level note on COURSES_300L).
     */
    private function seedCatalogOnly(array $defs, int $level): void
    {
        collect($defs)->each(fn (array $row) => Course::query()->firstOrCreate(
            ['code' => $row['code']],
            [
                'title' => $row['title'],
                'credit_units' => $row['credit_units'],
                'semester' => $row['semester'],
                'level' => $level,
                'category' => $row['category'] ?? CourseCategory::Core,
                'elective_group' => $row['elective_group'] ?? null,
                'choose_count' => $row['choose_count'] ?? null,
            ],
        ));
    }

    /**
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, Course>  $courses
     */
    private function registerElectives(Collection $students, Collection $courses, AcademicSession $session, User $registeredBy): void
    {
        $studentsByMatNo = $students->keyBy('mat_no');
        $coursesByCode = $courses->keyBy('code');

        foreach (self::ELECTIVE_CHOICES as $matNo => $code) {
            $student = $studentsByMatNo->get($matNo);
            $course = $coursesByCode->get($code);

            if (! $student || ! $course) {
                continue;
            }

            CourseRegistration::query()->firstOrCreate(
                [
                    'student_id' => $student->id,
                    'course_id' => $course->id,
                    'academic_session_id' => $session->id,
                ],
                ['registered_by' => $registeredBy->id],
            );
        }
    }

    /**
     * Seeds via the real ScoreService (rosterFor + saveScores), not
     * hand-crafted rows, so elective registration gating is exercised the
     * same way production uses it — a student only gets a Score row for an
     * elective course if rosterFor() finds a matching CourseRegistration.
     *
     * @param  Collection<int, Student>  $students
     * @param  Collection<int, Course>  $courses
     */
    private function seedScores(ScoreService $scoreService, Collection $students, AcademicSession $session, Collection $courses, User $examOfficer): void
    {
        $studentsByMatNo = $students->keyBy('mat_no');

        $courses->each(function (Course $course, int $courseIndex) use ($scoreService, $session, $examOfficer, $studentsByMatNo) {
            $roster = $scoreService->rosterFor($course, $session);
            $wobble = ($courseIndex % 3) - 1;

            $entries = [];

            foreach (self::STUDENTS as $row) {
                $student = $studentsByMatNo->get($row['mat_no']);

                if (! $student || ! $roster->contains('student_id', $student->id)) {
                    continue;
                }

                $entries[$student->id] = [
                    'ca' => max(0, min(30, $row['base_ca'] + $wobble)),
                    'exam' => max(0, min(70, $row['base_exam'] + $wobble)),
                ];
            }

            $scoreService->saveScores($course, $session, $entries, $examOfficer);
        });
    }
}

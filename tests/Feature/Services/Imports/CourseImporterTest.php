<?php

namespace Tests\Feature\Services\Imports;

use App\Enums\CourseCategory;
use App\Enums\Semester;
use App\Models\Course;
use App\Services\Imports\CourseImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_new_courses(): void
    {
        $result = (new CourseImporter)->import([
            ['CSC 411', 'Net-Centric Computing', '2', '1', '400'],
            ['CSC 408', 'Human-Computer Interaction', '2', '2', '300'],
        ]);

        $this->assertSame(2, $result->added);
        $this->assertSame(0, $result->skipped);

        $course = Course::where('code', 'CSC 411')->sole();
        $this->assertSame('Net-Centric Computing', $course->title);
        $this->assertSame(2, $course->credit_units);
        $this->assertSame(Semester::First, $course->semester);
        $this->assertSame(400, $course->level);
    }

    public function test_skips_duplicate_codes_both_existing_and_within_the_same_file(): void
    {
        Course::create(['code' => 'CSC 411', 'title' => 'Existing', 'credit_units' => 3, 'semester' => Semester::First, 'level' => 400]);

        $result = (new CourseImporter)->import([
            ['csc 411', 'Net-Centric Computing', '2', '1', '400'], // matches existing, case-insensitive
            ['CSC 408', 'HCI', '2', '2', '300'],
            ['CSC 408', 'HCI', '2', '2', '300'], // duplicate within file
        ]);

        $this->assertSame(1, $result->added);
        $this->assertSame(2, $result->skipped);
        $this->assertSame(2, Course::count());
    }

    public function test_reports_rows_missing_code_or_title(): void
    {
        $result = (new CourseImporter)->import([
            ['', 'Net-Centric Computing', '2', '1', '400'],
            ['CSC 408', '', '2', '2', '300'],
        ]);

        $this->assertSame(0, $result->added);
        $this->assertCount(2, $result->errors);
    }

    public function test_defaults_to_level_100_when_level_column_is_missing(): void
    {
        (new CourseImporter)->import([
            ['CSC 411', 'Net-Centric Computing', '2', '1'],
        ]);

        $this->assertSame(100, Course::sole()->level);
    }

    public function test_defaults_to_core_category_when_category_column_is_missing(): void
    {
        (new CourseImporter)->import([
            ['CSC 411', 'Net-Centric Computing', '2', '1', '400'],
        ]);

        $this->assertSame(CourseCategory::Core, Course::sole()->category);
    }

    public function test_imports_an_elective_course_with_its_group_and_choose_count(): void
    {
        (new CourseImporter)->import([
            ['LLA 317.2', 'Language and the Law', '2', '2', '300', 'Elective', 'Y3S2 Elective', '1'],
        ]);

        $course = Course::sole();
        $this->assertSame(CourseCategory::Elective, $course->category);
        $this->assertSame('Y3S2 Elective', $course->elective_group);
        $this->assertSame(1, $course->choose_count);
    }

    public function test_elective_group_and_choose_count_are_ignored_for_non_elective_courses(): void
    {
        (new CourseImporter)->import([
            ['CSC 411', 'Net-Centric Computing', '2', '1', '400', 'Core', 'Should be ignored', '2'],
        ]);

        $course = Course::sole();
        $this->assertSame(CourseCategory::Core, $course->category);
        $this->assertNull($course->elective_group);
        $this->assertNull($course->choose_count);
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Enums\Semester;
use App\Models\AcademicSession;
use App\Models\Course;
use App\Models\CourseAllocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_buckets_and_totals_courses_by_semester(): void
    {
        $admin = User::factory()->hod()->create();

        $currentSession = AcademicSession::create([
            'name' => '2025/2026',
            'is_current' => true,
        ]);

        $oldSession = AcademicSession::create([
            'name' => '2024/2025',
            'is_current' => false,
        ]);

        $lecturerOne = User::factory()->lecturer()->create(['name' => 'Dr. Amaka Obi']);
        $lecturerTwo = User::factory()->lecturer()->create(['name' => 'Dr. Bassey Eno']);

        $csc411 = Course::create([
            'code' => 'CSC 411',
            'title' => 'Software Engineering',
            'credit_units' => 3,
            'semester' => Semester::First,
            'level' => 400,
        ]);

        $csc413 = Course::create([
            'code' => 'CSC 413',
            'title' => 'Distributed Systems',
            'credit_units' => 2,
            'semester' => Semester::First,
            'level' => 400,
        ]);

        Course::create([
            'code' => 'CSC 422',
            'title' => 'Compiler Construction',
            'credit_units' => 4,
            'semester' => Semester::Second,
            'level' => 400,
        ]);

        // Two lecturers assigned to CSC 411 in the current session.
        CourseAllocation::create([
            'course_id' => $csc411->id,
            'user_id' => $lecturerOne->id,
            'academic_session_id' => $currentSession->id,
        ]);
        CourseAllocation::create([
            'course_id' => $csc411->id,
            'user_id' => $lecturerTwo->id,
            'academic_session_id' => $currentSession->id,
        ]);

        // An allocation from a past session must not leak into the "current" listing.
        CourseAllocation::create([
            'course_id' => $csc413->id,
            'user_id' => $lecturerOne->id,
            'academic_session_id' => $oldSession->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.courses.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Admin/Courses/Index'));

        $semesters = collect($response->inertiaProps('semesters'));

        $first = $semesters->firstWhere('value', Semester::First->value);
        $second = $semesters->firstWhere('value', Semester::Second->value);

        $this->assertSame('First', $first['label']);
        $this->assertSame(5, $first['total_credit_units']); // 3 (CSC 411) + 2 (CSC 413)
        $this->assertCount(2, $first['courses']);

        $this->assertSame('Second', $second['label']);
        $this->assertSame(4, $second['total_credit_units']);
        $this->assertCount(1, $second['courses']);

        $firstSemesterCourses = collect($first['courses']);

        $csc411Row = $firstSemesterCourses->firstWhere('code', 'CSC 411');
        $this->assertSame('Software Engineering', $csc411Row['title']);
        $this->assertSame(3, $csc411Row['credit_units']);
        $this->assertSame('Dr. Amaka Obi, Dr. Bassey Eno', $csc411Row['lecturers']);

        // CSC 413 only has an allocation in a past session, so it must show as Unassigned.
        $csc413Row = $firstSemesterCourses->firstWhere('code', 'CSC 413');
        $this->assertSame('Unassigned', $csc413Row['lecturers']);
    }

    public function test_store_creates_a_course_and_redirects_with_a_toast(): void
    {
        $admin = User::factory()->examOfficer()->create();

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), [
            'code' => 'csc 411',
            'title' => 'Software Engineering',
            'credit_units' => '3',
            'semester' => '1',
            'level' => '400',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.courses.index'));

        $this->assertSame('CSC 411 added — 3 credit units', session('toast'));

        $course = Course::sole();
        $this->assertSame('CSC 411', $course->code);
        $this->assertSame('Software Engineering', $course->title);
        $this->assertEquals(3, $course->credit_units);
        $this->assertSame(Semester::First, $course->semester);
        $this->assertEquals(400, $course->level);
        $this->assertTrue($course->is_active);
    }

    public function test_duplicate_course_code_is_rejected_with_a_validation_error(): void
    {
        $admin = User::factory()->hod()->create();

        Course::create([
            'code' => 'CSC 411',
            'title' => 'Software Engineering',
            'credit_units' => 3,
            'semester' => Semester::First,
            'level' => 400,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), [
            'code' => 'CSC 411',
            'title' => 'Software Engineering II',
            'credit_units' => '3',
            'semester' => '1',
            'level' => '400',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertSame(1, Course::count());
    }

    public function test_lecturers_cannot_access_the_courses_routes(): void
    {
        $lecturer = User::factory()->lecturer()->create();

        $this->actingAs($lecturer)
            ->get(route('admin.courses.index'))
            ->assertForbidden();

        $this->actingAs($lecturer)
            ->post(route('admin.courses.store'), [
                'code' => 'CSC 411',
                'title' => 'Software Engineering',
                'credit_units' => '3',
                'semester' => '1',
                'level' => '400',
            ])
            ->assertForbidden();

        $this->assertSame(0, Course::count());
    }
}

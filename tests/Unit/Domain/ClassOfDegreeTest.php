<?php

namespace Tests\Unit\Domain;

use App\Domain\Grading\ClassOfDegree;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ClassOfDegreeTest extends TestCase
{
    public static function cgpaProvider(): array
    {
        return [
            'lowest possible cgpa' => [0.00, ClassOfDegree::Fail, 'Fail', 'Fail'],
            'top of Fail range' => [0.99, ClassOfDegree::Fail, 'Fail', 'Fail'],
            'bottom of Pass range' => [1.00, ClassOfDegree::Pass, 'Pass', 'Pass'],
            'top of Pass range' => [1.49, ClassOfDegree::Pass, 'Pass', 'Pass'],
            'bottom of 3rd Class range' => [1.50, ClassOfDegree::Third, '3rd Class', '3rd'],
            'top of 3rd Class range' => [2.39, ClassOfDegree::Third, '3rd Class', '3rd'],
            'bottom of 2nd Class Lower range' => [2.40, ClassOfDegree::SecondLower, '2nd Class Lower', '2:2'],
            'top of 2nd Class Lower range' => [3.49, ClassOfDegree::SecondLower, '2nd Class Lower', '2:2'],
            'bottom of 2nd Class Upper range' => [3.50, ClassOfDegree::SecondUpper, '2nd Class Upper', '2:1'],
            'top of 2nd Class Upper range' => [4.49, ClassOfDegree::SecondUpper, '2nd Class Upper', '2:1'],
            'bottom of First Class range' => [4.50, ClassOfDegree::FirstClass, 'First Class', '1st'],
            'highest possible cgpa' => [5.00, ClassOfDegree::FirstClass, 'First Class', '1st'],
        ];
    }

    #[DataProvider('cgpaProvider')]
    public function test_from_cgpa_resolves_the_correct_class(
        float $cgpa,
        ClassOfDegree $expectedClass,
        string $expectedLabel,
        string $expectedAbbreviation,
    ): void {
        $class = ClassOfDegree::fromCgpa($cgpa);

        $this->assertSame($expectedClass, $class);
        $this->assertSame($expectedLabel, $class->label());
        $this->assertSame($expectedAbbreviation, $class->abbreviation());
    }

    public function test_boundary_just_below_each_cutoff_falls_into_the_lower_class(): void
    {
        $this->assertSame(ClassOfDegree::Fail, ClassOfDegree::fromCgpa(0.999999));
        $this->assertSame(ClassOfDegree::Pass, ClassOfDegree::fromCgpa(1.499999));
        $this->assertSame(ClassOfDegree::Third, ClassOfDegree::fromCgpa(2.399999));
        $this->assertSame(ClassOfDegree::SecondLower, ClassOfDegree::fromCgpa(3.499999));
        $this->assertSame(ClassOfDegree::SecondUpper, ClassOfDegree::fromCgpa(4.499999));
    }
}

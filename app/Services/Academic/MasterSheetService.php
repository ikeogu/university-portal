<?php

namespace App\Services\Academic;

use App\Domain\Grading\ClassOfDegree;
use App\Domain\Grading\ScoreRow;
use App\Models\Score;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Support\Collection;

class MasterSheetService
{
    public function __construct(private CumulativeResultCalculator $calculator) {}

    /**
     * A student "graduates" once they've reached the final level for the
     * programme's configured duration — this is a reporting cutoff, not a
     * clearance decision (carryovers, disciplinary holds, etc. are Senate/
     * Academic Board business, out of scope here).
     */
    public function terminalLevel(): int
    {
        return 100 * (int) Setting::get('programme_duration_years', 4);
    }

    /**
     * @return Collection<int, int> distinct entry years ("sets") among
     *         students who have reached the terminal level, newest first
     */
    public function graduatingSets(): Collection
    {
        $studentIds = StudentEnrollment::query()
            ->where('level', $this->terminalLevel())
            ->pluck('student_id')
            ->unique();

        return Student::query()
            ->whereIn('id', $studentIds)
            ->pluck('entry_year')
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->sortDesc()
            ->values();
    }

    /**
     * Every graduating student in $set, with their Year I..N breakdown,
     * final TCU/TQP/CGPA and class of degree — the real replacement for
     * the prototype's yr()/fin() history fakery, now driven entirely by
     * real enrollments and scores.
     *
     * @return Collection<int, array>
     */
    public function forSet(int $set, string $sortBy = 'cgpa'): Collection
    {
        $studentIds = StudentEnrollment::query()
            ->where('level', $this->terminalLevel())
            ->whereHas('student', fn ($query) => $query->where('entry_year', $set))
            ->pluck('student_id')
            ->unique();

        $rows = Student::query()
            ->whereIn('id', $studentIds)
            ->get()
            ->map(function (Student $student) {
                $result = $this->calculator->calculate($this->rowsByLevelFor($student));

                return [
                    'mat_no' => $student->mat_no,
                    'name' => $student->full_name,
                    'byLevel' => $result['byLevel'],
                    'tcu' => $result['tcu'],
                    'tqp' => $result['tqp'],
                    'cgpa' => $result['cgpa'],
                    'class' => ClassOfDegree::fromCgpa($result['cgpa']),
                ];
            });

        $sorted = $sortBy === 'matno'
            ? $rows->sortBy('mat_no')
            : $rows->sortByDesc('cgpa');

        return $sorted->values();
    }

    /**
     * @param  Collection<int, array{class: ClassOfDegree}>  $rows
     * @return array<string, int> counts keyed by ClassOfDegree::value
     */
    public function classDistribution(Collection $rows): array
    {
        return collect(ClassOfDegree::cases())
            ->mapWithKeys(fn (ClassOfDegree $class) => [
                $class->value => $rows->filter(fn ($row) => $row['class'] === $class)->count(),
            ])
            ->all();
    }

    /**
     * @return array<int, ScoreRow[]> every score row the student has,
     *         grouped by the level they were enrolled at when it was
     *         entered — sessions sharing a level (e.g. a repeated year)
     *         are merged rather than one silently overwriting the other.
     */
    private function rowsByLevelFor(Student $student): array
    {
        $enrollments = StudentEnrollment::where('student_id', $student->id)->get();
        $byLevel = [];

        foreach ($enrollments as $enrollment) {
            $rows = Score::query()
                ->where('student_id', $student->id)
                ->where('academic_session_id', $enrollment->academic_session_id)
                ->get()
                ->map(fn (Score $score) => new ScoreRow($score->ca, $score->exam, $score->credit_units_at_entry))
                ->all();

            $byLevel[$enrollment->level] = array_merge($byLevel[$enrollment->level] ?? [], $rows);
        }

        ksort($byLevel);

        return $byLevel;
    }
}

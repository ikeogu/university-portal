<?php

namespace App\Services\Imports;

readonly class ImportResult
{
    /**
     * @param  array<int, array{row: int, message: string}>  $errors  rows skipped entirely
     * @param  array<int, array{row: int, message: string}>  $warnings  rows that still succeeded, but partially
     * @param  array<int, array{mat_no: string, name: string, pin: string}>  $pins  students import only —
     *   each newly created student's one-time plaintext access PIN, for the admin to hand out
     */
    public function __construct(
        public int $added,
        public int $skipped,
        public array $errors = [],
        public array $warnings = [],
        public array $pins = [],
    ) {}
}

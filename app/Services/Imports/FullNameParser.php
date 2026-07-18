<?php

namespace App\Services\Imports;

class FullNameParser
{
    /**
     * Parse the "Surname, Other Names" convention used throughout this app
     * (onboard form, demo data, bulk imports) into the students table's
     * split last_name/first_name/middle_name columns. Falls back to
     * splitting on whitespace if there's no comma, since bulk spreadsheet
     * data is never guaranteed to follow the convention exactly.
     *
     * @return array{last_name: string, first_name: string, middle_name: ?string}
     */
    public static function parse(string $fullName): array
    {
        $fullName = trim($fullName);

        if (str_contains($fullName, ',')) {
            [$last, $rest] = explode(',', $fullName, 2);
            $last = trim($last);
            $parts = preg_split('/\s+/', trim($rest), -1, PREG_SPLIT_NO_EMPTY);
        } else {
            $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY);
            $last = array_shift($parts) ?? $fullName;
        }

        $first = array_shift($parts) ?? $last;
        $middle = $parts ? implode(' ', $parts) : null;

        return ['last_name' => $last, 'first_name' => $first, 'middle_name' => $middle];
    }
}

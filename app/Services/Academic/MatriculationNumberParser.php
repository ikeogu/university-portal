<?php

namespace App\Services\Academic;

class MatriculationNumberParser
{
    /**
     * Parse a student's entry year ("set") from their matriculation number,
     * e.g. "U2022/5570001" -> 2022. Callers may override the result manually.
     */
    public static function parseEntryYear(string $matNo): ?int
    {
        return preg_match('/(\d{4})/', $matNo, $matches) ? (int) $matches[1] : null;
    }
}

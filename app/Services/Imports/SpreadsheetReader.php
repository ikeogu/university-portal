<?php

namespace App\Services\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Facades\Excel;

class SpreadsheetReader implements ToCollection
{
    public function collection(Collection $rows): void
    {
        // Intentionally empty — this class exists only to satisfy
        // maatwebsite/excel's ToCollection contract for the synchronous
        // Excel::toCollection() reader below; rows are consumed directly
        // from that call's return value, not from this method.
    }

    /**
     * Read every row of a spreadsheet's first sheet, including the header
     * row (callers slice it off) — kept positional (not header-keyed)
     * since every column mapping in this app is "column A is X, column B
     * is Y", not header-name-based.
     *
     * @return Collection<int, array<int, mixed>>
     */
    public static function read(string $path): Collection
    {
        return Excel::toCollection(new self, $path)
            ->first()
            ->map(fn (Collection $row) => $row->all());
    }
}

<?php

namespace App\Http\Controllers\Print;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Academic\MasterSheetService;
use Inertia\Inertia;
use Inertia\Response;

class MasterSheetController extends Controller
{
    public function show(MasterSheetService $masterSheet): Response
    {
        $sortBy = Setting::get('masterSort', 'cgpa');
        $sets = $masterSheet->graduatingSets();

        $groups = $sets->map(function (int $set) use ($masterSheet, $sortBy) {
            $rows = $masterSheet->forSet($set, $sortBy);

            return [
                'label' => "{$set} SET",
                'rows' => $rows->values()->map(fn ($row, $i) => [
                    ...$row,
                    'sn' => $i + 1,
                    'classShort' => $row['class']->abbreviation(),
                ]),
            ];
        });

        $allRows = $groups->flatMap(fn ($group) => $group['rows']);

        return Inertia::render('Print/MasterSheet', [
            'institution' => [
                'name' => Setting::get('institution_name', 'Unity State University'),
                'faculty' => Setting::get('faculty_name', 'Faculty of Computing'),
                'department' => Setting::get('department_name', 'Department of Computer Science'),
                'programme' => Setting::get('programme_name', 'B.Sc Computer Science'),
            ],
            'terminalLevel' => $masterSheet->terminalLevel(),
            'groups' => $groups,
            'summary' => $masterSheet->classDistribution($allRows),
            'total' => $allRows->count(),
        ]);
    }
}

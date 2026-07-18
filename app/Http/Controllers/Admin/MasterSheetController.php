<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Academic\MasterSheetService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MasterSheetController extends Controller
{
    public function index(Request $request, MasterSheetService $masterSheet): Response
    {
        $sets = $masterSheet->graduatingSets();
        $selectedSet = (int) $request->query('set', $sets->first() ?? 0);
        $sortBy = Setting::get('masterSort', 'cgpa');

        $rows = $selectedSet ? $masterSheet->forSet($selectedSet, $sortBy) : collect();

        return Inertia::render('Admin/Master/Index', [
            'sets' => $sets,
            'selectedSet' => $selectedSet,
            'rows' => $rows->values()->map(fn ($row, $i) => [...$row, 'sn' => $i + 1, 'class' => $row['class']->label(), 'classShort' => $row['class']->abbreviation()]),
            'classDistribution' => $selectedSet ? $masterSheet->classDistribution($rows) : [],
            'terminalLevel' => $masterSheet->terminalLevel(),
        ]);
    }
}

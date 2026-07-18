<?php

use App\Http\Controllers\Public\BioDataController;
use App\Http\Controllers\Public\ResultCheckController;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Public/Landing', [
    'institutionName' => Setting::get('institution_name', 'Unity State University'),
    'facultyName' => Setting::get('faculty_name'),
    'departmentName' => Setting::get('department_name', 'Dept. of Computer Science'),
    'bioDataHref' => Setting::get('bioUpdateOpen', false) ? route('public.bio.edit') : null,
]))->name('landing');

Route::middleware('throttle:result-check')->group(function () {
    Route::get('check', [ResultCheckController::class, 'create'])->name('public.check');
    Route::post('check', [ResultCheckController::class, 'store'])->name('public.check.store');
    Route::post('check/bio/verify', [BioDataController::class, 'verify'])->name('public.bio.verify');
});

Route::get('check/result', [ResultCheckController::class, 'result'])->name('public.result');

Route::get('check/bio', [BioDataController::class, 'edit'])->name('public.bio.edit');

Route::middleware('throttle:20,1')->group(function () {
    Route::patch('check/bio', [BioDataController::class, 'update'])->name('public.bio.update');
});

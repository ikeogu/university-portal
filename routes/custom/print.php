<?php

use App\Http\Controllers\Print\ScoreSheetController;
use App\Http\Controllers\Print\StatementController;
use Illuminate\Support\Facades\Route;

Route::get('check/result/print', [StatementController::class, 'forPublic'])->name('public.result.print');

Route::middleware(['auth', 'role:exam_officer,hod'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('students/{student}/print', [StatementController::class, 'forAdmin'])->name('students.print');
});

Route::middleware('auth')->group(function () {
    Route::get('courses/{course}/scores/print', [ScoreSheetController::class, 'show'])->name('scores.print');
});

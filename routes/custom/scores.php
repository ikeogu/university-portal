<?php

use App\Http\Controllers\ScoreEntryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('courses/{course}/scores', [ScoreEntryController::class, 'show'])->name('scores.show');
    Route::put('courses/{course}/scores', [ScoreEntryController::class, 'update'])->name('scores.update');
});

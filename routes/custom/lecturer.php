<?php

use App\Http\Controllers\Lecturer\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('lecturer', [DashboardController::class, 'index'])->name('lecturer.dashboard');
});

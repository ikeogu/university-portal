<?php

use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:exam_officer,hod'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/sessions', [SettingsController::class, 'storeSession'])->name('settings.sessions.store');
    Route::post('settings/sessions/{session}/set-current', [SettingsController::class, 'setCurrentSession'])->name('settings.sessions.set-current');
    Route::post('settings/advance-cohort', [SettingsController::class, 'advanceCohort'])->name('settings.advance-cohort');
});

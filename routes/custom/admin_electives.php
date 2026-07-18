<?php

use App\Http\Controllers\Admin\ElectiveRegistrationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:exam_officer,hod'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('electives', [ElectiveRegistrationController::class, 'index'])->name('electives.index');
    Route::post('electives', [ElectiveRegistrationController::class, 'update'])->name('electives.update');
});

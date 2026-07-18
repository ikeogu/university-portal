<?php

use App\Http\Controllers\Admin\LecturerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:exam_officer,hod'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('lecturers', [LecturerController::class, 'index'])->name('lecturers.index');
    Route::post('lecturers', [LecturerController::class, 'store'])->name('lecturers.store');
    Route::post('lecturers/{lecturer}/courses/{course}/toggle', [LecturerController::class, 'toggleCourse'])->name('lecturers.toggle-course');
});

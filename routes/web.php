<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/custom/admin.php';
require __DIR__.'/custom/admin_lecturers.php';
require __DIR__.'/custom/admin_courses.php';
require __DIR__.'/custom/admin_electives.php';
require __DIR__.'/custom/admin_upload.php';
require __DIR__.'/custom/admin_master.php';
require __DIR__.'/custom/admin_settings.php';
require __DIR__.'/custom/lecturer.php';
require __DIR__.'/custom/scores.php';
require __DIR__.'/custom/public.php';
require __DIR__.'/custom/print.php';

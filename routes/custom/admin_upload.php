<?php

use App\Http\Controllers\Admin\UploadController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:exam_officer,hod'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('upload', [UploadController::class, 'index'])->name('upload.index');
    Route::post('upload/preview', [UploadController::class, 'preview'])->name('upload.preview');
    Route::post('upload/process', [UploadController::class, 'process'])->name('upload.process');
    Route::get('upload/sample/{type}', [UploadController::class, 'sample'])->name('upload.sample');
});

<?php

use App\Http\Controllers\Admin\MasterSheetController;
use App\Http\Controllers\Print\MasterSheetController as PrintMasterSheetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:exam_officer,hod'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('master', [MasterSheetController::class, 'index'])->name('master.index');
    Route::get('master/print', [PrintMasterSheetController::class, 'show'])->name('master.print');
});

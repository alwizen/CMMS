<?php

use App\Http\Controllers\MaintenanceHistoryPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/maintenance-histories/pdf', MaintenanceHistoryPdfController::class)
        ->name('maintenance-histories.pdf');
});

<?php

use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DailyReportPdfController;
use App\Http\Controllers\FieldDailyReportPdfController;
use App\Http\Controllers\MaintenanceHistoryPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Undangan pengguna (invite-only): tautan bertanda tangan, sekali pakai.
Route::get('/invitation/{user}', [InvitationController::class, 'show'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('invitations.accept');
Route::post('/invitation/{user}', [InvitationController::class, 'store'])
    ->middleware(['signed', 'throttle:30,1'])
    ->name('invitations.store');

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/maintenance-histories/pdf', MaintenanceHistoryPdfController::class)
        ->name('maintenance-histories.pdf');

    Route::get('/admin/daily-report/pdf', DailyReportPdfController::class)
        ->name('daily-report.pdf');

    Route::get('/app/daily-report/pdf', FieldDailyReportPdfController::class)
        ->name('app.daily-report.pdf');
});

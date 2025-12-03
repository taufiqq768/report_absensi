<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AbsensiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Laporan Absensi HCIS
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Guest routes (not authenticated)
Route::middleware('guest.session')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth.session')->group(function () {
    Route::get('/dashboard', [AbsensiController::class, 'index'])->name('dashboard');
    Route::post('/api/absensi', [AbsensiController::class, 'getData'])->name('absensi.data');
    Route::post('/absensi/export/excel', [AbsensiController::class, 'exportExcel'])->name('absensi.export.excel');
    Route::post('/absensi/export/pdf', [AbsensiController::class, 'exportPdf'])->name('absensi.export.pdf');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\HariController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\LevelAksesController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\SPTController;
use App\Http\Controllers\StatusPegawaiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaktuKerjaController;
use App\Http\Controllers\AbsenMasukController;
use App\Http\Controllers\AbsenPulangController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LaporanAbsensiController;
use App\Http\Middleware\JWTMiddleware;
use App\Http\Middleware\JsonThrottleMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login'])->middleware(['throttle:5,1']);


Route::middleware([JWTMiddleware::class])->group(function () {
    // Autentication
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('reset-device', [AuthController::class, 'resetAccount']);

    // Data Absensi
    Route::apiResource('absen-masuk', AbsenMasukController::class);
    Route::get('/absen-masuk/user/{user_id}', [AbsenMasukController::class, 'getAbsenPulangByUser']);

    Route::apiResource('absen-pulang', AbsenPulangController::class);

    // Laporan Absensi
    Route::get('laporan-user', [LaporanAbsensiController::class, 'getlaporanByUser']);
    Route::get('laporan-cetak-user', [LaporanAbsensiController::class, 'getlaporanCetakByUser']);

    // Data SPT
    Route::apiResource('spt', SPTController::class);
    Route::get('/spt/user/{user_id}', [SPTController::class, 'getSptByUser']);
    Route::get('/spt/diterima-sdm/{id}', [SPTController::class, 'setStatusDiterima']);
    Route::get('/spt/ditolak-sdm/{id}', [SPTController::class, 'setStatusDitolak']);
    
    // Dashboard
    

    // Master Data
    //  --- DIVISI ---
    Route::apiResource('divisi', DivisiController::class);
    // --- LEVEL AKSES ---
    Route::apiResource('level-akses', LevelAksesController::class);
    // --- GENDER ---
    Route::apiResource('gender', GenderController::class);
    // --- STATUS PEGAWAI ---
    Route::apiResource('status-pegawai', StatusPegawaiController::class);
    // --- JABATAN ---
    Route::apiResource('jabatan', JabatanController::class);
    // --- DATA USER ---
    Route::apiResource('users', UserController::class);
    Route::put('reset-password', [UserController::class, 'resetPassword'])->middleware(['throttle:5,1']);
    Route::post('upload-photo', [UserController::class, 'uploadPhoto'])->middleware(['throttle:5,1']);
    // --- SHIFT KERJA ---
    Route::apiResource('shift', ShiftController::class);
    // --- HARI KERJA ---
    Route::apiResource('hari', HariController::class);
    // --- WAKTU KERJA ---
    Route::apiResource('waktu-kerja', WaktuKerjaController::class);
    Route::get('/waktu-kerja/shift/{shift_id}', [WaktuKerjaController::class, 'getByShift']);
    // --- LOKASI ---
    Route::apiResource('locations', LocationController::class);
});

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\GenderController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\LevelAksesController;
use App\Http\Controllers\StatusPegawaiController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\JWTMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware([JWTMiddleware::class])->group(function () {
    // Autentication
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Data User


    // Data Absensi


    // Data SPT


    // Data Perjalanan Dinas


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
    // --- USER ---
    Route::apiResource('users', UserController::class);
});

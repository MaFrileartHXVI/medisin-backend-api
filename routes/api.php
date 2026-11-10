<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CryptoController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\VisitController;
use App\Http\Middleware\DecryptPayload;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/login', 'login')->middleware(DecryptPayload::class);
});

Route::prefix('crypto')->controller(CryptoController::class)->group(function () {
    Route::post('/encrypt', 'encrypt');
    Route::post('/decrypt', 'decrypt');
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Sanctum Authenticated)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::get('/me', 'me');
        Route::post('/logout', 'logout');
    });

    Route::prefix('patients')->controller(PatientController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/next-mrn', 'getNextMedicalRecordNumber');
        Route::get('/by-mrn/{mrn}', 'findByMedicalRecordNumber');
    });

    Route::prefix('visits')->controller(VisitController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
    });
});

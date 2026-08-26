<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthCenterController;
use App\Http\Controllers\Api\V1\MedicalSessionController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\PatientController;
use App\Http\Controllers\Api\V1\TemporaryAccessCodeController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::post('/patients/{patient}/attention-codes', [TemporaryAccessCodeController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'register']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::patch('/users/{user}/restore', [UserController::class, 'restore']);

        Route::get('/organizations', [OrganizationController::class, 'index']);
        Route::post('/organizations', [OrganizationController::class, 'store']);
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
        Route::put('/organizations/{organization}', [OrganizationController::class, 'update']);
        Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy']);
        Route::patch('/organizations/{organization}/restore', [OrganizationController::class, 'restore']);

        Route::get('/health-centers', [HealthCenterController::class, 'index']);
        Route::post('/health-centers', [HealthCenterController::class, 'store']);
        Route::get('/health-centers/{healthCenter}', [HealthCenterController::class, 'show']);
        Route::put('/health-centers/{healthCenter}', [HealthCenterController::class, 'update']);
        Route::delete('/health-centers/{healthCenter}', [HealthCenterController::class, 'destroy']);
        Route::patch('/health-centers/{healthCenter}/restore', [HealthCenterController::class, 'restore']);

        Route::get('/units', [UnitController::class, 'index']);
        Route::post('/units', [UnitController::class, 'store']);
        Route::get('/units/{unit}', [UnitController::class, 'show']);
        Route::put('/units/{unit}', [UnitController::class, 'update']);
        Route::delete('/units/{unit}', [UnitController::class, 'destroy']);
        Route::patch('/units/{unit}/restore', [UnitController::class, 'restore']);

        Route::get('/patients', [PatientController::class, 'index']);
        Route::get('/patients/{patient}', [PatientController::class, 'show']);

        Route::post('/attention-codes/validate', [TemporaryAccessCodeController::class, 'validateCode']);

        Route::post('/medical-sessions', [MedicalSessionController::class, 'store']);
        Route::get('/medical-sessions/active', [MedicalSessionController::class, 'active']);
        Route::get('/medical-sessions/{medicalSession}', [MedicalSessionController::class, 'show']);
        Route::patch('/medical-sessions/{medicalSession}/stage', [MedicalSessionController::class, 'advance'])
            ->middleware('session.active');

        Route::post('/medical-sessions/{medicalSession}/close', [MedicalSessionController::class, 'close'])
            ->middleware('session.active');
    });
});
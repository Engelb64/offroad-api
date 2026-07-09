<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MaintenanceRecordController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class);

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::apiResource('vehicles', VehicleController::class);

        Route::get('/vehicles/{vehicle}/maintenance-records', [MaintenanceRecordController::class, 'index']);
        Route::post('/vehicles/{vehicle}/maintenance-records', [MaintenanceRecordController::class, 'store']);
        Route::get('/maintenance-records/{maintenanceRecord}', [MaintenanceRecordController::class, 'show']);
        Route::put('/maintenance-records/{maintenanceRecord}', [MaintenanceRecordController::class, 'update']);
        Route::delete('/maintenance-records/{maintenanceRecord}', [MaintenanceRecordController::class, 'destroy']);
    });
});

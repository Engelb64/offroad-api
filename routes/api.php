<?php

use App\Http\Controllers\Api\V1\Admin\UserRoleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\MaintenanceRecordController;
use App\Http\Controllers\Api\V1\MyWorkshopController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class);

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::post('/me/become-workshop-owner', [ProfileController::class, 'becomeWorkshopOwner']);

        Route::apiResource('vehicles', VehicleController::class);

        Route::get('/vehicles/{vehicle}/maintenance-records', [MaintenanceRecordController::class, 'index']);
        Route::post('/vehicles/{vehicle}/maintenance-records', [MaintenanceRecordController::class, 'store']);
        Route::get('/maintenance-records/{maintenanceRecord}', [MaintenanceRecordController::class, 'show']);
        Route::put('/maintenance-records/{maintenanceRecord}', [MaintenanceRecordController::class, 'update']);
        Route::delete('/maintenance-records/{maintenanceRecord}', [MaintenanceRecordController::class, 'destroy']);

        Route::middleware('role:workshop_owner,admin')->prefix('my')->group(function () {
            Route::get('/workshops', [MyWorkshopController::class, 'index']);
            Route::post('/workshops', [MyWorkshopController::class, 'store']);
            Route::get('/workshops/{workshop}', [MyWorkshopController::class, 'show']);
            Route::put('/workshops/{workshop}', [MyWorkshopController::class, 'update']);
            Route::post('/workshops/{workshop}/submit', [MyWorkshopController::class, 'submit']);
        });

        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::patch('/users/{user}/role', [UserRoleController::class, 'update']);
        });
    });
});

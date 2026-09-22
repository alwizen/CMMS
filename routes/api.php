<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\MaintenancePlanController;
use App\Http\Controllers\Api\MaintenanceRequestController;
use App\Http\Controllers\Api\MaintenanceScheduleController;
use App\Http\Controllers\Api\WorkOrderController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/maintenance-plans', [MaintenancePlanController::class, 'index']);
    Route::get('/maintenance-plans/{id}', [MaintenancePlanController::class, 'show']);

    Route::get('/maintenance-schedules', [MaintenanceScheduleController::class, 'index']);
    Route::get('/maintenance-schedules/{id}', [MaintenanceScheduleController::class, 'show']);

    Route::get('/work-orders', [WorkOrderController::class, 'index']);
    Route::get('/work-orders/{id}', [WorkOrderController::class, 'show']);
    Route::put('/work-orders/{id}', [WorkOrderController::class, 'update']);
    Route::put('/work-orders/{workOrderId}/activities/{activityId}', [WorkOrderController::class, 'updateActivity']);

    Route::get('/equipment', [EquipmentController::class, 'index']);
    Route::get('/equipment/{id}', [EquipmentController::class, 'show']);

    Route::get('/maintenance-requests', [MaintenanceRequestController::class, 'index']);
    Route::get('/maintenance-requests/{id}', [MaintenanceRequestController::class, 'show']);
    Route::post('/maintenance-requests', [MaintenanceRequestController::class, 'store']);
    Route::put('/maintenance-requests/{id}', [MaintenanceRequestController::class, 'update']);
    Route::delete('/maintenance-requests/{id}', [MaintenanceRequestController::class, 'destroy']);
});

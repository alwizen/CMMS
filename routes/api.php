<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DocumentFileController;
use App\Http\Controllers\Api\DowntimeController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\HousekeepingAreaController;
use App\Http\Controllers\Api\HousekeepingPlanController;
use App\Http\Controllers\Api\MaintenanceCalendarController;
use App\Http\Controllers\Api\MaintenancePlanController;
use App\Http\Controllers\Api\MaintenanceRequestController;
use App\Http\Controllers\Api\MaintenanceScheduleController;
use App\Http\Controllers\Api\MeterLogController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkOrderController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Dokumen: hanya bisa diunduh melalui URL bertanda tangan berumur pendek.
Route::get('/documents/{document}/file', [DocumentFileController::class, 'show'])
    ->middleware('signed')
    ->name('documents.file');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/maintenance-plans', [MaintenancePlanController::class, 'index']);
    Route::get('/maintenance-plans/{id}', [MaintenancePlanController::class, 'show']);

    Route::get('/maintenance-calendar', [MaintenanceCalendarController::class, 'index']);
    Route::get('/maintenance-schedules', [MaintenanceScheduleController::class, 'index']);
    Route::get('/maintenance-schedules/{id}', [MaintenanceScheduleController::class, 'show']);

    Route::get('/work-orders', [WorkOrderController::class, 'index']);
    Route::get('/work-orders/{id}', [WorkOrderController::class, 'show']);
    Route::put('/work-orders/{id}', [WorkOrderController::class, 'update']);
    Route::put('/work-orders/{workOrderId}/activities/{activityId}', [WorkOrderController::class, 'updateActivity']);
    Route::post('/work-orders/{workOrderId}/activities/{activityId}/photos', [WorkOrderController::class, 'uploadActivityPhotos']);
    Route::delete('/work-orders/{workOrderId}/activities/{activityId}/photos', [WorkOrderController::class, 'deleteActivityPhoto']);

    Route::get('/equipment', [EquipmentController::class, 'index']);
    Route::get('/equipment/{id}', [EquipmentController::class, 'show']);

    Route::get('/maintenance-requests', [MaintenanceRequestController::class, 'index']);
    Route::get('/maintenance-requests/{id}', [MaintenanceRequestController::class, 'show']);
    Route::post('/maintenance-requests', [MaintenanceRequestController::class, 'store']);
    Route::put('/maintenance-requests/{id}', [MaintenanceRequestController::class, 'update']);
    Route::delete('/maintenance-requests/{id}', [MaintenanceRequestController::class, 'destroy']);

    Route::get('/meter-logs', [MeterLogController::class, 'index']);
    Route::get('/meter-logs/{id}', [MeterLogController::class, 'show']);
    Route::post('/meter-logs', [MeterLogController::class, 'store']);
    Route::get('/equipment/{equipmentId}/meter-logs', [MeterLogController::class, 'byEquipment']);

    Route::get('/downtime', [DowntimeController::class, 'index']);

    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/documents/{id}', [DocumentController::class, 'show']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    Route::get('/users/{id}', [UserController::class, 'show']);

    Route::get('/housekeeping-plans', [HousekeepingPlanController::class, 'index']);
    Route::post('/housekeeping-plans', [HousekeepingPlanController::class, 'store']);
    Route::get('/housekeeping-plans/{id}', [HousekeepingPlanController::class, 'show']);
    Route::put('/housekeeping-plans/{planId}/tasks/{taskId}', [HousekeepingPlanController::class, 'updateTask']);
    Route::post('/housekeeping-plans/{planId}/tasks', [HousekeepingPlanController::class, 'addTask']);
    Route::delete('/housekeeping-plans/{planId}/tasks/{taskId}', [HousekeepingPlanController::class, 'deleteTask']);
    Route::post('/housekeeping-plans/{planId}/manpowers', [HousekeepingPlanController::class, 'addManpowers']);
    Route::post('/housekeeping-plans/{planId}/tasks/{taskId}/photos', [HousekeepingPlanController::class, 'uploadTaskPhotos']);
    Route::delete('/housekeeping-plans/{planId}/tasks/{taskId}/photos', [HousekeepingPlanController::class, 'deleteTaskPhoto']);

    Route::get('/housekeeping-areas', [HousekeepingAreaController::class, 'index']);
    Route::get('/housekeeping-areas/{id}/manpowers', [HousekeepingAreaController::class, 'manpowers']);
    Route::get('/housekeeping-areas/{id}/facilities', [HousekeepingAreaController::class, 'facilities']);
});

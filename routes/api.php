<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\DealController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\CustomerTransferController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum', 'verified', 'organization'])->group(function (): void {
    Route::get('customers/export', [CustomerTransferController::class, 'export'])->middleware('permission:crm.manage');
    Route::post('customers/import', [CustomerTransferController::class, 'import'])->middleware('permission:crm.manage');
    Route::apiResource('customers', CustomerController::class)->parameters(['customers' => 'record'])->middleware('permission:crm.manage');
    Route::apiResource('leads', LeadController::class)->parameters(['leads' => 'record'])->middleware('permission:crm.manage');
    Route::apiResource('deals', DealController::class)->parameters(['deals' => 'record'])->middleware('permission:crm.manage');
    Route::apiResource('tasks', TaskController::class)->parameters(['tasks' => 'record'])->middleware('permission:tasks.manage');
    Route::apiResource('appointments', AppointmentController::class)->parameters(['appointments' => 'record'])->middleware('permission:booking.manage');
});

<?php

use App\Http\Controllers\CrmCustomerController;
use App\Http\Controllers\CustomerTransferController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ModuleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');
Route::view('api/documentation', 'api-docs')->name('api.docs');

Route::post('locale', LocaleController::class)->name('locale.update');

Route::middleware(['auth', 'verified', 'organization'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('modules/{module}', ModuleController::class)
        ->whereIn('module', ['team', 'crm', 'booking', 'communications', 'documents', 'finance', 'risk'])
        ->name('modules.show');
    Route::post('crm/customers', [CrmCustomerController::class, 'store'])
        ->middleware('permission:crm.manage')->name('crm.customers.store');
    Route::get('crm/customers/export', [CustomerTransferController::class, 'export'])
        ->middleware('permission:crm.manage')->name('crm.customers.export');
    Route::post('crm/customers/import', [CustomerTransferController::class, 'import'])
        ->middleware('permission:crm.manage')->name('crm.customers.import');
    Route::delete('crm/customers/{customer}', [CrmCustomerController::class, 'destroy'])
        ->middleware('permission:crm.manage')->name('crm.customers.destroy');
});

require __DIR__.'/settings.php';

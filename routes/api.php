<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\MeasurementUnitController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\Platform\CompanyController as PlatformCompanyController;
use App\Http\Controllers\Api\Platform\PermissionController as PlatformPermissionController;
use App\Http\Controllers\Api\Platform\RoleController as PlatformRoleController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/profile', [AuthController::class, 'updateProfile']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::middleware('permission:platform.manage')->prefix('platform')->group(function () {
        Route::get('companies', [PlatformCompanyController::class, 'index']);
        Route::post('companies', [PlatformCompanyController::class, 'store']);
        Route::get('companies/{company}', [PlatformCompanyController::class, 'show']);
        Route::patch('companies/{company}', [PlatformCompanyController::class, 'update']);
        Route::post('companies/{company}/logo', [PlatformCompanyController::class, 'uploadLogo']);
        Route::delete('companies/{company}/logo', [PlatformCompanyController::class, 'deleteLogo']);

        Route::get('permissions', [PlatformPermissionController::class, 'index']);
        Route::post('permissions', [PlatformPermissionController::class, 'store']);
        Route::delete('permissions/{permission}', [PlatformPermissionController::class, 'destroy']);

        Route::get('roles', [PlatformRoleController::class, 'index']);
        Route::post('roles', [PlatformRoleController::class, 'store']);
        Route::patch('roles/{role}', [PlatformRoleController::class, 'update']);
        Route::delete('roles/{role}', [PlatformRoleController::class, 'destroy']);
    });

    Route::get('platform/companies/{company}/logo', [PlatformCompanyController::class, 'logo']);
});

Route::middleware(['auth:api', 'company'])->group(function () {
    Route::middleware('permission:users.manage')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    Route::get('clients', [ClientController::class, 'index']);
    Route::post('clients', [ClientController::class, 'store']);
    Route::get('clients/import/template', [ClientController::class, 'importTemplate']);
    Route::post('clients/import', [ClientController::class, 'import']);
    Route::get('clients/{client}', [ClientController::class, 'show']);
    Route::patch('clients/{client}', [ClientController::class, 'update']);
    Route::delete('clients/{client}', [ClientController::class, 'destroy']);

    Route::get('measurement-units', [MeasurementUnitController::class, 'index']);

    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store']);
    Route::get('products/import/template', [ProductController::class, 'importTemplate']);
    Route::post('products/import', [ProductController::class, 'import']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::patch('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);

    Route::get('reports/dashboard', [ReportController::class, 'dashboard']);

    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::post('invoices', [InvoiceController::class, 'store']);
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);
    Route::patch('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);
});

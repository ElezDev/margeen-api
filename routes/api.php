<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Clientes\ClienteController;
use App\Http\Controllers\Facturas\FacturaController;
use App\Http\Controllers\Reportes\ReporteController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::patch('/profile', [AuthController::class, 'updateProfile']);
    });
});

Route::middleware(['auth:api', 'company'])->group(function () {
    Route::middleware('permission:users.manage')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    Route::get('clients', [ClientController::class, 'index']);
    Route::post('clients', [ClientController::class, 'store']);
    Route::get('clients/{client}', [ClientController::class, 'show']);
    Route::patch('clients/{client}', [ClientController::class, 'update']);
    Route::delete('clients/{client}', [ClientController::class, 'destroy']);

    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::patch('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);

    Route::get('reports/dashboard', [ReportController::class, 'dashboard']);

    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::post('invoices', [InvoiceController::class, 'store']);
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);
    Route::patch('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);
Route::middleware('auth:api')->group(function () {
    
    // Productos 
    Route::get('/products', [ProductController::class, 'index']);          
    Route::post('/products', [ProductController::class, 'store']);        
    Route::get('/products/{id}', [ProductController::class, 'show']);     
    Route::patch('/products/{id}', [ProductController::class, 'update']);   
    Route::delete('/products/{id}', [ProductController::class, 'destroy']); 

    // Clientes
    Route::apiResource('clients', ClienteController::class);

    // Facturas
    Route::apiResource('invoices', FacturaController::class);
    Route::get('invoices/{id}/pdf', [FacturaController::class, 'generatePdf']);
    Route::patch('invoices/{id}/status', [FacturaController::class, 'updateStatus']); 

    // Endpoint de Reportes para el Dashboard de la App Móvil
    Route::get('reports/dashboard', [ReporteController::class, 'getDashboardStats']);
    
});
});         
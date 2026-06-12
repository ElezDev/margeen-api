<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Products\ProductController; 
use App\Http\Controllers\Clientes\ClienteController;
use App\Http\Controllers\Facturas\FacturaController;
use App\Http\Controllers\Reportes\ReporteController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

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

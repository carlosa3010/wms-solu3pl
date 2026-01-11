<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\WarehouseApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 1. RUTAS PÚBLICAS (No requieren Token)
Route::post('/login', [AuthApiController::class, 'login']);

// 2. RUTAS PROTEGIDAS (Requieren Token Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth & User Info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthApiController::class, 'logout']);

    // --- WAREHOUSE APP (PDA) ---
    
    // Dashboard & Stats
    Route::get('/warehouse/stats', [WarehouseApiController::class, 'getDashboardStats']);
    
    // Consultas (Lookups)
    Route::get('/warehouse/products/{barcode}', [WarehouseApiController::class, 'getProductByBarcode']);
    Route::get('/warehouse/locations/{code}', [WarehouseApiController::class, 'getLocationContent']);

    // Operaciones: Picking
    Route::get('/warehouse/picking/orders', [WarehouseApiController::class, 'getPendingPickingOrders']);
    Route::get('/warehouse/picking/orders/{id}', [WarehouseApiController::class, 'getPickingOrderDetails']);
    Route::post('/warehouse/picking/scan', [WarehouseApiController::class, 'processPickingScan']);
    Route::post('/warehouse/picking/finalize/{id}', [WarehouseApiController::class, 'finalizePicking']);

    // Operaciones: Recepción (ASN)
    Route::get('/warehouse/reception/asns', [WarehouseApiController::class, 'getPendingASNs']);
    Route::get('/warehouse/reception/asns/{id}', [WarehouseApiController::class, 'getASNDetails']);
    Route::post('/warehouse/reception/scan', [WarehouseApiController::class, 'processReceptionScan']);
    Route::post('/warehouse/reception/finalize/{id}', [WarehouseApiController::class, 'finalizeReception']);

    // Operaciones: Inventario
    Route::post('/warehouse/inventory/move', [WarehouseApiController::class, 'moveInventory']);
    Route::post('/warehouse/inventory/adjust', [WarehouseApiController::class, 'adjustStock']);
});
<?php

use App\Http\Controllers\Seguridad\AuthController;
use App\Http\Controllers\Clientes\Categoria_ClientesController;
use App\Http\Controllers\Productos_Proveedores\Categoria_ProveedoresController;
use App\Http\Controllers\Productos_Proveedores\CategoriaController;
use App\Http\Controllers\Productos_Proveedores\ProductosController;
use App\Http\Controllers\Productos_Proveedores\ProveedoresController;
use App\Http\Controllers\Inventario\MovimientosController;
use App\Http\Controllers\Inventario\AlertaStockController;
use App\Http\Controllers\Inventario\ConfiguracionAlertaController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::apiResource('categorias',             CategoriaController::class);
Route::apiResource('categorias-clientes',    Categoria_ClientesController::class);
Route::apiResource('categorias-proveedores', Categoria_ProveedoresController::class);
Route::apiResource('proveedores',            ProveedoresController::class);
Route::get('productos/create',               [ProductosController::class, 'create']);
Route::apiResource('productos',              ProductosController::class);

Route::prefix('inventario')->group(function(){
    Route::apiResource('movimientos',      MovimientosController::class);
    Route::apiResource('alertas',           AlertaStockController::class);
    Route::apiResource('configuracion',     ConfiguracionAlertaController::class);
    Route::get('productos/lista', [ProductosController::class, 'index']);
});


Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Google OAuth
Route::get('/auth/google/redirect',  [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback',  [AuthController::class, 'handleGoogleCallback']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function() {
    Route::post('/logout', [AuthController::class, 'logout']);
});

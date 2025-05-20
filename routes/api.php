<?php

use App\Http\Controllers\Inventario\TiposMovimientosController;
use App\Http\Controllers\Seguridad\AuthController;
use App\Http\Controllers\Clientes\Categoria_ClientesController;
use App\Http\Controllers\Productos_Proveedores\Categoria_ProveedoresController;
use App\Http\Controllers\Productos_Proveedores\CategoriaController;
use App\Http\Controllers\Productos_Proveedores\ProductosController;
use App\Http\Controllers\Productos_Proveedores\ProveedoresController;
use App\Http\Controllers\Inventario\MovimientosController;
use App\Http\Controllers\Inventario\AlertaStockController;
use App\Http\Controllers\Inventario\ConfiguracionAlertaController;
use App\Http\Controllers\Inventario\NotificacionAlertaController;
use Illuminate\Support\Facades\Route;


Route::apiResource('categorias', CategoriaController::class);
Route::apiResource('categorias-clientes', Categoria_ClientesController::class);
Route::apiResource('categorias-proveedores', Categoria_ProveedoresController::class);
Route::apiResource('proveedores', ProveedoresController::class);

// Rutas de Productos
Route::get('productos/create', [ProductosController::class, 'create'])->name('productos.create_options'); // Para obtener datos para formularios de creación
Route::get('productos/buscar-por-nombre', [ProductosController::class, 'searchByName'])->name('productos.searchByName'); // Para búsqueda por nombre
Route::apiResource('productos', ProductosController::class);


Route::get('tipos-movimientos', [TiposMovimientosController::class, 'index'])->name('tipos-movimientos.index');
Route::get('tipos-movimientos/{id}', [TiposMovimientosController::class, 'show'])->name('tipos-movimientos.show');


// Grupo de rutas para el módulo de Inventario
Route::prefix('inventario')->name('inventario.')->group(function () {
    Route::apiResource('movimientos', MovimientosController::class);

    Route::apiResource('configuracion-alertas', ConfiguracionAlertaController::class)->names('configuracionAlertas');

    Route::apiResource('alertas-stock', AlertaStockController::class)
        ->except(['store'])
        ->names('alertasStock');
    Route::post('alertas-stock/manual', [AlertaStockController::class, 'storeManualAlerta'])
        ->name('alertasStock.storeManual');

    Route::apiResource('notificaciones-alertas', NotificacionAlertaController::class)
        ->except(['destroy'])
        ->names('notificacionesAlertas');

    Route::get('productos/lista', [ProductosController::class, 'index'])->name('productos.lista');
});



Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');


Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');


// Rutas protegidas con Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', [AuthController::class, 'getUserInfo'])->name('user.info');
});

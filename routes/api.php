<?php

use App\Http\Controllers\Clientes\Categoria_ClientesController;
use App\Http\Controllers\Productos_Proveedores\Categoria_ProveedoresController;
use App\Http\Controllers\Productos_Proveedores\CategoriaController;
use App\Http\Controllers\Productos_Proveedores\ProductosController;
use App\Http\Controllers\Productos_Proveedores\ProveedoresController;
use App\Http\Controllers\Seguridad\AuthController;
use Illuminate\Support\Facades\Route;

Route::apiResource('categorias',             CategoriaController::class);
Route::apiResource('categorias-clientes',    Categoria_ClientesController::class);
Route::apiResource('categorias-proveedores', Categoria_ProveedoresController::class);
Route::apiResource('proveedores', ProveedoresController::class);
Route::get('productos/create',    [ProductosController::class, 'create']);
Route::apiResource('productos',   ProductosController::class);
Route::post('/login', [AuthController::class, 'login']);

<?php

use App\Http\Controllers\Seguridad\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Productos_Proveedores\CategoriaController;

Route::apiResource('categorias', CategoriaController::class);
Route::post('/login', [AuthController::class, 'login']);

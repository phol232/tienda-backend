<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Seguridad\AuthController;
use App\Http\Controllers\Clientes\Categoria_ClientesController;
use App\Http\Controllers\Clientes\ClientesController;
use App\Http\Controllers\Productos_Proveedores\Categoria_ProveedoresController;
use App\Http\Controllers\Productos_Proveedores\CategoriaController;
use App\Http\Controllers\Productos_Proveedores\ProductosController;
use App\Http\Controllers\Productos_Proveedores\ProveedoresController;
use App\Http\Controllers\Inventario\TiposMovimientosController;
use App\Http\Controllers\Inventario\MovimientosController;
use App\Http\Controllers\Inventario\ConfiguracionAlertaController;
use App\Http\Controllers\Inventario\AlertaStockController;
use App\Http\Controllers\Inventario\NotificacionAlertaController;
use App\Http\Controllers\Seguridad\UsuariosController;
use App\Http\Controllers\Ventas_Pagos\BoletasController;
use App\Http\Controllers\Ventas_Pagos\FacturacionController;
use App\Http\Controllers\Ventas_Pagos\FacturasController;
use App\Http\Controllers\Ventas_Pagos\MercadoPagoController;
use App\Http\Controllers\Ventas_Pagos\MetodosPagoController;
use App\Http\Controllers\Pedidos\PedidosController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// —— Catálogos y CRUDs ——
Route::apiResource('categorias', CategoriaController::class);
Route::apiResource('categorias-clientes', Categoria_ClientesController::class);
Route::apiResource('categorias-proveedores', Categoria_ProveedoresController::class);
Route::apiResource('proveedores', ProveedoresController::class);
Route::apiResource('clientes', ClientesController::class);

Route::get('productos/create', [ProductosController::class, 'create'])
    ->name('productos.create_options');
Route::get('productos/buscar-por-nombre', [ProductosController::class, 'searchByName'])
    ->name('productos.searchByName');
Route::apiResource('productos', ProductosController::class);

Route::get('tipos-movimientos', [TiposMovimientosController::class, 'index'])
    ->name('tipos-movimientos.index');
Route::get('tipos-movimientos/{id}', [TiposMovimientosController::class, 'show'])
    ->name('tipos-movimientos.show');

Route::prefix('inventario')->name('inventario.')->group(function () {
    Route::apiResource('movimientos', MovimientosController::class);
    Route::apiResource('configuracion-alertas', ConfiguracionAlertaController::class)
        ->names('configuracionAlertas');
    Route::apiResource('alertas-stock', AlertaStockController::class)
        ->except(['store'])
        ->names('alertasStock');
    Route::post('alertas-stock/manual', [AlertaStockController::class, 'storeManualAlerta'])
        ->name('alertasStock.storeManual');
    Route::apiResource('notificaciones-alertas', NotificacionAlertaController::class)
        ->except(['destroy'])
        ->names('notificacionesAlertas');
    Route::get('productos/lista', [ProductosController::class, 'index'])
        ->name('productos.lista');
});

Route::apiResource('metodos-pago', MetodosPagoController::class);
Route::apiResource('pedidos', PedidosController::class);
Route::put('pedidos/{ped_id}/estado', [PedidosController::class, 'updateEstado']);

Route::apiResource('boletas', BoletasController::class)
    ->only(['index','store','show']);
Route::get('boletas/{id}/pdf', [BoletasController::class, 'pdf']);
Route::patch('boletas/{id}/cancelar', [BoletasController::class, 'cancelar'])
    ->name('boletas.cancelar');
Route::post('boletas/procesar-microservicio', [BoletasController::class,'procesarConMicroservicio']);
Route::get('boletas/payment/{paymentId}', [BoletasController::class,'buscarPorPaymentId']);

Route::apiResource('facturas', FacturasController::class);
Route::post('facturacion/emitir', [FacturacionController::class, 'emitirBoleta']);
Route::post('facturacion/emitir-frontend', [FacturacionController::class, 'emitirBoletaFrontend']);
Route::post('facturacion/pdf', [FacturacionController::class, 'generarPdf']);
Route::post('facturacion/emitir-replica-postman', [FacturacionController::class, 'emitirBoletaReplicaPostman']);

Route::get('perfil/{id}', [UsuariosController::class, 'show']);
Route::put('perfil/{id}', [UsuariosController::class, 'update']);

// —— Autenticación ——
// Login / Register
Route::post('login',    [AuthController::class, 'login'])->name('login');
Route::post('register', [AuthController::class, 'register'])->name('register');

// Google OAuth
Route::get('auth/google/redirect', [AuthController::class, 'redirectToGoogle'])
    ->name('auth.google.redirect');
Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback'])
    ->name('auth.google.callback');

// Microsoft OAuth
Route::get('auth/microsoft/redirect', [AuthController::class, 'redirectToMicrosoft'])
    ->name('auth.microsoft.redirect');
Route::get('auth/microsoft/callback', [AuthController::class, 'handleMicrosoftCallback'])
    ->name('auth.microsoft.callback');


// Rutas protegidas por Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout',   [AuthController::class, 'logout'])->name('logout');
    Route::get('user',      [AuthController::class, 'getUserInfo'])->name('user.info');
});

Route::get('/mail-check', function () {
    Mail::raw('✅ Prueba desde web', function($m){
        $m->to('ph2309.t@gmail.com')
          ->subject('Prueba Mailgun Web');
    });
    return 'OK';
});

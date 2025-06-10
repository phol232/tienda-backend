<?php

namespace App\Http\Controllers\Ventas_Pagos;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ventas_Pagos\Boletas;
use App\Models\Pedidos\Pedidos;
use App\Models\Productos_Proveedores\Productos;

class BoletasController extends Controller
{
    // Listar boletas con relaciones
    public function index()
    {
        try {
            $boletas = Boletas::with([
                'metodosPago',
                'pedido.detalles.producto',
                'pedido.cliente'
            ])->orderBy('boleta_fecha', 'desc')->get();

            return response()->json($boletas, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las boletas.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Crear boleta y asociar pagos, descuenta stock
    public function store(Request $request)
    {
        $request->validate([
            'ped_id'              => 'required|exists:Pedidos,ped_id',
            'boleta_numero'       => 'required|string|max:20',
            'boleta_notas'        => 'nullable|string',
            'payment_id'          => 'nullable|string', // Para MercadoPago
            'pagos'               => 'required|array|min:1',
            'pagos.*.met_id'      => 'required|exists:Metodos_Pago,met_id',
            'pagos.*.monto'       => 'required|numeric|min:0.01',
            'pagos.*.fecha_pago'  => 'required|date',
            'pagos.*.nota_pago'   => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $pedido = Pedidos::with('detalles.producto')->findOrFail($request->ped_id);

            // Generar id único (máximo 15 caracteres)
            $boleta_id = substr(uniqid('BOL-'), 0, 15);

            $boleta = new Boletas([
                'boleta_id'        => $boleta_id,
                'boleta_numero'    => $request->boleta_numero,
                'boleta_fecha'     => now(),
                'boleta_subtotal'  => $pedido->ped_subtotal,
                'boleta_impuestos' => $pedido->ped_impuestos,
                'boleta_descuento' => $pedido->ped_descuento,
                'boleta_total'     => $pedido->ped_total,
                'boleta_estado'    => 'Emitido',
                'boleta_notas'     => $request->boleta_notas,
                'payment_id'       => $request->payment_id, // Guardar payment_id de MercadoPago
                'ped_id'           => $pedido->ped_id,
            ]);
            $boleta->save();

            // Registrar métodos de pago
            foreach ($request->pagos as $pago) {
                $boleta->metodosPago()->attach($pago['met_id'], [
                    'monto'          => $pago['monto'],
                    'referencia'     => $pago['nota_pago'] ?? null,
                    'fecha_registro' => $pago['fecha_pago'] ?? now(),
                ]);
            }

            // Descontar stock
            foreach ($pedido->detalles as $detalle) {
                $producto = $detalle->producto;
                if ($producto->pro_stock < $detalle->det_cantidad) {
                    throw new \Exception("Stock insuficiente para el producto {$producto->pro_nombre}");
                }
                $producto->pro_stock -= $detalle->det_cantidad;
                $producto->save();
            }

            DB::commit();

            // Cargar la boleta con todas sus relaciones para devolverla completa
            $boletaCompleta = Boletas::with([
                'metodosPago',
                'pedido.detalles.producto',
                'pedido.cliente'
            ])->find($boleta->boleta_id);

            return response()->json([
                'message'    => 'Boleta registrada exitosamente',
                'boleta_id'  => $boleta->boleta_id,
                'boleta_numero' => $boleta->boleta_numero,
                'boleta'     => $boletaCompleta
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar la boleta.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Mostrar una boleta específica
    public function show($id)
    {
        try {
            $boleta = Boletas::with([
                'metodosPago',
                'pedido.detalles.producto',
                'pedido.cliente'
            ])->findOrFail($id);

            return response()->json($boleta, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Boleta no encontrada.',
                'error'   => $e->getMessage()
            ], 404);
        }
    }

    // Cancelar boleta y devolver stock
    public function cancelar($id)
    {
        DB::beginTransaction();

        try {
            $boleta = Boletas::with([
                'pedido.detalles.producto'
            ])->findOrFail($id);

            if ($boleta->boleta_estado === 'Cancelado') {
                return response()->json([
                    'message' => 'La boleta ya está en estado Cancelado.'
                ], 400);
            }

            // Devolver stock
            foreach ($boleta->pedido->detalles as $detalle) {
                $producto = $detalle->producto;
                $producto->pro_stock += $detalle->det_cantidad;
                $producto->save();
            }

            $boleta->boleta_estado = 'Cancelado';
            $boleta->save();

            DB::commit();

            return response()->json([
                'message' => 'Boleta cancelada correctamente.',
                'boleta_id' => $boleta->boleta_id
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al cancelar la boleta.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Generar PDF usando el microservicio
    public function pdf($id)
    {
        try {
            $boleta = Boletas::with([
                'metodosPago',
                'pedido.detalles.producto',
                'pedido.cliente'
            ])->findOrFail($id);

            // Buscar payment_id
            $paymentId = $boleta->payment_id ?? null;

            if (!$paymentId) {
                return response()->json([
                    'message' => 'No se encuentra el payment_id para la boleta. Esta boleta no se procesó con MercadoPago.'
                ], 400);
            }

            // Preparar datos para el microservicio
            $boletaData = [
                'boleta_id' => $boleta->boleta_id,
                'boleta_numero' => $boleta->boleta_numero,
                'boleta_fecha' => $boleta->boleta_fecha,
                'boleta_total' => $boleta->boleta_total,
                'boleta_subtotal' => $boleta->boleta_subtotal,
                'boleta_impuestos' => $boleta->boleta_impuestos,
                'boleta_descuento' => $boleta->boleta_descuento,
                'cliente' => $boleta->pedido->cliente ?? null,
                'detalles' => $boleta->pedido->detalles ?? [],
                'metodos_pago' => $boleta->metodosPago ?? []
            ];

            // Llamar al microservicio (ajusta la URL según tu configuración)
            $microservicioUrl = env('MICROSERVICIO_FACTURACION_URL', 'http://127.0.0.1:3000');

            $response = Http::timeout(30)->post($microservicioUrl . '/facturar-pago', [
                'payment_id' => $paymentId,
                'boleta' => $boletaData,
            ]);

            if ($response->successful()) {
                return response($response->body(), 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="boleta-'.$boleta->boleta_numero.'.pdf"');
            } else {
                return response()->json([
                    'message' => 'Error del microservicio de facturación.',
                    'error' => $response->body()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'No se pudo generar el PDF de la boleta.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Procesar con microservicio (llamado desde el frontend)
    public function procesarConMicroservicio(Request $request)
    {
        try {
            $paymentId = $request->payment_id;
            $boleta = $request->boleta;

            if (!$paymentId || !$boleta) {
                return response()->json([
                    'error' => 'payment_id y boleta son requeridos'
                ], 400);
            }

            // Llamar al microservicio
            $microservicioUrl = env('MICROSERVICIO_FACTURACION_URL', 'http://127.0.0.1:3000');

            $response = Http::timeout(30)->post($microservicioUrl . '/procesar', [
                'payment_id' => $paymentId,
                'boleta_data' => $boleta
            ]);

            if ($response->successful()) {
                return response($response->body())
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="boleta-'.$boleta['boleta_numero'].'.pdf"');
            }

            return response()->json([
                'error' => 'Error en microservicio',
                'details' => $response->body()
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Buscar boleta por payment_id (útil para el microservicio)
    public function buscarPorPaymentId($paymentId)
    {
        try {
            $boleta = Boletas::with([
                'metodosPago',
                'pedido.detalles.producto',
                'pedido.cliente'
            ])->where('payment_id', $paymentId)->first();

            if (!$boleta) {
                return response()->json([
                    'message' => 'No se encontró boleta con ese payment_id'
                ], 404);
            }

            return response()->json($boleta, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al buscar la boleta.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}

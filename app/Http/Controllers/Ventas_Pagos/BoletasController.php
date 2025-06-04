<?php

namespace App\Http\Controllers\Ventas_Pagos;

use App\Http\Controllers\Controller;
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
                'pedido.detalles'
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
            'pagos'               => 'required|array|min:1',
            'pagos.*.met_id'      => 'required|exists:Metodos_Pago,met_id',
            'pagos.*.monto'       => 'required|numeric|min:0.01',
            'pagos.*.fecha_pago'  => 'required|date',
            'pagos.*.nota_pago'   => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $pedido = Pedidos::with('detalles')->findOrFail($request->ped_id);

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
                $producto = Productos::findOrFail($detalle->prod_id);
                if ($producto->pro_stock < $detalle->det_cantidad) {
                    throw new \Exception("Stock insuficiente para el producto {$producto->pro_nombre}");
                }
                $producto->pro_stock -= $detalle->det_cantidad;
                $producto->save();
            }

            DB::commit();
            return response()->json([
                'message'    => 'Boleta registrada exitosamente',
                'boleta_id'  => $boleta->boleta_id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar la boleta.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Eliminar boleta y restaurar stock
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $boleta = Boletas::with('pedido.detalles')->findOrFail($id);
            $pedido = $boleta->pedido;

            // Restaurar stock de los productos
            foreach ($pedido->detalles as $detalle) {
                $producto = Productos::findOrFail($detalle->prod_id);
                $producto->pro_stock += $detalle->det_cantidad;
                $producto->save();
            }

            // Eliminar relaciones en la pivote
            $boleta->metodosPago()->detach();

            // Eliminar boleta
            $boleta->delete();

            DB::commit();
            return response()->json([
                'message' => 'Boleta eliminada y stock restaurado correctamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar la boleta.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}

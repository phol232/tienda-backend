<?php

namespace App\Http\Controllers\Ventas_Pagos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ventas_Pagos\Facturas;
use App\Models\Pedidos\Pedidos;
use App\Models\Productos_Proveedores\Productos;

class FacturasController extends Controller
{
    public function index()
    {
        try {
            $facturas = Facturas::with([
                'metodosPago',
                'pedido.detalles'
            ])->orderBy('factura_fecha', 'desc')->get();

            return response()->json($facturas, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener las facturas.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'ped_id'            => 'required|exists:Pedidos,ped_id',
            'pagos'             => 'required|array|min:1',
            'pagos.*.met_id'    => 'required|exists:Metodos_Pago,met_id',
            'pagos.*.monto'     => 'required|numeric|min:0.01',
            'pagos.*.fecha_pago'=> 'required|date',
            'notas'             => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $pedido = Pedidos::with('detalles')->findOrFail($request->ped_id);

            // Crear la factura
            $factura = new Facturas([
                'factura_id'       => uniqid('FAC-'),
                'factura_numero'   => null, // ajusta si usas correlativo
                'factura_fecha'    => now(),
                'factura_subtotal' => $pedido->ped_subtotal,
                'factura_impuestos'=> $pedido->ped_impuestos,
                'factura_descuento'=> $pedido->ped_descuento,
                'factura_total'    => $pedido->ped_total,
                'factura_estado'   => 'Emitido',
                'factura_notas'    => $request->notas,
                'ped_id'           => $pedido->ped_id,
            ]);
            $factura->save();

            // Registrar métodos de pago en tabla pivote
            foreach ($request->pagos as $pago) {
                $factura->metodosPago()->attach($pago['met_id'], [
                    'monto'      => $pago['monto'],
                    'fecha_pago' => $pago['fecha_pago'],
                    'nota_pago'  => $pago['nota_pago'] ?? null,
                ]);
            }

            // Descontar stock de productos del pedido
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
                'message' => 'Factura registrada exitosamente',
                'factura_id' => $factura->factura_id
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar la factura.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar factura y restaurar stock.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $factura = Facturas::with('pedido.detalles')->findOrFail($id);
            $pedido = $factura->pedido;

            // Restaurar stock de los productos
            foreach ($pedido->detalles as $detalle) {
                $producto = Productos::findOrFail($detalle->prod_id);
                $producto->pro_stock += $detalle->det_cantidad;
                $producto->save();
            }

            // Eliminar relaciones de métodos de pago en pivote
            $factura->metodosPago()->detach();

            // Eliminar factura
            $factura->delete();

            DB::commit();
            return response()->json([
                'message' => 'Factura eliminada y stock restaurado correctamente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al eliminar la factura.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}

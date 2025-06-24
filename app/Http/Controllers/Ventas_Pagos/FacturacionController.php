<?php

namespace App\Http\Controllers\Ventas_Pagos;

use App\Http\Controllers\Controller;
use App\Models\ventas_Pagos\Boletas;
use App\Models\ventas_Pagos\BoletasMetodos;
use App\Models\ventas_Pagos\MetodosPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FacturacionController extends Controller
{
    public function emitirBoleta(Request $req)
    {
        $data = $req->validate([
            'boleta_numero'             => 'required|string',
            'boleta_fecha'              => 'required|date',
            'boleta_subtotal'           => 'required|numeric',
            'boleta_impuestos'          => 'required|numeric',
            'boleta_descuento'          => 'nullable|numeric',
            'boleta_total'              => 'required|numeric',
            'boleta_estado'             => 'nullable|string',
            'boleta_notas'              => 'nullable|string',
            'ped_id'                    => 'required|string',
            'metodos_pago'              => 'required|array|min:1',
            'metodos_pago.*.met_nombre' => 'required|string',
            'metodos_pago.*.monto'      => 'nullable|numeric',
            'metodos_pago.*.fecha_pago' => 'nullable|date',
            'metodos_pago.*.nota_pago'  => 'nullable|string',
        ]);

        Log::info('[emitirBoleta] Iniciando emisión', ['numero' => $data['boleta_numero']]);

        DB::beginTransaction();
        try {
            // Crear registro de boleta
            $boleta = Boletas::create([
                'boleta_id'        => \Illuminate\Support\Str::uuid()->toString(),
                'boleta_numero'    => $data['boleta_numero'],
                'boleta_fecha'     => $data['boleta_fecha'],
                'boleta_subtotal'  => $data['boleta_subtotal'],
                'boleta_impuestos' => $data['boleta_impuestos'],
                'boleta_descuento' => $data['boleta_descuento'] ?? 0,
                'boleta_total'     => $data['boleta_total'],
                'boleta_estado'    => $data['boleta_estado']  ?? 'EMITIDA',
                'boleta_notas'     => $data['boleta_notas']   ?? '',
                'ped_id'           => $data['ped_id'],
            ]);

            // Asignar métodos de pago
            foreach ($data['metodos_pago'] as $mp) {
                $metodo = MetodosPago::where('met_nombre', $mp['met_nombre'])->first();
                if (! $metodo) {
                    throw new \Exception("Método '{$mp['met_nombre']}' no encontrado.");
                }
                BoletasMetodos::create([
                    'boleta_id'      => $boleta->boleta_id,
                    'met_id'         => $metodo->met_id,
                    'monto'          => $mp['monto'] ?? $data['boleta_total'],
                    'referencia'     => $mp['nota_pago']  ?? null,
                    'fecha_registro' => $mp['fecha_pago'] ?? now(),
                ]);
            }

            DB::commit();
            Log::info('[emitirBoleta] Boleta emitida con éxito', ['id' => $boleta->boleta_id]);

            return response()->json([
                'success' => true,
                'message' => 'Boleta registrada correctamente.',
                'boleta'  => $boleta->load('metodosPago'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[emitirBoleta] Error al registrar', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generarPdf(Request $req)
    {
        // 1. Validar todo el payload (igual que emitirBoleta)
        $data = $req->validate([
            'boleta_numero'             => 'required|string',
            'boleta_fecha'              => 'required|date',
            'boleta_subtotal'           => 'required|numeric',
            'boleta_impuestos'          => 'required|numeric',
            'boleta_descuento'          => 'nullable|numeric',
            'boleta_total'              => 'required|numeric',
            'boleta_estado'             => 'nullable|string',
            'boleta_notas'              => 'nullable|string',
            'ped_id'                    => 'required|string',
            'metodos_pago'              => 'required|array|min:1',
            'metodos_pago.*.met_nombre' => 'required|string',
            'metodos_pago.*.monto'      => 'nullable|numeric',
            'metodos_pago.*.fecha_pago' => 'nullable|date',
            'metodos_pago.*.nota_pago'  => 'nullable|string',
            'pedido.cliente.cli_tipo_doc'   => 'required|string',
            'pedido.cliente.cli_numero_doc' => 'required|string',
            'pedido.cliente.cli_nombre'     => 'required|string',
            'pedido.cliente.cli_apellido'   => 'required|string',
            'pedido.detalles'               => 'required|array|min:1',
            'pedido.detalles.*.det_cantidad'        => 'required|numeric',
            'pedido.detalles.*.det_precio_unitario' => 'required|numeric',
            'pedido.detalles.*.det_subtotal'        => 'required|numeric',
            'pedido.detalles.*.det_impuesto'        => 'required|numeric',
            'pedido.detalles.*.producto.pro_id'     => 'required|string',
            'pedido.detalles.*.producto.pro_nombre' => 'required|string',
        ]);

        Log::info('[generarPdf] Solicitando PDF al microservicio', ['input' => $data]);

        try {
            // 2. Enviar todo el payload al microservicio de PDFs
            $response = Http::timeout(60)
                ->withHeaders(['Accept' => 'application/pdf'])
                ->post(env('MICROSERVICIO_URL') . '/generar-boleta', $data);

            if (! $response->successful()) {
                throw new \Exception('Microservicio respondió con status ' . $response->status());
            }

            // 3. Devolver PDF al cliente
            return response($response->body(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=boleta_{$data['boleta_numero']}.pdf");
        }
        catch (\Exception $e) {
            Log::error('[generarPdf] Error al generar PDF', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo generar PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
}

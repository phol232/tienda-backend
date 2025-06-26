<?php

namespace App\Http\Controllers\Ventas_Pagos;

use App\Http\Controllers\Controller;
use App\Models\ventas_Pagos\Boletas;
use App\Models\ventas_Pagos\BoletasMetodos;
use App\Models\ventas_Pagos\MetodosPago;
use App\Models\Productos_Proveedores\Productos;        
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FacturacionController extends Controller
{
    /**
     * Crea la boleta, descuenta stock y registra métodos de pago.
     */
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
            'pedido.detalles'                           => 'required|array|min:1',
            'pedido.detalles.*.det_cantidad'            => 'required|numeric',
            'pedido.detalles.*.producto.pro_id'         => 'required|string',
            'pedido.detalles.*.producto.pro_nombre'     => 'required|string',
        ]);

        Log::info('[emitirBoleta] Iniciando emisión', ['numero' => $data['boleta_numero']]);

        DB::beginTransaction();
        try {
            // 1. Validar único número de boleta
            if (Boletas::where('boleta_numero', $data['boleta_numero'])->exists()) {
                throw new \Exception("Ya existe boleta {$data['boleta_numero']}");
            }

            // 2. Descontar stock de cada producto
            foreach ($data['pedido']['detalles'] as $detalle) {
                $cantidad = $detalle['det_cantidad'];
                $prodId   = $detalle['producto']['pro_id'];

                // bloquear fila para update
                $producto = Productos::lockForUpdate()->findOrFail($prodId);

                if ($producto->pro_stock < $cantidad) {
                    throw new \Exception(
                        "Stock insuficiente para {$producto->pro_nombre}. Disponible: {$producto->pro_stock}"
                    );
                }

                $producto->decrement('pro_stock', $cantidad);
            }

            // 3. Crear la boleta
            $boleta = Boletas::create([
                'boleta_id'        => Str::uuid()->toString(),
                'boleta_numero'    => $data['boleta_numero'],
                'boleta_fecha'     => $data['boleta_fecha'],
                'boleta_subtotal'  => $data['boleta_subtotal'],
                'boleta_impuestos' => $data['boleta_impuestos'],
                'boleta_descuento' => $data['boleta_descuento'] ?? 0,
                'boleta_total'     => $data['boleta_total'],
                'boleta_estado'    => $data['boleta_estado']  ?? 'EMITIDA',
                'boleta_notas'     => $data['boleta_notas']   ?? null,
                'ped_id'           => $data['ped_id'],
            ]);

            // 4. Registrar métodos de pago
            foreach ($data['metodos_pago'] as $mp) {
                $metodo = MetodosPago::firstOrCreate([
                    'met_nombre' => $mp['met_nombre']
                ]);

                BoletasMetodos::create([
                    'boleta_id'      => $boleta->boleta_id,
                    'met_id'         => $metodo->met_id,
                    'monto'          => $mp['monto']      ?? $data['boleta_total'],
                    'referencia'     => $mp['nota_pago']  ?? null,
                    'fecha_registro' => $mp['fecha_pago'] ?? now(),
                ]);
            }

            DB::commit();

            Log::info('[emitirBoleta] Boleta emitida y stock actualizado', [
                'boleta_id' => $boleta->boleta_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Boleta emitida correctamente.',
                'boleta'  => $boleta->load('metodosPago'),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[emitirBoleta] Falló emisión', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error al emitir boleta: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Envía los datos al microservicio PDF y devuelve el archivo.
     */
    public function generarPdf(Request $req)
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
            'pedido.cliente.cli_tipo_doc'   => 'required|string',
            'pedido.cliente.cli_numero_doc' => 'required|string',
            'pedido.cliente.cli_nombre'     => 'required|string',
            'pedido.cliente.cli_apellido'   => 'required|string',
            'pedido.detalles'               => 'required|array|min:1',
            'pedido.detalles.*.det_cantidad'        => 'required|numeric',
            'pedido.detalles.*.det_precio_unitario' => 'required|numeric',
            'pedido.detalles.*.det_subtotal'        => 'required|numeric',
            'pedido.detalles.*.det_impuesto'        => 'nullable|numeric',
            'pedido.detalles.*.producto.pro_id'     => 'required|string',
            'pedido.detalles.*.producto.pro_nombre' => 'required|string',
        ]);

        Log::info('[generarPdf] Solicitando PDF', [
            'numero' => $data['boleta_numero'],
            'microservice_url' => config('app.microservice_url')
        ]);

        try {
            // Obtener la URL del microservicio desde la configuración
            $microserviceUrl = config('app.microservice_url');
            
            if (empty($microserviceUrl)) {
                throw new \Exception('URL del microservicio no configurada');
            }

            Log::info('[generarPdf] Enviando petición a microservicio', [
                'url' => $microserviceUrl . '/generar-boleta',
                'payload_size' => strlen(json_encode($data))
            ]);

            $response = Http::timeout(60)
                ->withHeaders([
                    'Accept' => 'application/pdf',
                    'Content-Type' => 'application/json'
                ])
                ->post($microserviceUrl . '/generar-boleta', $data);

            Log::info('[generarPdf] Respuesta del microservicio', [
                'status' => $response->status(),
                'content_type' => $response->header('Content-Type'),
                'body_size' => strlen($response->body())
            ]);

            if (!$response->successful()) {
                // Intentar obtener el mensaje de error del microservicio
                $errorMessage = 'Microservicio respondió ' . $response->status();
                
                try {
                    $errorBody = $response->json();
                    if (isset($errorBody['message'])) {
                        $errorMessage .= ': ' . $errorBody['message'];
                    }
                } catch (\Exception $e) {
                    // Si no se puede parsear como JSON, usar el texto plano
                    $errorMessage .= ': ' . $response->body();
                }
                
                throw new \Exception($errorMessage);
            }

            // Verificar que la respuesta sea realmente un PDF
            $contentType = $response->header('Content-Type');
            if (!str_contains($contentType, 'application/pdf')) {
                Log::warning('[generarPdf] Respuesta no es PDF', [
                    'content_type' => $contentType,
                    'body_preview' => substr($response->body(), 0, 200)
                ]);
            }

            return response($response->body(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=boleta_{$data['boleta_numero']}.pdf")
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Throwable $e) {
            Log::error('[generarPdf] Falla generación PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'microservice_url' => config('app.microservice_url')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo generar PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
}

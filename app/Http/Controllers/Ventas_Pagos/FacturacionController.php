<?php

namespace App\Http\Controllers\Ventas_Pagos;

use App\Http\Controllers\Controller;
use App\Models\ventas_Pagos\Boletas;
use App\Models\ventas_Pagos\BoletasMetodos;
use App\Models\ventas_Pagos\MetodosPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\ApisPeruService;

class FacturacionController extends Controller
{
    protected ApisPeruService $api;

    public function __construct(ApisPeruService $api)
    {
        $this->api = $api;
    }

    public function emitirBoleta(Request $req)
    {
        $data = $req->validate([
            'boleta_numero'                   => 'required|string',
            'boleta_fecha'                    => 'required|date',
            'boleta_subtotal'                 => 'required|numeric',
            'boleta_impuestos'                => 'required|numeric',
            'boleta_descuento'                => 'nullable|numeric',
            'boleta_total'                    => 'required|numeric',
            'boleta_estado'                   => 'nullable|string',
            'boleta_notas'                    => 'nullable|string',
            'ped_id'                          => 'required|string',
            'metodos_pago'                    => 'required|array|min:1',
            'metodos_pago.*.met_nombre'       => 'required|string',
            'metodos_pago.*.monto'            => 'nullable|numeric',
            'metodos_pago.*.fecha_pago'       => 'nullable|date',
            'metodos_pago.*.nota_pago'        => 'nullable|string',
        ]);

        Log::info('[emitirBoleta] Persistiendo boleta localmente', [
            'numero'    => $data['boleta_numero'],
            'timestamp' => now()->toISOString(),
        ]);

        DB::beginTransaction();
        try {
            // 1) Crear cabecera de boleta
            $boleta = Boletas::create([
                'boleta_id'        => Str::uuid()->toString(),
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

            // 2) Registrar métodos de pago en la tabla pivote
            foreach ($data['metodos_pago'] as $mp) {
                $metodo = MetodosPago::where('met_nombre', $mp['met_nombre'])->first();
                if (! $metodo) {
                    throw new \Exception("Método de pago “{$mp['met_nombre']}” no encontrado.");
                }

                BoletasMetodos::create([
                    'boleta_id'      => $boleta->boleta_id,
                    'met_id'         => $metodo->met_id,
                    'monto'          => $mp['monto']      ?? $data['boleta_total'],
                    'referencia'     => $mp['nota_pago']  ?? null,
                    'fecha_registro' => $mp['fecha_pago'] ?? now(),
                ]);
            }

            DB::commit();

            $boleta->load('metodosPago', 'pedido');

            Log::info('[emitirBoleta] Boleta guardada con éxito', [
                'boleta_id'     => $boleta->boleta_id,
                'metodos_count' => $boleta->metodosPago->count(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Boleta registrada correctamente.',
                'boleta'  => $boleta,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[emitirBoleta] Error al registrar boleta', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la boleta: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🆕 Proxy para Frontend con timeout extendido
     */
    public function emitirBoletaFrontend(Request $req)
    {
        $requestId = uniqid('frontend_', true);
        $startTime = microtime(true);

        Log::info('[emitirBoletaFrontend] Inicio con timeout extendido', [
            'request_id' => $requestId,
            'input' => $req->all(),
            'timestamp' => now()->toISOString(),
        ]);

        $data = $req->validate([
            'boleta_numero'                        => 'required|string',
            'boleta_fecha'                         => 'required|date',
            'boleta_subtotal'                      => 'required|numeric',
            'boleta_impuestos'                     => 'required|numeric',
            'boleta_total'                         => 'required|numeric',
            'metodos_pago'                         => 'required|array|min:1',
            'metodos_pago.*.met_nombre'            => 'required|string',
            'pedido.cliente.cli_tipo_doc'          => 'required|string',
            'pedido.cliente.cli_numero_doc'        => 'required|string',
            'pedido.cliente.cli_nombre'            => 'required|string',
            'pedido.cliente.cli_apellido'          => 'required|string',
            'pedido.detalles'                      => 'required|array|min:1',
            'pedido.detalles.*.det_cantidad'       => 'required|numeric',
            'pedido.detalles.*.det_precio_unitario'=> 'required|numeric',
            'pedido.detalles.*.det_subtotal'       => 'required|numeric',
            'pedido.detalles.*.det_impuesto'       => 'required|numeric',
            'pedido.detalles.*.producto.pro_id'    => 'required|string',
            'pedido.detalles.*.producto.pro_nombre'=> 'required|string',
        ]);

        try {
            $payload = $this->buildPayload($data);
            $token   = $this->getCleanApiPeruTokenWithExtendedTimeout();

            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'User-Agent'    => 'Laravel-SUNAT-Server/1.0',
                    'Connection'    => 'keep-alive',
                ])
                ->post(config('services.apisperu.base_url') . '/api/v1/invoice/send', $payload);

            $processingTime = round((microtime(true) - $startTime), 2);

            if (! $response->successful()) {
                throw new \Exception('ApisPeru Error '.$response->status().': '.$response->body());
            }

            $dataResp = $response->json();

            return response()->json([
                'success' => true,
                'message' => 'Boleta emitida desde frontend en '.$processingTime.'s.',
                'data'    => [
                    'xml'                     => $dataResp['xml'] ?? null,
                    'cdr'                     => $dataResp['sunatResponse'] ?? null,
                    'processing_time_seconds' => $processingTime,
                ],
            ], 200);

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime), 2);
            $msg = str_contains($e->getMessage(), 'timeout')
                ? "Timeout después de {$processingTime}s."
                : $e->getMessage();

            return response()->json([
                'success' => false,
                'message' => $msg,
            ], 500);
        }
    }

    public function generarPdf(Request $req)
    {
        Log::info('[generarPdf] Generando PDF', ['input' => $req->all()]);

        $data = $req->validate([
            'boleta_numero'                        => 'required|string',
            'boleta_fecha'                         => 'required|date',
            'boleta_subtotal'                      => 'required|numeric',
            'boleta_impuestos'                     => 'required|numeric',
            'boleta_total'                         => 'required|numeric',
            'metodos_pago'                         => 'required|array|min:1',
            'metodos_pago.*.met_nombre'            => 'required|string',
            'pedido.cliente.cli_tipo_doc'          => 'required|string',
            'pedido.cliente.cli_numero_doc'        => 'required|string',
            'pedido.cliente.cli_nombre'            => 'required|string',
            'pedido.cliente.cli_apellido'          => 'required|string',
            'pedido.detalles'                      => 'required|array|min:1',
            'pedido.detalles.*.det_cantidad'       => 'required|numeric',
            'pedido.detalles.*.det_precio_unitario'=> 'required|numeric',
            'pedido.detalles.*.det_subtotal'       => 'required|numeric',
            'pedido.detalles.*.det_impuesto'       => 'required|numeric',
            'pedido.detalles.*.producto.pro_id'    => 'required|string',
            'pedido.detalles.*.producto.pro_nombre'=> 'required|string',
        ]);

        $pdf = Pdf::loadView('pdf.boleta', ['boleta' => $data]);

        return $pdf->download("boleta_{$data['boleta_numero']}.pdf");
    }

    private function buildPayload(array $data): array
    {
        list($serie, $correl) = explode('-', $data['boleta_numero'], 2);
        $metodo = $data['metodos_pago'][0]['met_nombre'];

        return [
            'ublVersion'    => '2.1',
            'tipoOperacion' => '0101',
            'tipoDoc'       => '03',
            'serie'         => $serie,
            'correlativo'   => $correl,
            'fechaEmision'  => $data['boleta_fecha'],
            'formaPago'     => ['moneda' => 'PEN', 'tipo' => $metodo],
            'tipoMoneda'    => 'PEN',
            'client'        => [
                'tipoDoc'   => $data['pedido']['cliente']['cli_tipo_doc'],
                'numDoc'    => $data['pedido']['cliente']['cli_numero_doc'],
                'rznSocial' => $data['pedido']['cliente']['cli_nombre'].' '.$data['pedido']['cliente']['cli_apellido'],
            ],
            'company'       => [
                'ruc'            => config('services.apisperu.company_ruc'),
                'razonSocial'    => config('services.apisperu.company_name'),
                'nombreComercial'=> config('services.apisperu.company_trade'),
                'address'        => [
                    'direccion'    => config('services.apisperu.company_address'),
                    'provincia'    => config('services.apisperu.company_province'),
                    'departamento' => config('services.apisperu.company_department'),
                    'distrito'     => config('services.apisperu.company_district'),
                    'ubigueo'      => config('services.apisperu.company_ubigeo'),
                ],
            ],
            'mtoOperGravadas'=> (float)$data['boleta_subtotal'],
            'mtoIGV'         => (float)$data['boleta_impuestos'],
            'valorVenta'     => (float)$data['boleta_subtotal'],
            'totalImpuestos' => (float)$data['boleta_impuestos'],
            'subTotal'       => (float)$data['boleta_total'],
            'mtoImpVenta'    => (float)$data['boleta_total'],
            'details'        => array_map(function($det) {
                return [
                    'codProducto'      => $det['producto']['pro_id'],
                    'descripcion'      => $det['producto']['pro_nombre'],
                    'cantidad'         => (int)$det['det_cantidad'],
                    'unidad'           => 'NIU',
                    'mtoValorUnitario' => (float)$det['det_precio_unitario'],
                    'mtoValorVenta'    => (float)$det['det_subtotal'],
                    'mtoBaseIgv'       => (float)$det['det_subtotal'],
                    'porcentajeIgv'    => 18,
                    'igv'              => (float)$det['det_impuesto'],
                    'tipAfeIgv'        => 10,
                    'totalImpuestos'   => (float)$det['det_impuesto'],
                    'mtoPrecioUnitario'=> (float)($det['det_precio_unitario'] * 1.18),
                ];
            }, $data['pedido']['detalles']),
            'legends'        => [[
                'code'  => '1000',
                'value' => $this->convertirTotalLetras($data['boleta_total']).' SOLES',
            ]],
        ];
    }

    private function convertirTotalLetras($numero)
    {
        try {
            $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
            return strtoupper($formatter->format($numero));
        } catch (\Exception $e) {
            Log::error('[convertirTotalLetras] '.$e->getMessage());
            return (string)$numero;
        }
    }

    public function emitirBoletaReplicaPostman(Request $req)
    {
        $requestId = uniqid('postman_replica_', true);
        $startTime = microtime(true);

        Log::info('[emitirBoletaReplicaPostman] Inicio', ['request_id' => $requestId]);

        $data = $req->validate([
            'boleta_numero' => 'required|string',
            'boleta_fecha'  => 'required|date',
            'boleta_subtotal'=> 'required|numeric',
            'boleta_impuestos'=> 'required|numeric',
            'boleta_total'=> 'required|numeric',
            'metodos_pago'=> 'required|array|min:1',
            'metodos_pago.*.met_nombre'=> 'required|string',
            'pedido.cliente.cli_tipo_doc'=>'required|string',
            'pedido.cliente.cli_numero_doc'=>'required|string',
            'pedido.cliente.cli_nombre'=>'required|string',
            'pedido.cliente.cli_apellido'=>'required|string',
            'pedido.detalles'=>'required|array|min:1',
            'pedido.detalles.*.det_cantidad'=>'required|numeric',
            'pedido.detalles.*.det_precio_unitario'=>'required|numeric',
            'pedido.detalles.*.det_subtotal'=>'required|numeric',
            'pedido.detalles.*.det_impuesto'=>'required|numeric',
            'pedido.detalles.*.producto.pro_id'=>'required|string',
            'pedido.detalles.*.producto.pro_nombre'=>'required|string',
        ]);

        try {
            $payload = $this->buildPayload($data);
            $token   = $this->getTokenReplicaPostman();

            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization'   => 'Bearer ' . $token,
                    'Content-Type'    => 'application/json',
                    'User-Agent'      => 'PostmanRuntime/7.44.0',
                    'Accept'          => '*/*',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Connection'      => 'keep-alive',
                ])
                ->post(config('services.apisperu.base_url') . '/api/v1/invoice/send', $payload);

            if (! $response->successful()) {
                throw new \Exception('Error Postman Replica: '.$response->status());
            }

            $resp = $response->json();

            return response()->json([
                'success' => true,
                'message' => 'Emitido replicando Postman.',
                'data'    => [
                    'xml'  => $resp['xml'] ?? null,
                    'cdr'  => $resp['sunatResponse'] ?? null,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error crítico: '.$e->getMessage(),
            ], 500);
        }
    }

    private function getCleanApiPeruTokenWithExtendedTimeout(): string
    {
        return Cache::remember('apisperu_token_frontend_extended', now()->addMinutes(50), function () {
            $response = Http::timeout(60)
                ->retry(3, 2000)
                ->post(config('services.apisperu.base_url') . '/api/v1/auth/login', [
                    'username' => config('services.apisperu.email'),
                    'password' => config('services.apisperu.password'),
                ]);
            if (! $response->successful()) {
                throw new \Exception('Login fallo: '.$response->body());
            }
            return $response->json('token');
        });
    }

    private function getTokenReplicaPostman(): string
    {
        return Cache::remember('apisperu_token_postman_replica', now()->addMinutes(50), function () {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type'    => 'application/json',
                    'User-Agent'      => 'PostmanRuntime/7.44.0',
                    'Accept'          => '*/*',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Connection'      => 'keep-alive',
                ])
                ->post(config('services.apisperu.base_url') . '/api/v1/auth/login', [
                    'username' => config('services.apisperu.email'),
                    'password' => config('services.apisperu.password'),
                ]);
            if (! $response->successful()) {
                throw new \Exception('Login Postman fallo: '.$response->body());
            }
            return $response->json('token');
        });
    }

    public function verificarEstadoApisPeru()
    {
        $start = microtime(true);
        try {
            $this->getCleanApiPeruTokenWithExtendedTimeout();
            $time = round(microtime(true) - $start, 2);
            return response()->json([
                'available' => true,
                'response_time' => $time,
            ], 200);
        } catch (\Exception $e) {
            $time = round(microtime(true) - $start, 2);
            return response()->json([
                'available' => false,
                'error'      => $e->getMessage(),
                'response_time' => $time,
            ], 503);
        }
    }
}

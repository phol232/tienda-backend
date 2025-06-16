<?php

namespace App\Http\Controllers\Ventas_Pagos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ApisPeruService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class FacturacionController extends Controller
{
    protected ApisPeruService $api;

    public function __construct(ApisPeruService $api)
    {
        $this->api = $api;
    }

    /**
     * 🔄 MÉTODO ORIGINAL - Para Postman (mantener funcionando)
     */
    public function emitirBoleta(Request $req)
    {
        Log::info('[emitirBoleta] Inicio - Request desde Postman/API directa', [
            'input' => $req->all(),
            'user_agent' => $req->header('User-Agent'),
            'timestamp' => now()->toISOString()
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

            Log::info('[emitirBoleta] Payload construido para Postman', [
                'payload_size' => strlen(json_encode($payload)),
                'boleta_numero' => $data['boleta_numero']
            ]);

            // Usar el servicio original para Postman
            $respuesta = $this->api->sendInvoice($payload);

            Log::info('[emitirBoleta] Respuesta exitosa de ApisPeru via Postman');

            return response()->json([
                'success'   => true,
                'message'   => 'Boleta emitida correctamente.',
                'sunat_xml' => $respuesta['xml'] ?? null,
                'cdr'       => $respuesta['sunatResponse'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('[emitirBoleta] Error en método Postman', [
                'error' => $e->getMessage(),
                'boleta_numero' => $data['boleta_numero'] ?? 'N/A'
            ]);

            $message = $e->getMessage();
            if (str_contains($message, 'servidor interno')) {
                $message = 'El servicio de facturación está temporalmente no disponible. Por favor, intente nuevamente en unos minutos.';
            }

            return response()->json(['success' => false, 'message' => $message], 500);
        }
    }

    /**
     * 🆕 MÉTODO NUEVO - Proxy para Frontend con timeout extendido (sin headers problemáticos)
     */
    public function emitirBoletaFrontend(Request $req)
    {
        $requestId = uniqid('frontend_', true);
        $startTime = microtime(true);

        Log::info('[emitirBoletaFrontend] Inicio con timeout extendido', [
            'request_id' => $requestId,
            'input' => $req->all(),
            'user_agent_original' => $req->header('User-Agent'),
            'origin' => $req->header('Origin'),
            'timestamp' => now()->toISOString(),
            'expected_processing_time' => '3-5 segundos basado en análisis de Postman'
        ]);

        // Mismas validaciones que el método original
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
            // Construir payload igual que el método original
            $payload = $this->buildPayload($data);

            Log::info('[emitirBoletaFrontend] Payload construido', [
                'request_id' => $requestId,
                'payload_size' => strlen(json_encode($payload)),
                'boleta_numero' => $data['boleta_numero'],
                'processing_note' => 'Preparándose para espera de 3-5 segundos de ApisPeru'
            ]);

            // 🔑 OBTENER TOKEN CON TIMEOUT EXTENDIDO
            $token = $this->getCleanApiPeruTokenWithExtendedTimeout();

            Log::info('[emitirBoletaFrontend] Enviando request a ApisPeru con timeout extendido', [
                'request_id' => $requestId,
                'timeout_seconds' => 120,
                'expected_response_time' => '3-5 segundos'
            ]);

            // 🔑 TIMEOUT CRÍTICO AUMENTADO A 120 SEGUNDOS (basado en observación de Postman 3.5s)
            $response = Http::timeout(120) // ← CAMBIO CRÍTICO: De 60 a 120 segundos
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'Laravel-SUNAT-Server/1.0',
                'Connection' => 'keep-alive' // Mantener conexión activa
            ])
                ->post(config('services.apisperu.base_url') . '/api/v1/invoice/send', $payload);

            $processingTime = round((microtime(true) - $startTime), 2);

            Log::info('[emitirBoletaFrontend] Respuesta recibida de ApisPeru después de espera', [
                'request_id' => $requestId,
                'status' => $response->status(),
                'success' => $response->successful(),
                'response_size' => strlen($response->body()),
                'processing_time_seconds' => $processingTime,
                'postman_comparison' => $processingTime > 3 ? 'Similar a Postman (3.5s)' : 'Más rápido que Postman'
            ]);

            if (!$response->successful()) {
                Log::error('[emitirBoletaFrontend] Error de ApisPeru después de espera prolongada', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'processing_time_seconds' => $processingTime
                ]);

                // Distinguir tipos de error después de la espera
                if ($response->status() === 500) {
                    throw new \Exception('ApisPeru respondió con error 500 después de ' . $processingTime . ' segundos. Error: ' . $response->body());
                } else {
                    throw new \Exception('Error de ApisPeru (Status: ' . $response->status() . '): ' . $response->body());
                }
            }

            $responseData = $response->json();

            Log::info('[emitirBoletaFrontend] Boleta emitida exitosamente después de espera', [
                'request_id' => $requestId,
                'boleta_numero' => $data['boleta_numero'],
                'has_xml' => isset($responseData['xml']),
                'has_cdr' => isset($responseData['sunatResponse']),
                'total_processing_time_seconds' => $processingTime,
                'success_after_wait' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Boleta emitida correctamente desde frontend. ApisPeru procesó en ' . $processingTime . ' segundos.',
                'data' => [
                    'sunat_xml' => $responseData['xml'] ?? null,
                    'cdr' => $responseData['sunatResponse'] ?? null,
                    'request_id' => $requestId,
                    'processing_time_seconds' => $processingTime,
                    'performance_note' => $processingTime > 3 ? 'Tiempo normal para ApisPeru' : 'Respuesta más rápida que lo usual'
                ]
            ]);

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime), 2);

            Log::error('[emitirBoletaFrontend] Error al procesar con timeout extendido', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'processing_time_seconds' => $processingTime,
                'trace' => $e->getTraceAsString()
            ]);

            // Mensajes específicos según el tipo de error y tiempo transcurrido
            $message = $e->getMessage();

            if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
                $message = "Timeout después de {$processingTime} segundos. ApisPeru está tardando más de lo normal (usualmente 3-5s). El servicio puede estar sobrecargado. Por favor, intente nuevamente en unos minutos.";
            } elseif (str_contains($message, 'servidor interno')) {
                $message = "ApisPeru está experimentando problemas internos después de {$processingTime} segundos de espera. El servicio está funcionando pero puede estar lento. Por favor, intente nuevamente.";
            } elseif (str_contains($message, 'error 500')) {
                $message = "ApisPeru procesó la solicitud durante {$processingTime} segundos pero respondió con error interno. Esto indica que el servicio está operativo pero sobrecargado.";
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'request_id' => $requestId,
                'processing_time_seconds' => $processingTime,
                'suggestion' => 'ApisPeru necesita 3-5 segundos para procesar normalmente. Si sigue fallando, puede estar sobrecargado.',
                'technical_details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🛠️ Método helper: Obtener token de ApisPeru con timeout extendido
     */
    private function getCleanApiPeruTokenWithExtendedTimeout(): string
    {
        return Cache::remember('apisperu_token_frontend_extended', now()->addMinutes(50), function () {
            Log::info('[getCleanApiPeruTokenWithExtendedTimeout] Obteniendo token con timeout extendido para frontend');

            $response = Http::timeout(60) // ← AUMENTADO: De 30 a 60 segundos
            ->retry(3, 2000) // ← AGREGADO: 3 reintentos con 2 segundos entre cada uno
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'Laravel-SUNAT-Server/1.0',
                'Connection' => 'keep-alive'
            ])
                ->post(config('services.apisperu.base_url') . '/api/v1/auth/login', [
                    'username' => config('services.apisperu.email'),
                    'password' => config('services.apisperu.password'),
                ]);

            Log::info('[getCleanApiPeruTokenWithExtendedTimeout] Respuesta de login con timeout extendido', [
                'status' => $response->status(),
                'success' => $response->successful(),
                'response_time_note' => 'Token obtenido con configuración extendida'
            ]);

            if (!$response->successful()) {
                throw new \Exception('Login ApisPeru falló con timeout extendido: ' . $response->body());
            }

            $token = $response->json('token');

            Log::info('[getCleanApiPeruTokenWithExtendedTimeout] Token obtenido exitosamente con timeout extendido');

            return $token;
        });
    }

    /**
     * 🛠️ Método helper: Obtener token original (para mantener compatibilidad)
     */
    private function getCleanApiPeruToken(): string
    {
        return Cache::remember('apisperu_token_frontend', now()->addMinutes(50), function () {
            Log::info('[getCleanApiPeruToken] Obteniendo token limpio para frontend (método original)');

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'Laravel-SUNAT-Server/1.0'
                ])
                ->post(config('services.apisperu.base_url') . '/api/v1/auth/login', [
                    'username' => config('services.apisperu.email'),
                    'password' => config('services.apisperu.password'),
                ]);

            Log::info('[getCleanApiPeruToken] Respuesta de login', [
                'status' => $response->status(),
                'success' => $response->successful()
            ]);

            if (!$response->successful()) {
                throw new \Exception('Login ApisPeru falló: ' . $response->body());
            }

            $token = $response->json('token');

            Log::info('[getCleanApiPeruToken] Token obtenido exitosamente');

            return $token;
        });
    }

    /**
     * 🆕 MÉTODO ADICIONAL - Verificar estado de ApisPeru antes de procesar
     */
    public function verificarEstadoApisPeru()
    {
        $startTime = microtime(true);

        try {
            Log::info('[verificarEstadoApisPeru] Iniciando verificación de estado');

            // Intentar obtener token como prueba de conectividad
            $token = $this->getCleanApiPeruTokenWithExtendedTimeout();

            $responseTime = round((microtime(true) - $startTime), 2);

            Log::info('[verificarEstadoApisPeru] Verificación exitosa', [
                'response_time_seconds' => $responseTime,
                'status' => 'available'
            ]);

            return response()->json([
                'apisperu_available' => true,
                'response_time_seconds' => $responseTime,
                'status' => 'available',
                'message' => 'ApisPeru está disponible y respondiendo.',
                'performance' => $responseTime < 2 ? 'excellent' : ($responseTime < 5 ? 'good' : 'slow'),
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime), 2);

            Log::warning('[verificarEstadoApisPeru] Error en verificación', [
                'error' => $e->getMessage(),
                'response_time_seconds' => $responseTime
            ]);

            return response()->json([
                'apisperu_available' => false,
                'response_time_seconds' => $responseTime,
                'status' => 'unavailable',
                'message' => 'ApisPeru no está disponible en este momento.',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }

    /**
     * 🛠️ Método helper: Construir payload para ApisPeru
     */
    private function buildPayload(array $data): array
    {
        list($serie, $correl) = explode('-', $data['boleta_numero'], 2);
        $metodo = $data['metodos_pago'][0]['met_nombre'];

        return [
            'ublVersion'      => '2.1',
            'tipoOperacion'   => '0101',
            'tipoDoc'         => '03',
            'serie'           => $serie,
            'correlativo'     => $correl,
            'fechaEmision'    => $data['boleta_fecha'],
            'formaPago'       => ['moneda' => 'PEN', 'tipo' => $metodo],
            'tipoMoneda'      => 'PEN',
            'client'          => [
                'tipoDoc'   => $data['pedido']['cliente']['cli_tipo_doc'],
                'numDoc'    => $data['pedido']['cliente']['cli_numero_doc'],
                'rznSocial' => $data['pedido']['cliente']['cli_nombre'].' '.$data['pedido']['cliente']['cli_apellido'],
            ],
            'company'         => [
                'ruc'            => config('services.apisperu.company_ruc'),
                'razonSocial'    => config('services.apisperu.company_name'),
                'nombreComercial'=> config('services.apisperu.company_trade'),
                'address'        => [
                    'direccion'   => config('services.apisperu.company_address'),
                    'provincia'   => config('services.apisperu.company_province'),
                    'departamento'=> config('services.apisperu.company_department'),
                    'distrito'    => config('services.apisperu.company_district'),
                    'ubigueo'     => config('services.apisperu.company_ubigeo'),
                ]
            ],
            'mtoOperGravadas' => (float)$data['boleta_subtotal'],
            'mtoIGV'          => (float)$data['boleta_impuestos'],
            'valorVenta'      => (float)$data['boleta_subtotal'],
            'totalImpuestos'  => (float)$data['boleta_impuestos'],
            'subTotal'        => (float)$data['boleta_total'],
            'mtoImpVenta'     => (float)$data['boleta_total'],
            'details'         => array_map(function($det) {
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
            'legends' => [
                [
                    'code'  => '1000',
                    'value' => $this->convertirTotalLetras($data['boleta_total']).' SOLES',
                ]
            ],
        ];
    }

    /**
     * Genera el PDF manualmente usando DomPDF y Blade (boleta.blade.php)
     */
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

        $pdf = PDF::loadView('pdf.boleta', [
            'boleta' => $data
        ]);

        Log::info('[generarPdf] PDF generado correctamente para la boleta', ['boleta_numero' => $data['boleta_numero']]);
        return $pdf->download("boleta_{$data['boleta_numero']}.pdf");
    }

    /**
     * Convierte total numérico a letras (muy básico)
     */
    private function convertirTotalLetras($numero)
    {
        try {
            $formatter = new \NumberFormatter('es', \NumberFormatter::SPELLOUT);
            $total = $formatter->format($numero);
            Log::info('[convertirTotalLetras] Número convertido a letras', [
                'numero' => $numero,
                'letras' => $total
            ]);
            return strtoupper($total);
        } catch (\Exception $e) {
            Log::error('[convertirTotalLetras] Error', [
                'numero' => $numero,
                'error' => $e->getMessage()
            ]);
            return (string) $numero;
        }
    }
    public function emitirBoletaReplicaPostman(Request $req)
    {
        $requestId = uniqid('postman_replica_', true);
        $startTime = microtime(true);

        Log::info('[emitirBoletaReplicaPostman] Inicio - Replicando EXACTAMENTE Postman', [
            'request_id' => $requestId,
            'objetivo' => 'Replicar User-Agent y headers de Postman que funciona',
            'timestamp' => now()->toISOString()
        ]);

        // Validaciones iguales...
        $data = $req->validate([
            'boleta_numero' => 'required|string',
            'boleta_fecha' => 'required|date',
            'boleta_subtotal' => 'required|numeric',
            'boleta_impuestos' => 'required|numeric',
            'boleta_total' => 'required|numeric',
            'metodos_pago' => 'required|array|min:1',
            'metodos_pago.*.met_nombre' => 'required|string',
            'pedido.cliente.cli_tipo_doc' => 'required|string',
            'pedido.cliente.cli_numero_doc' => 'required|string',
            'pedido.cliente.cli_nombre' => 'required|string',
            'pedido.cliente.cli_apellido' => 'required|string',
            'pedido.detalles' => 'required|array|min:1',
            'pedido.detalles.*.det_cantidad' => 'required|numeric',
            'pedido.detalles.*.det_precio_unitario' => 'required|numeric',
            'pedido.detalles.*.det_subtotal' => 'required|numeric',
            'pedido.detalles.*.det_impuesto' => 'required|numeric',
            'pedido.detalles.*.producto.pro_id' => 'required|string',
            'pedido.detalles.*.producto.pro_nombre' => 'required|string',
        ]);

        try {
            $payload = $this->buildPayload($data);

            Log::info('[emitirBoletaReplicaPostman] Payload construido', [
                'request_id' => $requestId,
                'payload_size' => strlen(json_encode($payload))
            ]);

            // 🔑 OBTENER TOKEN EXACTAMENTE COMO POSTMAN
            $token = $this->getTokenReplicaPostman();

            Log::info('[emitirBoletaReplicaPostman] Enviando con headers EXACTOS de Postman', [
                'request_id' => $requestId,
                'user_agent' => 'PostmanRuntime/7.44.0',
                'note' => 'Replicando headers que funcionan en Postman'
            ]);

            // 🎯 REQUEST EXACTA COMO POSTMAN
            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json', // ← EXACTO como Postman
                    'User-Agent' => 'PostmanRuntime/7.44.0', // ← EXACTO como Postman
                    'Accept' => '*/*', // ← EXACTO como Postman
                    'Accept-Encoding' => 'gzip, deflate, br', // ← EXACTO como Postman
                    'Connection' => 'keep-alive' // ← EXACTO como Postman
                ])
                ->post(config('services.apisperu.base_url') . '/api/v1/invoice/send', $payload);

            $processingTime = round((microtime(true) - $startTime), 2);

            Log::info('[emitirBoletaReplicaPostman] Respuesta con headers de Postman', [
                'request_id' => $requestId,
                'status' => $response->status(),
                'success' => $response->successful(),
                'processing_time_seconds' => $processingTime,
                'comparison' => 'Usando EXACTAMENTE los headers de Postman que funcionan'
            ]);

            if (!$response->successful()) {
                Log::error('[emitirBoletaReplicaPostman] Falló incluso con headers de Postman', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'processing_time' => $processingTime,
                    'critical_note' => 'Si esto falla, el problema es más profundo'
                ]);

                throw new \Exception('Falló incluso replicando Postman exactamente. Status: ' . $response->status() . ', Body: ' . $response->body());
            }

            $responseData = $response->json();

            Log::info('[emitirBoletaReplicaPostman] ¡ÉXITO! Funciona replicando Postman', [
                'request_id' => $requestId,
                'boleta_numero' => $data['boleta_numero'],
                'processing_time_seconds' => $processingTime,
                'success_note' => 'Headers de Postman son la clave del éxito'
            ]);

            return response()->json([
                'success' => true,
                'message' => "¡ÉXITO! Boleta emitida replicando exactamente Postman en {$processingTime} segundos.",
                'data' => [
                    'sunat_xml' => $responseData['xml'] ?? null,
                    'cdr' => $responseData['sunatResponse'] ?? null,
                    'request_id' => $requestId,
                    'processing_time_seconds' => $processingTime,
                    'method' => 'postman_replica',
                    'headers_used' => 'PostmanRuntime/7.44.0'
                ]
            ]);

        } catch (\Exception $e) {
            $processingTime = round((microtime(true) - $startTime), 2);

            Log::error('[emitirBoletaReplicaPostman] Error crítico incluso replicando Postman', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'processing_time' => $processingTime
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error crítico: Falló incluso replicando exactamente Postman.',
                'request_id' => $requestId,
                'technical_details' => $e->getMessage(),
                'processing_time_seconds' => $processingTime
            ], 500);
        }
    }

    /**
     * 🔑 Obtener token replicando exactamente Postman
     */
    private function getTokenReplicaPostman(): string
    {
        return Cache::remember('apisperu_token_postman_replica', now()->addMinutes(50), function () {
            Log::info('[getTokenReplicaPostman] Login con headers exactos de Postman');

            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json', // ← EXACTO como Postman
                    'User-Agent' => 'PostmanRuntime/7.44.0', // ← EXACTO como Postman
                    'Accept' => '*/*', // ← EXACTO como Postman
                    'Accept-Encoding' => 'gzip, deflate, br', // ← EXACTO como Postman
                    'Connection' => 'keep-alive' // ← EXACTO como Postman
                ])
                ->post(config('services.apisperu.base_url') . '/api/v1/auth/login', [
                    'username' => config('services.apisperu.email'),
                    'password' => config('services.apisperu.password'),
                ]);

            if (!$response->successful()) {
                throw new \Exception('Login falló con headers de Postman: ' . $response->body());
            }

            Log::info('[getTokenReplicaPostman] Token obtenido con headers de Postman');
            return $response->json('token');
        });
    }
}

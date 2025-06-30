<?php

namespace App\Http\Controllers\Ventas_Pagos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\ventas_Pagos\Boletas;
use App\Models\Pedidos\Pedidos;

class BoletasController extends Controller
{
    // Listar todas las boletas con sus relaciones
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

    // Crear boleta, asociar pagos y descontar stock
    public function store(Request $request)
    {
        $request->validate([
            'ped_id'             => 'required|exists:Pedidos,ped_id',
            'boleta_numero'      => 'required|string|max:20',
            'boleta_notas'       => 'nullable|string',
            'payment_id'         => 'nullable|string',
            'pagos'              => 'required|array|min:1',
            'pagos.*.met_id'     => 'required|exists:Metodos_Pago,met_id',
            'pagos.*.monto'      => 'required|numeric|min:0.01',
            'pagos.*.fecha_pago' => 'required|date',
            'pagos.*.nota_pago'  => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $pedido = Pedidos::with('detalles.producto')->findOrFail($request->ped_id);
            $boleta_id = substr(uniqid('BOL-'), 0, 15);

            $boleta = Boletas::create([
                'boleta_id'        => $boleta_id,
                'boleta_numero'    => $request->boleta_numero,
                'boleta_fecha'     => now(),
                'boleta_subtotal'  => $pedido->ped_subtotal,
                'boleta_impuestos' => $pedido->ped_impuestos,
                'boleta_descuento' => $pedido->ped_descuento,
                'boleta_total'     => $pedido->ped_total,
                'boleta_estado'    => 'Emitido',
                'boleta_notas'     => $request->boleta_notas,
                'payment_id'       => $request->payment_id,
                'ped_id'           => $pedido->ped_id,
            ]);

            // Registrar métodos de pago
            foreach ($request->pagos as $pago) {
                $boleta->metodosPago()->attach($pago['met_id'], [
                    'monto'          => $pago['monto'],
                    'referencia'     => $pago['nota_pago'] ?? null,
                    'fecha_registro' => $pago['fecha_pago'],
                ]);
            }

            // Descontar stock
            foreach ($pedido->detalles as $detalle) {
                $producto = $detalle->producto;
                if ($producto->pro_stock < $detalle->det_cantidad) {
                    throw new \Exception("Stock insuficiente para {$producto->pro_nombre}");
                }
                $producto->decrement('pro_stock', $detalle->det_cantidad);
            }

            DB::commit();

            $boletaFull = Boletas::with([
                'metodosPago',
                'pedido.detalles.producto',
                'pedido.cliente'
            ])->findOrFail($boleta_id);

            return response()->json([
                'message' => 'Boleta registrada exitosamente',
                'boleta'  => $boletaFull
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar la boleta.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // Mostrar boleta específica
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

    public function cancelar($id)
    {
        try {
            // Usar el procedimiento almacenado que maneja toda la lógica
            $resultado = DB::select('CALL sp_cancelar_boleta_completo(?)', [$id]);

            if (empty($resultado)) {
                return response()->json([
                    'message' => 'No se pudo procesar la cancelación de la boleta.'
                ], 500);
            }

            $data = $resultado[0];

            return response()->json([
                'message' => 'Boleta cancelada correctamente',
                'boleta_id' => $data->boleta_cancelada_id,
                'pedido_anulado_id' => $data->pedido_anulado_id,
                'nuevo_pedido_id' => $data->nuevo_pedido_id,
                'cliente_nombre' => $data->cliente_nombre
            ], 200);

        } catch (\Exception $e) {

            \Log::error('Error al cancelar boleta: ' . $e->getMessage(), [
                'boleta_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error al cancelar la boleta.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
   
    public function emitirSunatXml($id)
    {
        try {
            // 1) Recuperar boleta con relaciones
            $boleta   = Boletas::with(['pedido.cliente', 'pedido.detalles.producto'])->findOrFail($id);
            $cliente  = $boleta->pedido->cliente;
            $detalles = $boleta->pedido->detalles;

            // 2) Preparar datos para la vista
            $serie  = substr($boleta->boleta_numero, 0, 4);
            $numero = intval(substr($boleta->boleta_numero, 4));
            $fecha  = $boleta->boleta_fecha->format('Y-m-d');

            // 3) Renderizar la vista Blade que genera el UBL/XML
            $xml = view('sunat.boleta', compact('boleta', 'cliente', 'detalles', 'serie', 'numero', 'fecha'))
                ->render();

            // 4) Codificar en Base64
            $xmlBase64 = base64_encode($xml);

            // 5) Armar payload
            $body = [
                'personaId'    => config('services.apisunat.persona_id'),
                'personaToken' => config('services.apisunat.token'),
                'contentFile'  => $xmlBase64,
                'fileName'     => "{$serie}-" . str_pad($numero, 8, '0', STR_PAD_LEFT) . ".xml",
            ];

            // 6) Enviar al endpoint
            $response = Http::withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->timeout(60)
                ->post(config('services.apisunat.xml_url'), $body);

            // 7) Validar resultado
            if (! $response->successful()) {
                return response()->json([
                    'message' => 'Error al enviar XML a APISUNAT',
                    'error'   => $response->json() ?: $response->body(),
                ], 500);
            }

            $data = $response->json();

            // 8) Si devuelve PDF en Base64
            if (! empty($data['contentFile'])) {
                $pdf = base64_decode($data['contentFile']);
                return response($pdf, 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'attachment; filename="' . ($serie . '-' . str_pad($numero, 8, '0', STR_PAD_LEFT)) . '.pdf"');
            }

            // 9) Si devuelve URL de descarga
            if (! empty($data['urlPdf'])) {
                return response()->json([
                    'message' => 'Boleta generada, descarga aquí',
                    'url'     => $data['urlPdf'],
                ], 200);
            }

            // 10) Respuesta genérica
            return response()->json([
                'message'  => 'Boleta enviada correctamente a APISUNAT',
                'response' => $data,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno al emitir boleta',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function descargarPdfSunat($documentId, $fileName)
    {
        $url = "https://back.apisunat.com/documents/{$documentId}/getPDF/A4/{$fileName}.pdf";
        $response = Http::withHeaders([
            'personaId'    => config('services.apisunat.persona_id'),
            'Authorization' => 'Bearer '.config('services.apisunat.token'),
        ])
            ->accept('application/pdf')
            ->get($url);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Error al descargar PDF de APISUNAT',
                'error'   => $response->body()
            ], 500);
        }

        return response($response->body(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$fileName}.pdf\"");
    }

    // Buscar boleta por payment_id
    public function buscarPorPaymentId($paymentId)
    {
        try {
            $boleta = Boletas::with([
                'metodosPago',
                'pedido.detalles.producto',
                'pedido.cliente'
            ])->where('payment_id', $paymentId)->firstOrFail();

            return response()->json($boleta, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Boleta no encontrada por payment_id.',
                'error'   => $e->getMessage()
            ], 404);
        }
    }
}

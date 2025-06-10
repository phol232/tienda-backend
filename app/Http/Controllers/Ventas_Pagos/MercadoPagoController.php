<?php

namespace App\Http\Controllers\Ventas_Pagos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPagoController extends Controller
{
    private $accessToken;
    private $publicKey;

    public function __construct()
    {
        $this->accessToken = env('MERCADOPAGO_ACCESS_TOKEN');
        $this->publicKey = env('MERCADOPAGO_PUBLIC_KEY');
    }

    public function createPreference(Request $request)
    {
        try {
            $request->validate([
                'items' => 'required|array',
                'items.*.title' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'payer' => 'required|array',
                'payer.email' => 'required|email',
                'external_reference' => 'required|string',
                'back_urls' => 'required|array',
                'back_urls.success' => 'required|url',
                'back_urls.failure' => 'required|url',
                'back_urls.pending' => 'required|url'
            ]);

            // Convierte back_urls a array plano por si acaso (Laravel puede enviarlo como Collection)
            $backUrls = is_array($request->back_urls) ? $request->back_urls : $request->input('back_urls');

            // Opcional: log para depuración
            Log::info('Payload recibido para preferencia MercadoPago:', $request->all());
            Log::info('back_urls enviado:', $backUrls);

            $preferenceData = [
                'items' => $request->items,
                'payer' => $request->payer,
                'external_reference' => $request->external_reference,
                'back_urls' => $backUrls,
                'auto_return' => 'approved',
                'notification_url' => env('APP_URL') . '/api/mercadopago/webhook',
                'statement_descriptor' => 'TU_NEGOCIO',
                'expires' => true,
                'expiration_date_from' => now()->toISOString(),
                'expiration_date_to' => now()->addHours(24)->toISOString()
            ];

            // Opcional: log para depuración
            Log::info('Payload enviado a MercadoPago:', $preferenceData);

            // Envía la preferencia a la API de Mercado Pago
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json'
            ])->post('https://api.mercadopago.com/checkout/preferences', $preferenceData);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'status' => true,
                    'preference_id' => $data['id'],
                    'init_point' => $data['init_point'],
                    'sandbox_init_point' => $data['sandbox_init_point']
                ]);
            } else {
                Log::error('MercadoPago API Error:', $response->json());
                return response()->json([
                    'status' => false,
                    'message' => 'Error al crear preferencia de pago',
                    'error' => $response->json()
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('MercadoPago Exception:', ['message' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPaymentInfo($paymentId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->accessToken
            ])->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if ($response->successful()) {
                return response()->json([
                    'status' => true,
                    'payment' => $response->json()
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Pago no encontrado'
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Error getting payment info:', ['message' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener información del pago'
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        try {
            $data = $request->all();
            Log::info('MercadoPago Webhook received:', $data);

            if (isset($data['type']) && $data['type'] === 'payment') {
                $paymentId = $data['data']['id'];

                // Aquí puedes procesar el pago
                // Por ejemplo, actualizar el estado del pedido
                Log::info('Payment processed:', ['payment_id' => $paymentId]);
            }

            return response()->json(['status' => 'ok'], 200);
        } catch (\Exception $e) {
            Log::error('Webhook error:', ['message' => $e->getMessage()]);
            return response()->json(['status' => 'error'], 500);
        }
    }
}

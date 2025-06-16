<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ApisPeruService
{
    protected string $baseUrl;
    protected string $email;
    protected string $password;

    public function __construct()
    {
        $this->baseUrl  = config('services.apisperu.base_url');
        $this->email    = config('services.apisperu.email');
        $this->password = config('services.apisperu.password');
    }

    /**
     * Forzar la renovación del token (solo para debug).
     */
    public function forceRenewToken()
    {
        Cache::forget('apisperu_token');
    }

    /**
     * Obtener y cachear token por 50 minutos.
     */
    public function getToken(): string
    {
        return Cache::remember('apisperu_token', now()->addMinutes(50), function () {
            $response = Http::acceptJson()
                ->timeout(30)
                ->post("{$this->baseUrl}/api/v1/auth/login", [
                    'username' => $this->email,
                    'password' => $this->password,
                ]);

            \Log::info('[APISPERU] Login response', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            if (! $response->successful()) {
                throw new \Exception('Login APISPERU fallido: ' . $response->body());
            }

            $token = $response->json('token');
            \Log::info('[APISPERU] Nuevo token obtenido', ['token' => $token]);
            return $token;
        });
    }

    /**
     * Emitir boleta o factura.
     */
    public function sendInvoice(array $payload)
    {
        // SOLO PARA PRUEBA: borra el cache antes de pedir el token
        $this->forceRenewToken();

        $token = $this->getToken();
        \Log::info('[APISPERU] Token usado para emisión', ['token' => $token]);

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(60)
            ->post("{$this->baseUrl}/api/v1/invoice/send", $payload);

        \Log::debug('[sendInvoice] APISPERU raw response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        if (! $response->successful()) {
            throw new \Exception(
                'Error al emitir comprobante: ' . $response->body()
            );
        }

        return $response->json();
    }
}

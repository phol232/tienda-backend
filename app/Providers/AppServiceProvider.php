<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Configuración para Cloudflare
        if ($this->app->environment('production')) {
            // Forzar HTTPS siempre
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
            
            // Configurar request para Cloudflare
            $request = request();
            $request->server->set('HTTPS', 'on');
            $request->server->set('SERVER_PORT', 443);
            
            // Si viene de Cloudflare, usar la IP real
            if ($request->hasHeader('CF-Connecting-IP')) {
                $request->server->set('REMOTE_ADDR', $request->header('CF-Connecting-IP'));
            }
        }
    }
}

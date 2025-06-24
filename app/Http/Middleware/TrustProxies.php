<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /** Confiamos en *todos* los proxies */
    protected $proxies = '*';

    /** Le decimos a Laravel que respete X-Forwarded-Proto, etc. */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}

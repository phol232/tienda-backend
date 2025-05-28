<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, string>>
     */
    protected $listen = [
        // Aquí puedes añadir otros eventos de tu app…
        
        // Este escucha cuando Socialite carga y registra el driver Microsoft
        SocialiteWasCalled::class => [
            'SocialiteProviders\\Microsoft\\MicrosoftExtendSocialite@handle',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
        //
    }
}

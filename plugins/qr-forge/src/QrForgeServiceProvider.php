<?php

namespace Techysavvy\QrForge;

use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\ToolRegistry;

class QrForgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'qr-forge');

        $this->app->make(ToolRegistry::class)->register(new QrForge());
    }
}

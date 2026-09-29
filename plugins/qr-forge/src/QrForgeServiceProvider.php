<?php

namespace Techysavvy\QrForge;

use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\Assets\AssetBundle;
use Techysavvy\Core\Assets\AssetRegistry;
use Techysavvy\Core\ToolRegistry;

class QrForgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'qr-forge');

        $this->app->make(ToolRegistry::class)->register(new QrForge());

        $this->app->make(AssetRegistry::class)->register('qr-forge', new AssetBundle(
            directory: __DIR__.'/../resources/dist',
            scripts: ['qr-forge.js'],
        ));
    }
}

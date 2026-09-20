<?php

namespace Techysavvy\EnvDiff;

use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\Assets\AssetBundle;
use Techysavvy\Core\Assets\AssetRegistry;
use Techysavvy\Core\ToolRegistry;

class EnvDiffServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'env-diff');

        $this->app->make(ToolRegistry::class)->register(new EnvDiff());

        $this->app->make(AssetRegistry::class)->register('env-diff', new AssetBundle(
            directory: __DIR__.'/../resources/dist',
            scripts: ['env-diff.js'],
        ));
    }
}

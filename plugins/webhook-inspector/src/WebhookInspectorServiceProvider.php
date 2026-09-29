<?php

namespace Techysavvy\WebhookInspector;

use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\ToolRegistry;

class WebhookInspectorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/webhook-inspector.php', 'webhook-inspector');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'webhook-inspector');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->app->make(ToolRegistry::class)->register(new WebhookInspector());
    }
}

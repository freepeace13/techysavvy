<?php

namespace Techysavvy\WebhookInspector;

use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\ToolRegistry;

class WebhookInspectorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'webhook-inspector');

        $this->app->make(ToolRegistry::class)->register(new WebhookInspector());
    }
}

<?php

namespace Techysavvy\WebhookInspector;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('webhook-inspector-create', fn (Request $request) => Limit::perMinute(
            (int) config('webhook-inspector.create_rate_per_minute')
        )->by($request->ip()));

        RateLimiter::for('webhook-inspector-capture', fn (Request $request) => Limit::perMinute(
            (int) config('webhook-inspector.capture_rate_per_minute')
        )->by('webhook-inspector-capture:'.$request->route('binId'))->response(
            fn () => response()->json(['error' => 'Too many requests.'], 429)->header('Access-Control-Allow-Origin', '*')
        ));
    }
}

<?php

namespace Techysavvy\WebhookInspector;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\Assets\AssetBundle;
use Techysavvy\Core\Assets\AssetRegistry;
use Techysavvy\Core\ToolRegistry;
use Techysavvy\WebhookInspector\Console\PruneExpiredBinsCommand;
use Techysavvy\WebhookInspector\Http\Controllers\CaptureController;

class WebhookInspectorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/webhook-inspector.php', 'webhook-inspector');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'webhook-inspector');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->app->make(ToolRegistry::class)->register(new WebhookInspector());

        $this->app->make(AssetRegistry::class)->register('webhook-inspector', new AssetBundle(
            directory: __DIR__.'/../resources/dist',
            scripts: ['webhook-inspector.js'],
        ));

        RateLimiter::for('webhook-inspector-create', fn (Request $request) => Limit::perMinute(
            (int) config('webhook-inspector.create_rate_per_minute')
        )->by($request->ip()));

        RateLimiter::for('webhook-inspector-capture', fn (Request $request) => Limit::perMinute(
            (int) config('webhook-inspector.capture_rate_per_minute')
        )->by('webhook-inspector-capture:'.$request->route('binId'))->response(
            fn () => CaptureController::withCors(response()->json(['error' => 'Too many requests.'], 429))
        ));

        if ($this->app->runningInConsole()) {
            $this->commands([PruneExpiredBinsCommand::class]);
        }

        $this->app->booted(function () {
            $interval = max(1, (int) config('webhook-inspector.prune_interval_minutes'));

            $this->app->make(Schedule::class)
                ->command(PruneExpiredBinsCommand::class)
                ->cron("*/{$interval} * * * *");
        });
    }
}

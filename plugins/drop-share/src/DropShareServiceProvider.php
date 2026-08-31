<?php

namespace Techysavvy\DropShare;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\ToolRegistry;

class DropShareServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/drop-share.php', 'drop-share');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'drop-share');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->app->make(ToolRegistry::class)->register(new DropShareTool());

        RateLimiter::for('drop-share-uploads', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}

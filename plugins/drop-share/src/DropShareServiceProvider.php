<?php

namespace Techysavvy\DropShare;

use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\ToolRegistry;

class DropShareServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/drop-share.php', 'drop-share');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'drop-share');

        $this->app->make(ToolRegistry::class)->register(new DropShareTool());
    }
}

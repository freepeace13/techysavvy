<?php

namespace Techysavvy\HelloTool;

use Illuminate\Support\ServiceProvider;
use Techysavvy\ToolRegistry\ToolRegistry;

class HelloToolServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'hello-tool');

        $this->app->make(ToolRegistry::class)->register(new HelloTool());
    }
}

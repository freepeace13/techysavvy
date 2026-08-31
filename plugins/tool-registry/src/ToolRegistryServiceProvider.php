<?php

namespace Techysavvy\ToolRegistry;

use Illuminate\Support\ServiceProvider;

class ToolRegistryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ToolRegistry::class);
    }
}

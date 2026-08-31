<?php

namespace Techysavvy\SharedUi;

use Illuminate\Support\ServiceProvider;

class SharedUiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'brand');
    }
}

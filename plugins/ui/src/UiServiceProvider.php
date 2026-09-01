<?php

namespace Techysavvy\Ui;

use Illuminate\Support\ServiceProvider;

class UiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/ui.php', 'ui');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'brand');

        $this->publishes([
            __DIR__.'/../config/ui.php' => config_path('ui.php'),
        ], 'ui-config');
    }
}

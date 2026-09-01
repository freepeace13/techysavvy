<?php

namespace Techysavvy\PhotoTweaker;

use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\ToolRegistry;

class PhotoTweakerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'photo-tweaker');

        $this->app->make(ToolRegistry::class)->register(new PhotoTweaker());
    }
}

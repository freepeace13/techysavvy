<?php

namespace Techysavvy\Core;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\Assets\AssetRegistry;
use Techysavvy\Core\Assets\RequiredAssets;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ToolRegistry::class);
        $this->app->singleton(AssetRegistry::class);
        $this->app->scoped(RequiredAssets::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'core');

        Blade::directive('pluginAssets', fn (string $expression) => "<?php app(\\Techysavvy\\Core\\Assets\\RequiredAssets::class)->add({$expression}); ?>");
    }
}

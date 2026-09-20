<?php

namespace Techysavvy\DocToMarkdown;

use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\Assets\AssetBundle;
use Techysavvy\Core\Assets\AssetRegistry;
use Techysavvy\Core\ToolRegistry;

class DocToMarkdownServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/doc-to-markdown.php', 'doc-to-markdown');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'doc-to-markdown');

        $this->app->make(ToolRegistry::class)->register(new DocToMarkdownTool());

        // The plugin builds its own JS (see package.json / vite.config.js); core serves
        // the bundle straight from resources/dist and emits the tag for pages that
        // call @pluginAssets('doc-to-markdown').
        $this->app->make(AssetRegistry::class)->register('doc-to-markdown', new AssetBundle(
            directory: __DIR__.'/../resources/dist',
            scripts: ['doc-to-markdown.js'],
        ));
    }
}

<?php

namespace Techysavvy\DocToMarkdown;

use Illuminate\Support\ServiceProvider;
use Techysavvy\Core\ToolRegistry;

class DocToMarkdownServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/doc-to-markdown.php', 'doc-to-markdown');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'doc-to-markdown');

        $this->app->make(ToolRegistry::class)->register(new DocToMarkdownTool());

        // The plugin builds its own JS (see package.json / vite.config.js) and
        // ships the bundle as a static file from the host's public directory.
        $this->publishes([
            __DIR__.'/../resources/dist' => public_path('vendor/doc-to-markdown'),
        ], 'doc-to-markdown-assets');
    }
}

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
    }
}

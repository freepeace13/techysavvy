<?php

namespace Techysavvy\Core\Assets;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

/**
 * Builds the <link>/<script> markup for the bundles the current request required.
 * Resolve fresh from the container per render (not a singleton): it reads the
 * request-scoped RequiredAssets.
 */
class AssetRenderer
{
    public function __construct(
        private readonly AssetRegistry $registry,
        private readonly RequiredAssets $required,
    ) {
    }

    public function styles(): HtmlString
    {
        $tags = [];

        foreach ($this->required->names() as $name) {
            foreach ($this->registry->get($name)->styles() as $file) {
                $tags[] = $this->tag($name, $file, fn (string $url) => '<link rel="stylesheet" href="'.e($url).'">');
            }
        }

        return new HtmlString(implode("\n", array_filter($tags)));
    }

    public function scripts(): HtmlString
    {
        $tags = [];

        foreach ($this->required->names() as $name) {
            foreach ($this->registry->get($name)->scripts() as $file => $options) {
                // No `defer`: a plain classic script at the end of <body> runs during
                // parse, i.e. before the layout's deferred Alpine module starts.
                $type = $options['module'] ? ' type="module"' : '';

                $tags[] = $this->tag($name, $file, fn (string $url) => '<script'.$type.' src="'.e($url).'"></script>');
            }
        }

        return new HtmlString(implode("\n", array_filter($tags)));
    }

    /** @param  callable(string): string  $render */
    private function tag(string $bundle, string $file, callable $render): string
    {
        if (! is_file($this->registry->get($bundle)->path($file))) {
            Log::warning("techysavvy/core: asset {$bundle}/{$file} is declared but missing on disk (not built?).");

            return config('app.debug')
                ? '<!-- techysavvy/core: missing asset '.e("{$bundle}/{$file}").' -->'
                : '';
        }

        return $render($this->registry->url($bundle, $file));
    }
}

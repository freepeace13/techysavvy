<?php

namespace Techysavvy\Core\Tests\Feature;

use Techysavvy\Core\Assets\AssetBundle;
use Techysavvy\Core\Assets\AssetRegistry;
use Techysavvy\Core\Tests\TestCase;

class RenderOrderTest extends TestCase
{
    public function test_a_directive_inside_the_slot_is_honoured_by_the_layouts_emit_points(): void
    {
        view()->addNamespace('probe', __DIR__.'/../fixtures/views');
        $this->app->make(AssetRegistry::class)->register('demo', new AssetBundle(
            $this->makeBundleDir(['demo.js' => 'x', 'demo.css' => 'y']),
            ['demo.js'],
            ['demo.css'],
        ));

        $html = view('probe::page')->render();

        $this->assertMatchesRegularExpression('~<head><link rel="stylesheet" href="/_plugin-assets/demo/demo\.css[^"]*">\s*</head>~', $html);
        $this->assertMatchesRegularExpression('~<script src="/_plugin-assets/demo/demo\.js[^"]*"></script>\s*</body>~', $html);
    }

    public function test_a_page_that_requires_nothing_emits_no_tags(): void
    {
        view()->addNamespace('probe', __DIR__.'/../fixtures/views');

        $html = view('probe::components.layout', ['slot' => ''])->render();

        $this->assertStringNotContainsString('<link', $html);
        $this->assertStringNotContainsString('<script', $html);
    }
}

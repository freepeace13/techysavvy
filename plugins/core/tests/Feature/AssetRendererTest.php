<?php

namespace Techysavvy\Core\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Techysavvy\Core\Assets\AssetBundle;
use Techysavvy\Core\Assets\AssetRegistry;
use Techysavvy\Core\Assets\RequiredAssets;
use Techysavvy\Core\Tests\TestCase;

class AssetRendererTest extends TestCase
{
    private function register(string $name, array $files, array $scripts, array $styles = []): void
    {
        $this->app->make(AssetRegistry::class)->register(
            $name,
            new AssetBundle($this->makeBundleDir($files), $scripts, $styles),
        );
    }

    private function require(string ...$names): void
    {
        foreach ($names as $name) {
            $this->app->make(RequiredAssets::class)->add($name);
        }
    }

    public function test_it_renders_nothing_when_nothing_is_required(): void
    {
        $this->register('demo', ['a.js' => 'x'], ['a.js']);

        $this->assertSame('', trim(Blade::render('<x-core::assets.styles /><x-core::assets.scripts />')));
    }

    public function test_it_renders_link_tags_for_required_styles(): void
    {
        $this->register('demo', ['a.css' => 'x'], [], ['a.css']);
        $this->require('demo');

        $html = Blade::render('<x-core::assets.styles />');

        $this->assertMatchesRegularExpression('~<link rel="stylesheet" href="/_plugin-assets/demo/a\.css\?v=[0-9a-f]{12}">~', $html);
    }

    public function test_it_renders_classic_scripts_without_defer_and_module_scripts_as_modules(): void
    {
        $this->register('demo', ['a.js' => 'x', 'm.js' => 'y'], ['a.js', 'm.js' => ['module' => true]]);
        $this->require('demo');

        $html = Blade::render('<x-core::assets.scripts />');

        $this->assertMatchesRegularExpression('~<script src="/_plugin-assets/demo/a\.js\?v=[0-9a-f]{12}"></script>~', $html);
        $this->assertMatchesRegularExpression('~<script type="module" src="/_plugin-assets/demo/m\.js\?v=[0-9a-f]{12}"></script>~', $html);
        $this->assertStringNotContainsString('defer', $html);
    }

    public function test_it_emits_bundles_in_first_required_order_without_duplicates(): void
    {
        $this->register('one', ['a.js' => 'x'], ['a.js']);
        $this->register('two', ['b.js' => 'y'], ['b.js']);
        $this->require('two', 'one', 'two');

        $html = Blade::render('<x-core::assets.scripts />');

        $this->assertSame(1, substr_count($html, '/two/b.js'));
        $this->assertLessThan(strpos($html, '/one/a.js'), strpos($html, '/two/b.js'));
    }

    public function test_the_fingerprint_changes_when_the_file_changes(): void
    {
        $dir = $this->makeBundleDir(['a.js' => 'first']);
        $this->app->make(AssetRegistry::class)->register('demo', new AssetBundle($dir, ['a.js']));
        $this->require('demo');

        $before = Blade::render('<x-core::assets.scripts />');
        file_put_contents($dir.'/a.js', 'second build');

        $this->assertNotSame($before, Blade::render('<x-core::assets.scripts />'));
    }

    public function test_a_missing_file_is_skipped_and_logged_and_leaves_a_comment_only_in_debug(): void
    {
        Log::shouldReceive('warning')->twice()->withArgs(fn (string $message) => str_contains($message, 'demo/a.js'));

        $this->register('demo', [], ['a.js']);
        $this->require('demo');

        config(['app.debug' => false]);
        $quiet = Blade::render('<x-core::assets.scripts />');

        config(['app.debug' => true]);
        $debug = Blade::render('<x-core::assets.scripts />');

        $this->assertStringNotContainsString('<script', $quiet);
        $this->assertStringNotContainsString('<!--', $quiet);
        $this->assertStringNotContainsString('<script', $debug);
        $this->assertStringContainsString('<!-- techysavvy/core: missing asset demo/a.js -->', $debug);
    }
}

<?php

namespace Techysavvy\Core\Tests\Feature;

use Techysavvy\Core\Assets\AssetBundle;
use Techysavvy\Core\Assets\AssetRegistry;
use Techysavvy\Core\Tests\TestCase;

class AssetRouteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $dir = $this->makeBundleDir(['app.js' => 'console.log(1);', 'app.css' => 'body{}', 'secret.txt' => 'nope']);
        $this->app->make(AssetRegistry::class)->register('demo', new AssetBundle(
            $dir,
            ['app.js', 'gone.js'],
            ['app.css'],
        ));
    }

    public function test_it_serves_a_declared_script_with_cache_headers_and_an_etag(): void
    {
        $url = $this->app->make(AssetRegistry::class)->url('demo', 'app.js');
        $response = $this->get($url);

        $response->assertOk();
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertStringContainsString('text/javascript', $response->headers->get('Content-Type'));
        $cache = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cache);
        $this->assertStringContainsString('max-age=31536000', $cache);
        $this->assertStringContainsString('immutable', $cache);
        $this->assertNotEmpty($response->headers->get('ETag'));
    }

    public function test_an_unversioned_or_stale_url_is_not_cached_as_immutable(): void
    {
        foreach (['/_plugin-assets/demo/app.js', '/_plugin-assets/demo/app.js?v=stale'] as $url) {
            $cache = $this->get($url)->assertOk()->headers->get('Cache-Control');
            $this->assertStringNotContainsString('immutable', $cache);
            $this->assertStringContainsString('no-cache', $cache);
        }
    }

    public function test_it_serves_a_declared_stylesheet_as_css(): void
    {
        $response = $this->get('/_plugin-assets/demo/app.css');

        $response->assertOk();
        $this->assertStringContainsString('text/css', $response->headers->get('Content-Type'));
    }

    public function test_it_answers_a_matching_if_none_match_with_304(): void
    {
        $etag = $this->get('/_plugin-assets/demo/app.js')->headers->get('ETag');

        $this->get('/_plugin-assets/demo/app.js', ['If-None-Match' => $etag])->assertStatus(304);
    }

    public function test_the_route_is_named_core_assets(): void
    {
        $this->assertSame(
            '/_plugin-assets/demo/app.js',
            route('core.assets', ['bundle' => 'demo', 'file' => 'app.js'], absolute: false),
        );
    }

    public function test_it_404s_for_an_unknown_bundle(): void
    {
        $this->get('/_plugin-assets/nope/app.js')->assertNotFound();
    }

    public function test_it_404s_for_an_undeclared_file_even_if_it_exists_on_disk(): void
    {
        $this->get('/_plugin-assets/demo/secret.txt')->assertNotFound();
    }

    public function test_it_404s_for_a_declared_file_that_is_missing_on_disk(): void
    {
        $this->get('/_plugin-assets/demo/gone.js')->assertNotFound();
    }

    public function test_it_404s_for_path_traversal_attempts(): void
    {
        $this->get('/_plugin-assets/demo/..%2Fsecret.txt')->assertNotFound();
        $this->get('/_plugin-assets/demo/../secret.txt')->assertNotFound();
    }
}

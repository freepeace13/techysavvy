<?php

namespace Techysavvy\Core\Tests\Feature;

use InvalidArgumentException;
use LogicException;
use Techysavvy\Core\Assets\AssetBundle;
use Techysavvy\Core\Assets\AssetRegistry;
use Techysavvy\Core\Tests\TestCase;

class AssetRegistryTest extends TestCase
{
    private function registry(): AssetRegistry
    {
        return $this->app->make(AssetRegistry::class);
    }

    public function test_it_is_a_singleton(): void
    {
        $this->assertSame($this->registry(), $this->registry());
    }

    public function test_it_registers_and_returns_bundles(): void
    {
        $bundle = new AssetBundle('/tmp/dist', ['a.js']);
        $this->registry()->register('demo', $bundle);

        $this->assertTrue($this->registry()->has('demo'));
        $this->assertSame($bundle, $this->registry()->get('demo'));
        $this->assertSame(['demo' => $bundle], $this->registry()->all());
        $this->assertNull($this->registry()->get('nope'));
    }

    public function test_registering_the_same_name_twice_throws(): void
    {
        $this->registry()->register('demo', new AssetBundle('/tmp/dist', ['a.js']));

        $this->expectException(LogicException::class);
        $this->registry()->register('demo', new AssetBundle('/tmp/other', ['b.js']));
    }

    public function test_it_rejects_invalid_bundle_names(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->registry()->register('Bad Name', new AssetBundle('/tmp/dist', ['a.js']));
    }

    public function test_url_points_at_the_route_and_carries_a_content_fingerprint(): void
    {
        $dir = $this->makeBundleDir(['a.js' => 'first']);
        $this->registry()->register('demo', new AssetBundle($dir, ['a.js']));

        $first = $this->registry()->url('demo', 'a.js');

        $this->assertStringStartsWith('/_plugin-assets/demo/a.js?v=', $first);
        $this->assertSame(12, strlen(substr($first, strlen('/_plugin-assets/demo/a.js?v='))));

        file_put_contents($dir.'/a.js', 'second, different');

        $this->assertNotSame($first, $this->registry()->url('demo', 'a.js'));
    }

    public function test_url_omits_the_fingerprint_when_the_file_is_missing(): void
    {
        $this->registry()->register('demo', new AssetBundle($this->makeBundleDir([]), ['a.js']));

        $this->assertSame('/_plugin-assets/demo/a.js', $this->registry()->url('demo', 'a.js'));
    }
}

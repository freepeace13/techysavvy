<?php

namespace Techysavvy\Core\Tests\Feature;

use InvalidArgumentException;
use Techysavvy\Core\Assets\AssetBundle;
use Techysavvy\Core\Assets\AssetRegistry;
use Techysavvy\Core\Assets\RequiredAssets;
use Techysavvy\Core\Tests\TestCase;

class RequiredAssetsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $registry = $this->app->make(AssetRegistry::class);
        $registry->register('one', new AssetBundle('/tmp/one', ['a.js']));
        $registry->register('two', new AssetBundle('/tmp/two', ['b.js']));
    }

    public function test_it_keeps_first_required_order_and_dedupes(): void
    {
        $required = $this->app->make(RequiredAssets::class);
        $required->add('two');
        $required->add('one');
        $required->add('two');

        $this->assertSame(['two', 'one'], $required->names());
    }

    public function test_requiring_an_unknown_bundle_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->app->make(RequiredAssets::class)->add('missing');
    }

    public function test_state_is_scoped_per_request_but_registrations_survive(): void
    {
        $this->app->make(RequiredAssets::class)->add('one');

        // What Octane / queue workers do between requests.
        $this->app->forgetScopedInstances();

        $this->assertSame([], $this->app->make(RequiredAssets::class)->names());
        $this->assertTrue($this->app->make(AssetRegistry::class)->has('one'));
    }
}

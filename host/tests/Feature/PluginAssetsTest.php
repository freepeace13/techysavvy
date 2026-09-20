<?php

namespace Tests\Feature;

use Techysavvy\Core\Assets\AssetRegistry;
use Tests\TestCase;

class PluginAssetsTest extends TestCase
{
    /**
     * Plugin-agnostic, like ToolListingTest: walks whatever bundles are registered
     * rather than naming one. A bundle whose files are not built on this checkout
     * is skipped file by file; the route answering 404 for those is core's own test.
     */
    public function test_every_built_plugin_asset_is_served_through_the_core_route(): void
    {
        $registry = $this->app->make(AssetRegistry::class);
        $served = 0;

        foreach ($registry->all() as $name => $bundle) {
            foreach ($bundle->files() as $file) {
                if (! is_file($bundle->path($file))) {
                    continue;
                }

                $this->get($registry->url($name, $file))
                    ->assertOk()
                    ->assertHeader('ETag');
                $served++;
            }
        }

        if ($served === 0) {
            $this->markTestSkipped('No built plugin assets on this checkout.');
        }
    }
}

<?php

namespace Tests\Feature;

use Techysavvy\Core\ToolRegistry;
use Tests\TestCase;

class ToolListingTest extends TestCase
{
    /**
     * Deliberately plugin-agnostic: asserts over whatever is currently in
     * the ToolRegistry rather than naming a specific tool, so this test
     * doesn't need editing (and can't go stale) when a plugin is added
     * or removed. Plugin-specific assertions belong in that plugin's own
     * tests, not here.
     */
    public function test_home_page_lists_every_registered_tool(): void
    {
        $tools = $this->app->make(ToolRegistry::class)->all();

        $this->assertNotEmpty($tools, 'Expected at least one tool to be registered.');

        $response = $this->get('/');
        $response->assertOk();

        foreach ($tools as $tool) {
            $response->assertSee($tool->name());
            $response->assertSee($tool->description());
            $response->assertSee($tool->url(), escape: false);
        }
    }

    public function test_every_registered_tools_own_route_responds(): void
    {
        $tools = $this->app->make(ToolRegistry::class)->all();

        foreach ($tools as $tool) {
            $this->get($tool->url())->assertOk();
        }
    }
}

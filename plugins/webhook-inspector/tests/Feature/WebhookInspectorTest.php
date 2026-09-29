<?php

namespace Techysavvy\WebhookInspector\Tests\Feature;

use Techysavvy\Core\ToolRegistry;
use Techysavvy\WebhookInspector\Tests\TestCase;
use Techysavvy\WebhookInspector\WebhookInspector;

class WebhookInspectorTest extends TestCase
{
    public function test_it_registers_itself_in_the_tool_registry(): void
    {
        $names = collect($this->app->make(ToolRegistry::class)->all())->map(fn ($tool) => $tool->name())->all();

        $this->assertContains((new WebhookInspector())->name(), $names);
    }

    public function test_home_route_renders_the_tool(): void
    {
        $this->get((new WebhookInspector())->url())
            ->assertOk()
            ->assertSee('Webhook Inspector');
    }
}

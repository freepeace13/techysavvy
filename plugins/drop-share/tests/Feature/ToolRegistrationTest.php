<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Techysavvy\Core\ToolRegistry;
use Techysavvy\DropShare\DropShareTool;
use Techysavvy\DropShare\Tests\TestCase;

class ToolRegistrationTest extends TestCase
{
    public function test_it_registers_itself_into_the_tool_registry(): void
    {
        $tools = $this->app->make(ToolRegistry::class)->all();

        $dropShare = $tools->first(fn ($tool) => $tool instanceof DropShareTool);

        $this->assertNotNull($dropShare);
        $this->assertSame('Drop Share', $dropShare->name());
        $this->assertSame('📦', $dropShare->icon());
    }

    public function test_home_route_responds_successfully(): void
    {
        $response = $this->get('/drop-share');

        $response->assertOk();
        $response->assertSee('Drop Share');
    }
}

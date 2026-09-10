<?php

namespace Techysavvy\DocToMarkdown\Tests\Feature;

use Techysavvy\Core\ToolRegistry;
use Techysavvy\DocToMarkdown\DocToMarkdownTool;
use Techysavvy\DocToMarkdown\Tests\TestCase;

class ToolRegistrationTest extends TestCase
{
    public function test_it_registers_itself_into_the_tool_registry(): void
    {
        $tools = $this->app->make(ToolRegistry::class)->all();

        $tool = $tools->first(fn ($tool) => $tool instanceof DocToMarkdownTool);

        $this->assertNotNull($tool);
        $this->assertSame('Doc to Markdown', $tool->name());
        $this->assertSame('📝', $tool->icon());
    }

    public function test_home_route_responds_successfully(): void
    {
        $response = $this->get('/doc-to-markdown');

        $response->assertOk();
        $response->assertSee('Doc to Markdown');
    }
}

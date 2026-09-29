<?php

namespace Techysavvy\QrForge\Tests\Feature;

use Techysavvy\Core\ToolRegistry;
use Techysavvy\QrForge\QrForge;
use Techysavvy\QrForge\Tests\TestCase;

class QrForgeTest extends TestCase
{
    public function test_it_registers_itself_in_the_tool_registry(): void
    {
        $names = collect($this->app->make(ToolRegistry::class)->all())->map(fn ($tool) => $tool->name())->all();

        $this->assertContains((new QrForge())->name(), $names);
    }

    public function test_home_route_renders_the_tool(): void
    {
        $this->get((new QrForge())->url())
            ->assertOk()
            ->assertSee('QR Forge');
    }
}

<?php

namespace Techysavvy\PhotoTweaker\Tests\Feature;

use Techysavvy\PhotoTweaker\Tests\TestCase;

use Techysavvy\Core\ToolRegistry;
use Techysavvy\PhotoTweaker\PhotoTweaker;

class PhotoTweakerTest extends TestCase
{
    public function test_it_registers_itself_in_the_tool_registry(): void
    {
        $names = collect($this->app->make(ToolRegistry::class)->all())->map(fn ($tool) => $tool->name())->all();

        $this->assertContains((new PhotoTweaker())->name(), $names);
    }

    public function test_home_route_renders_the_tool(): void
    {
        $this->get((new PhotoTweaker())->url())
            ->assertOk()
            ->assertSee('Photo Tweaker')
            ->assertSee('accept="image/*"', escape: false);
    }
}

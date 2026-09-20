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

    public function test_home_page_requests_the_registered_bundle(): void
    {
        foreach (['photo-tweaker.js', 'photo-tweaker.css'] as $file) {
            if (! is_file(__DIR__.'/../../resources/dist/'.$file)) {
                $this->markTestSkipped('The bundle is not built (npm run build --prefix plugins/photo-tweaker).');
            }
        }

        $html = $this->get(route('photo-tweaker.home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~/_plugin-assets/photo-tweaker/photo-tweaker\\.js\?v=[0-9a-f]{12}~', $html);
        $this->assertMatchesRegularExpression('~/_plugin-assets/photo-tweaker/photo-tweaker\\.css\?v=[0-9a-f]{12}~', $html);
    }

    public function test_home_page_has_no_inline_script_or_style_block(): void
    {
        $html = $this->get(route('photo-tweaker.home'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('~<script(?![^>]*\bsrc=)[^>]*>~i', $html);
        $this->assertStringNotContainsString('<style', $html);
    }
}

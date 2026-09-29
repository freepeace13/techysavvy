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

    public function test_home_page_requests_the_registered_bundle(): void
    {
        if (! is_file(__DIR__.'/../../resources/dist/qr-forge.js')) {
            $this->markTestSkipped('The bundle is not built (npm run build --prefix plugins/qr-forge).');
        }

        $html = $this->get(route('qr-forge.home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~/_plugin-assets/qr-forge/qr-forge\\.js\?v=[0-9a-f]{12}~', $html);
    }

    public function test_home_page_has_no_inline_script_or_style_block(): void
    {
        $html = $this->get(route('qr-forge.home'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('~<script(?![^>]*\bsrc=)[^>]*>~i', $html);
        $this->assertStringNotContainsString('<style', $html);
    }

    public function test_home_page_mounts_the_generator(): void
    {
        $this->get(route('qr-forge.home'))
            ->assertOk()
            ->assertSee('x-data="qrForge()"', escape: false)
            ->assertSee('accept="image/*"', escape: false);
    }
}

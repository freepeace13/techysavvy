<?php

namespace Techysavvy\EnvDiff\Tests\Feature;

use Techysavvy\EnvDiff\Tests\TestCase;

class HomeViewTest extends TestCase
{
    public function test_home_page_renders_the_safety_note(): void
    {
        $this->get(route('env-diff.home'))
            ->assertOk()
            ->assertSee('Why it is safe to paste your env here')
            ->assertSee('never sent to our server');
    }

    public function test_home_page_requests_the_registered_bundle(): void
    {
        if (! is_file(__DIR__.'/../../resources/dist/env-diff.js')) {
            $this->markTestSkipped('The bundle is not built (npm run build --prefix plugins/env-diff).');
        }

        $html = $this->get(route('env-diff.home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~/_plugin-assets/env-diff/env-diff\\.js\?v=[0-9a-f]{12}~', $html);
    }

    public function test_home_page_has_no_inline_script_or_third_party_assets(): void
    {
        $html = $this->get(route('env-diff.home'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('~<script(?![^>]*\bsrc=)[^>]*>~i', $html);
        $this->assertDoesNotMatchRegularExpression('~<(script|link|img|iframe)[^>]+(src|href)="https?://~i', $html);
    }

    public function test_the_tool_has_no_server_endpoint_that_accepts_input(): void
    {
        $this->post('/env-diff', ['a' => 'SECRET=1'])->assertStatus(405);
    }
}

<?php

namespace Techysavvy\WebhookInspector\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Techysavvy\WebhookInspector\Services\BinService;
use Techysavvy\WebhookInspector\Tests\TestCase;

class ViewerPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_mounts_the_component_with_its_config(): void
    {
        $bin = app(BinService::class)->create();

        $this->get(route('webhook-inspector.bins.show', $bin->view_token))
            ->assertOk()
            ->assertSee('x-data="webhookInspector($el.dataset)"', escape: false)
            ->assertSee('data-poll-url="'.route('webhook-inspector.bins.requests', $bin->view_token).'"', escape: false)
            ->assertSee('data-capture-url="'.route('webhook-inspector.capture', $bin->bin_id).'"', escape: false)
            ->assertSee('data-interval-ms="2000"', escape: false)
            ->assertSee('data-max-requests="200"', escape: false)
            ->assertSee('data-max-body-kb="256"', escape: false)
            ->assertSee('data-expired="false"', escape: false)
            ->assertSee('action="'.route('webhook-inspector.bins.destroy', $bin->view_token).'"', escape: false)
            ->assertSee('Delete this bin and all captured requests?');
    }

    public function test_viewer_never_uses_x_html_and_has_no_inline_blocks(): void
    {
        $bin = app(BinService::class)->create();
        $html = $this->get(route('webhook-inspector.bins.show', $bin->view_token))->getContent();

        $this->assertStringNotContainsString('x-html', $html);
        $this->assertDoesNotMatchRegularExpression('~<script(?![^>]*\bsrc=)[^>]*>~i', $html);
        $this->assertStringNotContainsString('<style', $html);
    }

    public function test_viewer_requests_the_registered_bundle(): void
    {
        if (! is_file(__DIR__.'/../../resources/dist/webhook-inspector.js')) {
            $this->markTestSkipped('The bundle is not built (npm run build --prefix plugins/webhook-inspector).');
        }
        $bin = app(BinService::class)->create();

        $html = $this->get(route('webhook-inspector.bins.show', $bin->view_token))->getContent();

        $this->assertMatchesRegularExpression('~/_plugin-assets/webhook-inspector/webhook-inspector\\.js\?v=[0-9a-f]{12}~', $html);
    }

    public function test_expired_bin_renders_the_expired_state_with_410(): void
    {
        $bin = app(BinService::class)->create();
        $bin->update(['expires_at' => now()->subSecond()]);

        $this->get(route('webhook-inspector.bins.show', $bin->view_token))
            ->assertStatus(410)
            ->assertSee('data-expired="true"', escape: false)
            ->assertSee('This bin has expired');
    }
}

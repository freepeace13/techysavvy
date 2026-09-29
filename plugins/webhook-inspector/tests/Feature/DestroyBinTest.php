<?php

namespace Techysavvy\WebhookInspector\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Techysavvy\WebhookInspector\Services\BinService;
use Techysavvy\WebhookInspector\Tests\TestCase;

class DestroyBinTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_the_bin_and_its_requests(): void
    {
        $bin = app(BinService::class)->create();
        $this->post("/webhook-inspector/in/{$bin->bin_id}", ['a' => 1]);

        $this->delete(route('webhook-inspector.bins.destroy', $bin->view_token))
            ->assertRedirect(route('webhook-inspector.home'));

        $this->assertDatabaseCount('webhook_inspector_requests', 0);
        $this->assertTrue($bin->fresh()->isExpired());
        $this->post("/webhook-inspector/in/{$bin->bin_id}")->assertStatus(410);
        $this->getJson(route('webhook-inspector.bins.requests', $bin->view_token))->assertStatus(410);
    }

    public function test_unknown_token_is_404(): void
    {
        $this->delete(route('webhook-inspector.bins.destroy', str_repeat('x', 40)))->assertNotFound();
    }
}

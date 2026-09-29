<?php

namespace Techysavvy\WebhookInspector\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Techysavvy\WebhookInspector\Models\CapturedRequest;
use Techysavvy\WebhookInspector\Services\BinService;
use Techysavvy\WebhookInspector\Tests\TestCase;

class RequestsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_requests_after_the_given_id_newest_first(): void
    {
        $bin = app(BinService::class)->create();
        foreach (['one', 'two', 'three'] as $body) {
            $this->call('POST', "/webhook-inspector/in/{$bin->bin_id}/p?x=1", [], [], [], ['HTTP_X_TEST' => $body], $body);
        }
        $first = CapturedRequest::orderBy('id')->first();

        $this->getJson(route('webhook-inspector.bins.requests', [$bin->view_token, 'after' => $first->id]))
            ->assertOk()
            ->assertJsonPath('expires_at', $bin->expires_at->toIso8601String())
            ->assertJsonCount(2, 'requests')
            ->assertJsonPath('requests.0.body', 'three')
            ->assertJsonPath('requests.1.body', 'two')
            ->assertJsonPath('requests.0.method', 'POST')
            ->assertJsonPath('requests.0.path', '/p')
            ->assertJsonPath('requests.0.query', 'x=1')
            ->assertJsonPath('requests.0.headers.x-test', ['three'])
            ->assertJsonStructure(['requests' => [['id', 'method', 'path', 'query', 'headers', 'body', 'content_type', 'body_size', 'truncated', 'is_binary', 'ip', 'received_at']]]);
    }

    public function test_unknown_token_is_404_and_expired_is_410(): void
    {
        $bin = app(BinService::class)->create();
        $bin->update(['expires_at' => now()->subSecond()]);

        $this->getJson(route('webhook-inspector.bins.requests', str_repeat('x', 40)))->assertNotFound();
        $this->getJson(route('webhook-inspector.bins.requests', $bin->view_token))->assertStatus(410);
    }

    public function test_capture_url_never_returns_captured_data(): void
    {
        $bin = app(BinService::class)->create();
        $this->call('POST', "/webhook-inspector/in/{$bin->bin_id}", [], [], [], [], 'secret-payload');

        $response = $this->get("/webhook-inspector/in/{$bin->bin_id}");

        $response->assertExactJson(['ok' => true]);
        $this->assertStringNotContainsString('secret-payload', $response->getContent());
        $this->assertStringNotContainsString($bin->view_token, $response->getContent());
    }
}

<?php

namespace Techysavvy\WebhookInspector\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Techysavvy\WebhookInspector\Models\CapturedRequest;
use Techysavvy\WebhookInspector\Services\BinService;
use Techysavvy\WebhookInspector\Tests\TestCase;

class CaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_method_path_query_headers_and_body(): void
    {
        $this->freezeSecond();
        $bin = app(BinService::class)->create();

        $this->call('POST', "/webhook-inspector/in/{$bin->bin_id}/hooks/stripe?b=2&a=1", [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => 'sig'], '{"a":1}')
            ->assertOk()
            ->assertExactJson(['ok' => true])
            ->assertHeader('Access-Control-Allow-Origin', '*');

        $captured = CapturedRequest::sole();
        $this->assertSame($bin->id, $captured->bin_id);
        $this->assertSame('POST', $captured->method);
        $this->assertSame('/hooks/stripe', $captured->path);
        $this->assertSame('b=2&a=1', $captured->query);
        $this->assertSame(['sig'], $captured->headers['x-signature']);
        $this->assertSame('{"a":1}', $captured->body);
        $this->assertSame('application/json', $captured->content_type);
        $this->assertSame(7, $captured->body_size);
        $this->assertFalse($captured->truncated);
        $this->assertFalse($captured->is_binary);
        $this->assertSame('127.0.0.1', $captured->ip);
        $this->assertTrue($captured->received_at->equalTo(now()));
    }

    public function test_it_accepts_every_method(): void
    {
        $bin = app(BinService::class)->create();

        foreach (['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'] as $method) {
            $this->call($method, "/webhook-inspector/in/{$bin->bin_id}")->assertOk();
        }

        $this->assertSame(['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'], CapturedRequest::orderBy('id')->pluck('method')->all());
        $this->assertSame('/', CapturedRequest::first()->path);
        $this->assertNull(CapturedRequest::first()->body);
    }

    public function test_capture_route_is_outside_the_web_group_and_sets_no_cookies(): void
    {
        $bin = app(BinService::class)->create();

        $response = $this->post("/webhook-inspector/in/{$bin->bin_id}", ['x' => 1])->assertOk();

        $this->assertSame([], $response->headers->getCookies());
        $this->assertNotContains('web', app('router')->getRoutes()->getByName('webhook-inspector.capture')->gatherMiddleware());
    }

    public function test_unknown_bin_is_404_and_stores_nothing(): void
    {
        $this->postJson('/webhook-inspector/in/nosuchbin0000000', ['a' => 1])
            ->assertNotFound()
            ->assertExactJson(['error' => 'Bin not found.'])
            ->assertHeader('Access-Control-Allow-Origin', '*');

        $this->assertDatabaseCount('webhook_inspector_requests', 0);
    }

    public function test_expired_bin_is_410_and_stores_nothing(): void
    {
        $bin = app(BinService::class)->create();
        $bin->update(['expires_at' => now()->subSecond()]);

        $this->postJson("/webhook-inspector/in/{$bin->bin_id}", ['a' => 1])
            ->assertStatus(410)
            ->assertExactJson(['error' => 'Bin expired.']);

        $this->assertDatabaseCount('webhook_inspector_requests', 0);
    }
}

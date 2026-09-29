<?php

namespace Techysavvy\WebhookInspector\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Techysavvy\WebhookInspector\Models\Bin;
use Techysavvy\WebhookInspector\Tests\TestCase;

class ModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_has_the_spec_defaults(): void
    {
        $this->assertSame([
            'lifespan_hours' => 24,
            'max_body_kb' => 256,
            'max_requests_per_bin' => 200,
            'capture_rate_per_minute' => 60,
            'create_rate_per_minute' => 10,
            'poll_interval_ms' => 2000,
            'prune_interval_minutes' => 5,
        ], config('webhook-inspector'));
    }

    public function test_deleting_a_bin_cascades_to_its_requests(): void
    {
        $bin = Bin::create(['bin_id' => 'abcdefgh12345678', 'view_token' => str_repeat('t', 40), 'expires_at' => now()->addDay()]);
        $bin->requests()->create([
            'method' => 'POST', 'path' => '/', 'query' => '', 'headers' => ['x-a' => ['1']],
            'body' => '{}', 'content_type' => 'application/json', 'body_size' => 2,
            'truncated' => false, 'is_binary' => false, 'ip' => '127.0.0.1', 'received_at' => now(),
        ]);

        $this->assertSame(['x-a' => ['1']], $bin->requests()->first()->headers);

        $bin->delete();

        $this->assertDatabaseCount('webhook_inspector_requests', 0);
    }
}

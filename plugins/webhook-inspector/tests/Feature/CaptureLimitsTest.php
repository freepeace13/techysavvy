<?php

namespace Techysavvy\WebhookInspector\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Techysavvy\WebhookInspector\Models\CapturedRequest;
use Techysavvy\WebhookInspector\Services\BinService;
use Techysavvy\WebhookInspector\Tests\TestCase;

class CaptureLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_oldest_requests_are_dropped_over_the_cap(): void
    {
        config(['webhook-inspector.max_requests_per_bin' => 3]);
        $bin = app(BinService::class)->create();

        foreach (range(1, 5) as $n) {
            $this->call('POST', "/webhook-inspector/in/{$bin->bin_id}", [], [], [], [], "body-$n")->assertOk();
        }

        $this->assertSame(['body-3', 'body-4', 'body-5'], CapturedRequest::orderBy('id')->pluck('body')->all());
    }
}

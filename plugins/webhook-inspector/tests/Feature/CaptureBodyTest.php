<?php

namespace Techysavvy\WebhookInspector\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Techysavvy\WebhookInspector\Models\CapturedRequest;
use Techysavvy\WebhookInspector\Services\BinService;
use Techysavvy\WebhookInspector\Tests\TestCase;

class CaptureBodyTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_body_is_truncated_and_flagged(): void
    {
        config(['webhook-inspector.max_body_kb' => 1]);
        $bin = app(BinService::class)->create();

        $this->call('POST', "/webhook-inspector/in/{$bin->bin_id}", [], [], [], ['CONTENT_TYPE' => 'text/plain'], str_repeat('é', 1000))->assertOk();

        $captured = CapturedRequest::sole();
        $this->assertTrue($captured->truncated);
        $this->assertSame(2000, $captured->body_size);
        $this->assertLessThanOrEqual(1024, strlen($captured->body));
        $this->assertTrue(mb_check_encoding($captured->body, 'UTF-8'));
        $this->assertFalse($captured->is_binary);
    }

    public function test_non_utf8_body_is_stored_base64_and_flagged(): void
    {
        $bin = app(BinService::class)->create();
        $bytes = "\xff\xfe\x00\x01";

        $this->call('POST', "/webhook-inspector/in/{$bin->bin_id}", [], [], [], ['CONTENT_TYPE' => 'application/octet-stream'], $bytes)->assertOk();

        $captured = CapturedRequest::sole();
        $this->assertTrue($captured->is_binary);
        $this->assertSame($bytes, base64_decode($captured->body));
        $this->assertSame(4, $captured->body_size);
    }
}

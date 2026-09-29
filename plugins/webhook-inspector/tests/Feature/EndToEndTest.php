<?php

namespace Techysavvy\WebhookInspector\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Techysavvy\WebhookInspector\Models\Bin;
use Techysavvy\WebhookInspector\Tests\TestCase;

class EndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_capture_and_read_back_through_the_viewer_api(): void
    {
        $this->post(route('webhook-inspector.bins.store'));
        $bin = Bin::sole();
        $other = str_repeat('z', 40);

        $this->call('POST', route('webhook-inspector.capture', $bin->bin_id), [], [], [], ['CONTENT_TYPE' => 'application/json'], '{"a":1}')
            ->assertExactJson(['ok' => true]);
        $this->call('POST', route('webhook-inspector.capture', $bin->bin_id), [], [], [], ['CONTENT_TYPE' => 'text/html'], '<script>alert(1)</script>');

        $this->getJson(route('webhook-inspector.bins.requests', $bin->view_token))
            ->assertJsonPath('requests.0.body', '<script>alert(1)</script>')
            ->assertJsonPath('requests.1.body', '{"a":1}');
        $this->getJson(route('webhook-inspector.bins.requests', $other))->assertNotFound();
    }
}

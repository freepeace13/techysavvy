<?php

namespace Techysavvy\WebhookInspector\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Techysavvy\WebhookInspector\Models\Bin;
use Techysavvy\WebhookInspector\Tests\TestCase;

class BinCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_bin_and_redirects_to_the_viewer(): void
    {
        $this->freezeSecond();

        $response = $this->post(route('webhook-inspector.bins.store'));

        $bin = Bin::sole();
        $this->assertMatchesRegularExpression('/^[a-z0-9]{16}$/', $bin->bin_id);
        $this->assertSame(40, strlen($bin->view_token));
        $this->assertTrue($bin->expires_at->equalTo(now()->addHours(24)));
        $response->assertRedirect(route('webhook-inspector.bins.show', $bin->view_token));

        $this->get(route('webhook-inspector.bins.show', $bin->view_token))
            ->assertOk()
            ->assertSee(route('webhook-inspector.capture', $bin->bin_id));
    }

    public function test_bin_creation_is_throttled_per_ip(): void
    {
        config(['webhook-inspector.create_rate_per_minute' => 2]);

        $this->post(route('webhook-inspector.bins.store'))->assertRedirect();
        $this->post(route('webhook-inspector.bins.store'))->assertRedirect();
        $this->post(route('webhook-inspector.bins.store'))->assertStatus(429);

        $this->assertSame(2, Bin::count());
    }

    public function test_unknown_view_token_is_404(): void
    {
        $this->get(route('webhook-inspector.bins.show', str_repeat('x', 40)))->assertNotFound();
    }
}

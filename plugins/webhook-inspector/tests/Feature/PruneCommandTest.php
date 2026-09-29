<?php

namespace Techysavvy\WebhookInspector\Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Techysavvy\WebhookInspector\Models\Bin;
use Techysavvy\WebhookInspector\Services\BinService;
use Techysavvy\WebhookInspector\Tests\TestCase;

class PruneCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_expired_bins_and_their_requests(): void
    {
        $expired = app(BinService::class)->create();
        $this->post("/webhook-inspector/in/{$expired->bin_id}", ['a' => 1]);
        $expired->update(['expires_at' => now()->subMinute()]);
        $live = app(BinService::class)->create();

        $this->artisan('webhook-inspector:prune')
            ->expectsOutputToContain('Pruned 1 expired webhook-inspector bin(s).')
            ->assertExitCode(0);

        $this->assertSame([$live->id], Bin::pluck('id')->all());
        $this->assertDatabaseCount('webhook_inspector_requests', 0);
    }

    public function test_it_is_scheduled_every_prune_interval(): void
    {
        $event = collect($this->app->make(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command ?? '', 'webhook-inspector:prune'));

        $this->assertNotNull($event);
        $this->assertSame('*/5 * * * *', $event->expression);
    }
}

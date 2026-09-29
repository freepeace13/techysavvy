<?php

namespace Techysavvy\WebhookInspector\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Techysavvy\WebhookInspector\Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_offers_a_create_bin_form_and_a_privacy_note_without_creating_a_bin(): void
    {
        $this->get(route('webhook-inspector.home'))
            ->assertOk()
            ->assertSee('action="'.route('webhook-inspector.bins.store').'"', escape: false)
            ->assertSee('method="POST"', escape: false)
            ->assertSee('Create bin')
            ->assertSee("Don't send production secrets");

        $this->assertDatabaseCount('webhook_inspector_bins', 0);
    }
}

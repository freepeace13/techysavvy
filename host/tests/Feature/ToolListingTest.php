<?php

namespace Tests\Feature;

use Tests\TestCase;

class ToolListingTest extends TestCase
{
    public function test_home_page_lists_registered_tools(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Hello Tool');
        $response->assertSee('Demo plugin proving a tool can register itself and boot end-to-end.');
        $response->assertSee(route('hello-tool.home'), escape: false);
    }

    public function test_tool_card_link_navigates_to_the_tools_own_route(): void
    {
        $response = $this->get(route('hello-tool.home'));

        $response->assertOk();
        $response->assertSee('Hello Tool');
    }
}

<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Techysavvy\DropShare\Tests\TestCase;

class HomeViewTest extends TestCase
{
    public function test_home_page_renders_both_forms(): void
    {
        $response = $this->get(route('drop-share.home'));

        $response->assertOk();
        $response->assertSee(route('drop-share.upload'), false);
        $response->assertSee(route('drop-share.download'), false);
    }

    public function test_home_page_shows_the_flashed_phrase_after_upload(): void
    {
        $response = $this->withSession(['drop_share_phrase' => 'correct-horse-battery-staple'])
            ->get(route('drop-share.home'));

        $response->assertSee('correct-horse-battery-staple');
    }

    public function test_home_page_shows_the_flashed_download_error(): void
    {
        $response = $this->withSession(['drop_share_error' => 'That phrase is invalid or has expired.'])
            ->get(route('drop-share.home'));

        $response->assertSee('That phrase is invalid or has expired.');
    }
}

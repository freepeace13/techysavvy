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

    public function test_home_page_requests_the_registered_bundle(): void
    {
        foreach (['drop-share.js', 'drop-share.css'] as $file) {
            if (! is_file(__DIR__.'/../../resources/dist/'.$file)) {
                $this->markTestSkipped('The bundle is not built (npm run build --prefix plugins/drop-share).');
            }
        }

        $html = $this->get(route('drop-share.home'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('~/_plugin-assets/drop-share/drop-share\\.js\?v=[0-9a-f]{12}~', $html);
        $this->assertMatchesRegularExpression('~/_plugin-assets/drop-share/drop-share\\.css\?v=[0-9a-f]{12}~', $html);
    }

    public function test_home_page_has_no_inline_script_or_style_block(): void
    {
        $html = $this->get(route('drop-share.home'))->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('~<script(?![^>]*\bsrc=)[^>]*>~i', $html);
        $this->assertStringNotContainsString('<style', $html);
    }
}

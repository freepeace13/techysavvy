<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Techysavvy\DropShare\Models\SharedFile;
use Techysavvy\DropShare\Services\DropShareService;
use Techysavvy\DropShare\Tests\TestCase;

class DownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_phrase_downloads_the_original_file(): void
    {
        Storage::fake(config('drop-share.disk'));

        $file = UploadedFile::fake()->createWithContent('report.txt', 'the actual content');
        $sharedFile = $this->app->make(DropShareService::class)->store($file);

        $response = $this->post(route('drop-share.download'), ['phrase' => $sharedFile->phrase]);

        $response->assertOk();
        $this->assertSame('the actual content', $response->getContent());
        $this->assertStringContainsString('report.txt', $response->headers->get('Content-Disposition'));
    }

    public function test_invalid_phrase_redirects_with_an_error(): void
    {
        $response = $this->post(route('drop-share.download'), ['phrase' => 'nonexistent-phrase-here-now']);

        $response->assertRedirect(route('drop-share.home'));
        $response->assertSessionHas('drop_share_error');
    }

    public function test_expired_phrase_is_treated_as_not_found(): void
    {
        SharedFile::create([
            'phrase' => 'gone-now-four-words',
            'disk_path' => 'drop-share/whatever',
            'original_name' => 'x.txt',
            'mime_type' => 'text/plain',
            'size' => 1,
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $response = $this->post(route('drop-share.download'), ['phrase' => 'gone-now-four-words']);

        $response->assertRedirect(route('drop-share.home'));
        $response->assertSessionHas('drop_share_error');
    }

    public function test_downloads_are_throttled_after_the_configured_limit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('drop-share.download'), ['phrase' => 'nonexistent-phrase-here-now']);
        }

        $response = $this->post(route('drop-share.download'), ['phrase' => 'nonexistent-phrase-here-now']);

        $response->assertStatus(429);
    }
}

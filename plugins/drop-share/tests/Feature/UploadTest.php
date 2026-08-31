<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Techysavvy\DropShare\Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_upload_redirects_with_a_phrase_flashed(): void
    {
        Storage::fake(config('drop-share.disk'));

        $file = UploadedFile::fake()->create('report.pdf', 100);

        $response = $this->post(route('drop-share.upload'), ['file' => $file]);

        $response->assertRedirect(route('drop-share.home'));
        $response->assertSessionHas('drop_share_phrase');

        $phrase = session('drop_share_phrase');
        $this->assertMatchesRegularExpression('/^[a-z]+(-[a-z]+){3}$/', $phrase);
    }

    public function test_upload_without_a_file_fails_validation(): void
    {
        $response = $this->post(route('drop-share.upload'), []);

        $response->assertSessionHasErrors('file');
    }

    public function test_upload_larger_than_configured_limit_fails_validation(): void
    {
        config(['drop-share.max_upload_kb' => 10]);

        $file = UploadedFile::fake()->create('too-big.bin', 50); // 50KB > 10KB limit

        $response = $this->post(route('drop-share.upload'), ['file' => $file]);

        $response->assertSessionHasErrors('file');
    }

    public function test_uploads_are_throttled_after_the_configured_limit(): void
    {
        Storage::fake(config('drop-share.disk'));

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('drop-share.upload'), [
                'file' => UploadedFile::fake()->create("file{$i}.txt", 1),
            ])->assertRedirect();
        }

        $response = $this->post(route('drop-share.upload'), [
            'file' => UploadedFile::fake()->create('one-too-many.txt', 1),
        ]);

        $response->assertStatus(429);
    }
}

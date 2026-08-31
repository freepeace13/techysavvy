<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Techysavvy\DropShare\Models\SharedFile;
use Techysavvy\DropShare\Services\DropShareService;
use Techysavvy\DropShare\Tests\TestCase;

class DropShareServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_encrypts_the_file_and_creates_a_row(): void
    {
        Storage::fake(config('drop-share.disk'));

        $file = UploadedFile::fake()->createWithContent('notes.txt', 'hello world');

        $sharedFile = $this->app->make(DropShareService::class)->store($file);

        $this->assertDatabaseHas('drop_share_uploads', [
            'id' => $sharedFile->id,
            'original_name' => 'notes.txt',
        ]);
        $this->assertMatchesRegularExpression('/^[a-z]+(-[a-z]+){3}$/', $sharedFile->phrase);

        $stored = Storage::disk(config('drop-share.disk'))->get($sharedFile->disk_path);
        $this->assertNotSame('hello world', $stored);
        $this->assertSame('hello world', Crypt::decryptString($stored));
    }

    public function test_find_valid_returns_null_for_unknown_phrase(): void
    {
        $result = $this->app->make(DropShareService::class)->findValid('does-not-exist-anywhere');

        $this->assertNull($result);
    }

    public function test_find_valid_returns_null_for_expired_phrase(): void
    {
        SharedFile::create([
            'phrase' => 'expired-phrase-four-words',
            'disk_path' => 'drop-share/whatever',
            'original_name' => 'x.txt',
            'mime_type' => 'text/plain',
            'size' => 1,
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $result = $this->app->make(DropShareService::class)->findValid('expired-phrase-four-words');

        $this->assertNull($result);
    }

    public function test_build_download_response_returns_decrypted_content(): void
    {
        Storage::fake(config('drop-share.disk'));

        $file = UploadedFile::fake()->createWithContent('notes.txt', 'secret content');
        $service = $this->app->make(DropShareService::class);
        $sharedFile = $service->store($file);

        $response = $service->buildDownloadResponse($sharedFile);

        $this->assertSame('secret content', $response->getContent());
        $this->assertStringContainsString('notes.txt', $response->headers->get('Content-Disposition'));
    }

    public function test_prune_expired_deletes_expired_rows_and_files_only(): void
    {
        Storage::fake(config('drop-share.disk'));
        $disk = config('drop-share.disk');

        Storage::disk($disk)->put('drop-share/expired-path', 'x');
        $expired = SharedFile::create([
            'phrase' => 'expired-one-two-three',
            'disk_path' => 'drop-share/expired-path',
            'original_name' => 'a.txt',
            'mime_type' => 'text/plain',
            'size' => 1,
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        Storage::disk($disk)->put('drop-share/valid-path', 'y');
        $valid = SharedFile::create([
            'phrase' => 'valid-one-two-three',
            'disk_path' => 'drop-share/valid-path',
            'original_name' => 'b.txt',
            'mime_type' => 'text/plain',
            'size' => 1,
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $deletedCount = $this->app->make(DropShareService::class)->pruneExpired();

        $this->assertSame(1, $deletedCount);
        $this->assertDatabaseMissing('drop_share_uploads', ['id' => $expired->id]);
        $this->assertDatabaseHas('drop_share_uploads', ['id' => $valid->id]);
        Storage::disk($disk)->assertMissing('drop-share/expired-path');
        Storage::disk($disk)->assertExists('drop-share/valid-path');
    }
}

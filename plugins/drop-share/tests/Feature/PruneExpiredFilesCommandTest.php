<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Techysavvy\DropShare\Models\SharedFile;
use Techysavvy\DropShare\Tests\TestCase;

class PruneExpiredFilesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_expired_rows_and_files_and_reports_the_count(): void
    {
        Storage::fake(config('drop-share.disk'));
        $disk = config('drop-share.disk');

        Storage::disk($disk)->put('drop-share/expired-path', 'x');
        SharedFile::create([
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

        $this->artisan('drop-share:prune')
            ->expectsOutputToContain('Pruned 1 expired drop-share upload(s).')
            ->assertExitCode(0);

        $this->assertDatabaseCount('drop_share_uploads', 1);
        $this->assertDatabaseHas('drop_share_uploads', ['id' => $valid->id]);
    }
}

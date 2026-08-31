<?php

namespace Techysavvy\DropShare\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Techysavvy\DropShare\Models\SharedFile;
use Techysavvy\DropShare\Tests\TestCase;

class SharedFileModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_and_casts_expires_at_to_a_carbon_instance(): void
    {
        $sharedFile = SharedFile::create([
            'phrase' => 'correct-horse-battery-staple',
            'disk_path' => 'drop-share/abc123',
            'original_name' => 'notes.txt',
            'mime_type' => 'text/plain',
            'size' => 42,
            'expires_at' => Carbon::now()->addHours(24),
        ]);

        $fresh = SharedFile::query()->first();

        $this->assertSame($sharedFile->id, $fresh->id);
        $this->assertSame('correct-horse-battery-staple', $fresh->phrase);
        $this->assertInstanceOf(Carbon::class, $fresh->expires_at);
    }
}

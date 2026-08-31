<?php

namespace Techysavvy\DropShare\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Techysavvy\DropShare\Models\SharedFile;

class DropShareService
{
    private const MAX_PHRASE_ATTEMPTS = 5;

    public function __construct(private readonly PhraseGenerator $phraseGenerator)
    {
    }

    public function store(UploadedFile $file): SharedFile
    {
        $encrypted = Crypt::encryptString(file_get_contents($file->getRealPath()));
        $diskPath = 'drop-share/'.(string) Str::uuid();

        Storage::disk(config('drop-share.disk'))->put($diskPath, $encrypted);

        return SharedFile::create([
            'phrase' => $this->uniquePhrase(),
            'disk_path' => $diskPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'expires_at' => now()->addHours((int) config('drop-share.lifespan_hours')),
        ]);
    }

    public function findValid(string $phrase): ?SharedFile
    {
        $sharedFile = SharedFile::where('phrase', $phrase)->first();

        if (! $sharedFile || $sharedFile->expires_at->isPast()) {
            return null;
        }

        return $sharedFile;
    }

    public function buildDownloadResponse(SharedFile $sharedFile): Response
    {
        $encrypted = Storage::disk(config('drop-share.disk'))->get($sharedFile->disk_path);
        $decrypted = Crypt::decryptString($encrypted);

        return response($decrypted, 200, [
            'Content-Type' => $sharedFile->mime_type,
            'Content-Disposition' => 'attachment; filename="'.$sharedFile->original_name.'"',
        ]);
    }

    public function pruneExpired(): int
    {
        $expired = SharedFile::where('expires_at', '<=', now())->get();

        foreach ($expired as $sharedFile) {
            Storage::disk(config('drop-share.disk'))->delete($sharedFile->disk_path);
            $sharedFile->delete();
        }

        return $expired->count();
    }

    private function uniquePhrase(): string
    {
        for ($attempt = 0; $attempt < self::MAX_PHRASE_ATTEMPTS; $attempt++) {
            $phrase = $this->phraseGenerator->generate();

            if (! SharedFile::where('phrase', $phrase)->exists()) {
                return $phrase;
            }
        }

        throw new RuntimeException('Unable to generate a unique drop-share phrase after '.self::MAX_PHRASE_ATTEMPTS.' attempts.');
    }
}

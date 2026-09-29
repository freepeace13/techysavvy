<?php

namespace Techysavvy\WebhookInspector\Services;

use Illuminate\Support\Str;
use Techysavvy\WebhookInspector\Models\Bin;

class BinService
{
    public function create(): Bin
    {
        do {
            $binId = Str::lower(Str::random(16));
            $viewToken = Str::random(40);
        } while (Bin::where('bin_id', $binId)->orWhere('view_token', $viewToken)->exists());

        return Bin::create([
            'bin_id' => $binId,
            'view_token' => $viewToken,
            'expires_at' => now()->addHours((int) config('webhook-inspector.lifespan_hours')),
        ]);
    }

    public function findByViewToken(string $viewToken): Bin
    {
        return Bin::where('view_token', $viewToken)->firstOrFail();
    }

    public function findByBinId(string $binId): ?Bin
    {
        return Bin::where('bin_id', $binId)->first();
    }
}

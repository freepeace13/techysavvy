<?php

namespace Techysavvy\WebhookInspector\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Techysavvy\WebhookInspector\Models\Bin;
use Techysavvy\WebhookInspector\Models\CapturedRequest;

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

    public function capture(Bin $bin, Request $request, ?string $path): CapturedRequest
    {
        $raw = $request->getContent();

        return $bin->requests()->create([
            'method' => $request->method(),
            'path' => '/'.ltrim((string) $path, '/'),
            // Raw, as sent: getQueryString() re-sorts the parameters.
            'query' => (string) $request->server('QUERY_STRING', ''),
            'headers' => $request->headers->all(),
            'body' => $raw === '' ? null : $raw,
            'content_type' => $request->header('Content-Type'),
            'body_size' => strlen($raw),
            'truncated' => false,
            'is_binary' => false,
            'ip' => $request->ip(),
            'received_at' => now(),
        ]);
    }
}

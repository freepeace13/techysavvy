<?php

namespace Techysavvy\WebhookInspector\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $max = (int) config('webhook-inspector.max_body_kb') * 1024;
        $isBinary = ! mb_check_encoding($raw, 'UTF-8');
        $truncated = strlen($raw) > $max;

        // Cut text on a character boundary so the stored body stays valid UTF-8.
        $stored = $truncated
            ? ($isBinary ? substr($raw, 0, $max) : mb_strcut($raw, 0, $max, 'UTF-8'))
            : $raw;

        return DB::transaction(function () use ($bin, $request, $path, $raw, $stored, $truncated, $isBinary) {
            $captured = $bin->requests()->create([
                'method' => $request->method(),
                'path' => '/'.ltrim((string) $path, '/'),
                // Raw, as sent: getQueryString() re-sorts the parameters.
                'query' => (string) $request->server('QUERY_STRING', ''),
                'headers' => $request->headers->all(),
                'body' => $raw === '' ? null : ($isBinary ? base64_encode($stored) : $stored),
                'content_type' => $request->header('Content-Type'),
                'body_size' => strlen($raw),
                'truncated' => $truncated,
                'is_binary' => $isBinary,
                'ip' => $request->ip(),
                'received_at' => now(),
            ]);

            $this->dropOverflow($bin);

            return $captured;
        });
    }

    // Keeps only the newest max_requests_per_bin requests.
    private function dropOverflow(Bin $bin): void
    {
        $ids = $bin->requests()
            ->orderByDesc('id')
            ->skip((int) config('webhook-inspector.max_requests_per_bin'))
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            CapturedRequest::whereIn('id', $ids)->delete();
        }
    }

    // Keeps the row as an expired tombstone so the capture URL answers 410
    // (not 404) until the prune command removes it.
    public function delete(Bin $bin): void
    {
        DB::transaction(function () use ($bin) {
            $bin->requests()->delete();
            $bin->update(['expires_at' => now()]);
        });
    }
}

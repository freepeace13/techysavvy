<?php

namespace Techysavvy\WebhookInspector\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Techysavvy\WebhookInspector\Services\BinService;

class CaptureController
{
    public function __invoke(Request $request, BinService $service, string $binId, ?string $path = null): JsonResponse
    {
        $bin = $service->findByBinId($binId);

        if (! $bin) {
            return self::withCors(response()->json(['error' => 'Bin not found.'], 404));
        }

        if ($bin->isExpired()) {
            return self::withCors(response()->json(['error' => 'Bin expired.'], 410));
        }

        $service->capture($bin, $request, $path);

        return self::withCors(response()->json(['ok' => true]));
    }

    // Lets browser-based senders, including preflighted ones, reach the bin
    // and read the response. Also used for the capture rate limiter's 429.
    public static function withCors(JsonResponse $response): JsonResponse
    {
        return $response->withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => '*',
            'Access-Control-Allow-Headers' => '*',
        ]);
    }
}

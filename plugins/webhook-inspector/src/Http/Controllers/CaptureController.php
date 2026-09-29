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
            return $this->cors(response()->json(['error' => 'Bin not found.'], 404));
        }

        if ($bin->isExpired()) {
            return $this->cors(response()->json(['error' => 'Bin expired.'], 410));
        }

        $service->capture($bin, $request, $path);

        return $this->cors(response()->json(['ok' => true]));
    }

    // Lets browser-based senders read the response.
    private function cors(JsonResponse $response): JsonResponse
    {
        return $response->header('Access-Control-Allow-Origin', '*');
    }
}

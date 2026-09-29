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

        abort_unless($bin, 404);

        $service->capture($bin, $request, $path);

        return response()->json(['ok' => true])->header('Access-Control-Allow-Origin', '*');
    }
}

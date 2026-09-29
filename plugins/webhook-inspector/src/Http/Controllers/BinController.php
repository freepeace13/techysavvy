<?php

namespace Techysavvy\WebhookInspector\Http\Controllers;

use Illuminate\Http\Request;
use Techysavvy\WebhookInspector\Models\CapturedRequest;
use Techysavvy\WebhookInspector\Services\BinService;

class BinController
{
    public function store(BinService $service)
    {
        $bin = $service->create();

        return redirect()->route('webhook-inspector.bins.show', $bin->view_token);
    }

    public function show(string $viewToken, BinService $service)
    {
        $bin = $service->findByViewToken($viewToken);

        return view('webhook-inspector::show', [
            'bin' => $bin,
            'captureUrl' => route('webhook-inspector.capture', $bin->bin_id),
        ]);
    }

    public function requests(Request $request, string $viewToken, BinService $service)
    {
        $bin = $service->findByViewToken($viewToken);

        abort_if($bin->isExpired(), 410);

        return [
            'expires_at' => $bin->expires_at->toIso8601String(),
            'requests' => $bin->requests()
                ->where('id', '>', (int) $request->query('after', 0))
                ->orderByDesc('id')
                ->get()
                ->map(fn (CapturedRequest $captured) => [
                    'id' => $captured->id,
                    'method' => $captured->method,
                    'path' => $captured->path,
                    'query' => $captured->query,
                    'headers' => $captured->headers,
                    'body' => $captured->body,
                    'content_type' => $captured->content_type,
                    'body_size' => $captured->body_size,
                    'truncated' => $captured->truncated,
                    'is_binary' => $captured->is_binary,
                    'ip' => $captured->ip,
                    'received_at' => $captured->received_at->toIso8601String(),
                ]),
        ];
    }

    public function destroy(string $viewToken, BinService $service)
    {
        $service->delete($service->findByViewToken($viewToken));

        return redirect()->route('webhook-inspector.home');
    }
}

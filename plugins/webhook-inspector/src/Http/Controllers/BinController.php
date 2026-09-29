<?php

namespace Techysavvy\WebhookInspector\Http\Controllers;

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
}

<?php

namespace Techysavvy\DropShare\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Techysavvy\DropShare\Services\DropShareService;

class DownloadController
{
    public function store(Request $request, DropShareService $service): RedirectResponse|Response
    {
        $validated = $request->validate(['phrase' => ['required', 'string']]);

        $sharedFile = $service->findValid($validated['phrase']);

        if (! $sharedFile) {
            return redirect()
                ->route('drop-share.home')
                ->with('drop_share_error', 'That phrase is invalid or has expired.');
        }

        return $service->buildDownloadResponse($sharedFile);
    }
}

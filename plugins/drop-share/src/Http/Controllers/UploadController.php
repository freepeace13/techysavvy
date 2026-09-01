<?php

namespace Techysavvy\DropShare\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Techysavvy\DropShare\Http\Requests\StoreUploadRequest;
use Techysavvy\DropShare\Services\DropShareService;

class UploadController
{
    public function store(StoreUploadRequest $request, DropShareService $service): RedirectResponse|JsonResponse
    {
        $sharedFile = $service->store($request->file('file'));

        if ($request->wantsJson()) {
            return response()->json([
                'phrase' => $sharedFile->phrase,
                'expires_at' => $sharedFile->expires_at->toIso8601String(),
            ]);
        }

        return redirect()
            ->route('drop-share.home')
            ->with('drop_share_phrase', $sharedFile->phrase);
    }
}

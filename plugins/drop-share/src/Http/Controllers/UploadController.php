<?php

namespace Techysavvy\DropShare\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Techysavvy\DropShare\Http\Requests\StoreUploadRequest;
use Techysavvy\DropShare\Services\DropShareService;

class UploadController
{
    public function store(StoreUploadRequest $request, DropShareService $service): RedirectResponse
    {
        $sharedFile = $service->store($request->file('file'));

        return redirect()
            ->route('drop-share.home')
            ->with('drop_share_phrase', $sharedFile->phrase);
    }
}

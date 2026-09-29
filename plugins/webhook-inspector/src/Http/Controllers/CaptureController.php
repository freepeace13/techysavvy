<?php

namespace Techysavvy\WebhookInspector\Http\Controllers;

class CaptureController
{
    public function __invoke()
    {
        return response()->json(['ok' => true]);
    }
}

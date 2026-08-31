<?php

namespace Techysavvy\DropShare;

use Techysavvy\Core\ToolContract;

class DropShareTool implements ToolContract
{
    public function icon(): string
    {
        return '📦';
    }

    public function name(): string
    {
        return 'Drop Share';
    }

    public function description(): string
    {
        return 'Upload a file, get a phrase, share it — auto-deletes after expiry.';
    }

    public function url(): string
    {
        return route('drop-share.home');
    }
}

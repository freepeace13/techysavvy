<?php

namespace Techysavvy\HelloTool;

use Techysavvy\Core\ToolContract;

class HelloTool implements ToolContract
{
    public function icon(): string
    {
        return '👋';
    }

    public function name(): string
    {
        return 'Hello Tool';
    }

    public function description(): string
    {
        return 'Demo plugin proving a tool can register itself and boot end-to-end.';
    }

    public function url(): string
    {
        return route('hello-tool.home');
    }
}

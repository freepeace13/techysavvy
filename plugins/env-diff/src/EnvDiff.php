<?php

namespace Techysavvy\EnvDiff;

use Techysavvy\Core\ToolContract;

class EnvDiff implements ToolContract
{
    public function icon(): string
    {
        return '🔍';
    }

    public function name(): string
    {
        return 'EnvDiff';
    }

    public function description(): string
    {
        return 'Compare .env files, spot drift and leaked secrets, and generate a clean .env.example.';
    }

    public function url(): string
    {
        return route('env-diff.home');
    }
}

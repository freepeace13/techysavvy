<?php

namespace Techysavvy\WebhookInspector;

use Techysavvy\Core\ToolContract;

class WebhookInspector implements ToolContract
{
    public function icon(): string
    {
        return '🪝';
    }

    public function name(): string
    {
        return 'Webhook Inspector';
    }

    public function description(): string
    {
        return 'Get a throwaway URL and watch incoming webhooks arrive live.';
    }

    public function url(): string
    {
        return route('webhook-inspector.home');
    }
}

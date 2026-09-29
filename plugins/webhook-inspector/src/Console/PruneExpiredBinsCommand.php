<?php

namespace Techysavvy\WebhookInspector\Console;

use Illuminate\Console\Command;
use Techysavvy\WebhookInspector\Services\BinService;

class PruneExpiredBinsCommand extends Command
{
    protected $signature = 'webhook-inspector:prune';

    protected $description = 'Delete expired webhook-inspector bins and their captured requests.';

    public function handle(BinService $service): int
    {
        $deleted = $service->pruneExpired();

        $this->info("Pruned {$deleted} expired webhook-inspector bin(s).");

        return self::SUCCESS;
    }
}

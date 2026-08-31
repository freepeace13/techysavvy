<?php

namespace Techysavvy\DropShare\Console;

use Illuminate\Console\Command;
use Techysavvy\DropShare\Services\DropShareService;

class PruneExpiredFilesCommand extends Command
{
    protected $signature = 'drop-share:prune';

    protected $description = 'Delete expired drop-share uploads (database rows and their files).';

    public function handle(DropShareService $service): int
    {
        $deleted = $service->pruneExpired();

        $this->info("Pruned {$deleted} expired drop-share upload(s).");

        return self::SUCCESS;
    }
}

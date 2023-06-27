<?php

namespace alanrogers\tools\queue\jobs;

use alanrogers\tools\services\errors\reporters\Database;
use craft\queue\JobInterface;

class PruneErrorDBTable implements JobInterface
{
    public int $days_old = 90;

    public function getDescription(): ?string
    {
        return sprintf('Pruning errors older than %d days', $this->days_old);
    }

    public function execute($queue): void
    {
        $db_reporter = new Database();
        $db_reporter->pruneRecords($this->days_old);
    }
}
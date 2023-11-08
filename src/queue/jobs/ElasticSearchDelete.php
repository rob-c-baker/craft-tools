<?php

namespace alanrogers\tools\queue\jobs;

use alanrogers\tools\services\ARLogger;
use alanrogers\tools\services\es\Config;
use alanrogers\tools\services\es\ESException;
use alanrogers\tools\services\es\IndexType;
use alanrogers\tools\services\ServiceLocator;
use Craft;
use craft\console\Application as Console;
use craft\queue\BaseJob;
use craft\queue\QueueInterface;
use Throwable;
use yii\queue\Queue;
use yii\queue\RetryableJobInterface;

/**
 * Deletes an item by it's id in a specific index.
 */
class ElasticSearchDelete extends BaseJob implements RetryableJobInterface
{
    public const TTR = 300;
    public const MAX_ATTEMPTS = 5;

    /**
     * @var null | string
     */
    public ?string $index = null;

    /**
     * @var int|null
     */
    public ?int $id = null;

    public QueueInterface $queue;

    public function getDescription() : string
    {
        return sprintf('Deleting item with id: %d from index: %s', $this->id, $this->index);
    }

    /**
     * @param QueueInterface|Queue $queue
     * @throws ESException
     */
    public function execute($queue) : void
    {
        $this->queue = $queue;

        try {
            $this->start();
        } catch (ESException $e) {
            ServiceLocator::getInstance()->error->reportBackendException($e);
            throw $e;
        }
    }

    /**
     * @throws ESException
     */
    public function start(): void
    {
        $count = 0;
        $es_log = ARLogger::getInstance('elastic-search');

        if (!$this->index) {
            $es_log->error('No index supplied to delete');
            throw new ESException('No index supplied to delete');
        }

        $index = Config::getInstance()->getIndexByName($this->index);
        if (!$index) {
            $msg = sprintf('Invalid index "%s" supplied.', $this->index);
            $es_log->error($msg);
            throw new ESException($msg);
        }

        if (!$this->id) {
            $es_log->error('No id supplied to delete');
            throw new ESException('No id supplied to delete');
        }

        $message = sprintf('Elastic Search: Deleting item with id: %d from index: %s ...', $this->id, $this->index);
        $es_log->info('[Start] ' . $message);

        if (!Craft::$app instanceof Console) {
            $this->setProgress($this->queue, (float) $count, $message);
        }

        $es = ServiceLocator::getInstance()->elastic_search;
        $es->setThrowExceptions(true);

        if ($index->auto_index) {
            $es->deleteFromIndex($index->indexName(), $this->id);
        }

        // delete from the sayt index(es)
        foreach (Config::getInstance()->getIndexes() as $index) {
            if ($index->type === IndexType::SAYT && $index->auto_index) {
                $es->deleteFromIndex($index->indexName(), $this->id);
            }
        }

        $count++;

        if (!Craft::$app instanceof Console) {
            $this->setProgress($this->queue, (float) $count, $message);
        }
    }

    /**
     * Time to retry
     * @return int
     */
    public function getTtr() : int
    {
        return self::TTR;
    }

    /**
     * @param int $attempt
     * @param Throwable $error
     * @return bool
     */
    public function canRetry($attempt, $error) : bool
    {
        $can_retry = $attempt <= self::MAX_ATTEMPTS;
        if (!$can_retry) {
            $msg = sprintf('Ran out of attempts when deleting item %d from ES index "%s".', $this->id, $this->index);
            ServiceLocator::getInstance()->error->reportBackendException($error, true, $msg);
        }
        return $can_retry;
    }
}
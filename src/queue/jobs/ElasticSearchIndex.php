<?php

namespace alanrogers\tools\queue\jobs;

use alanrogers\tools\services\ARLogger;
use alanrogers\tools\services\es\Config;
use alanrogers\tools\services\es\ESException;
use alanrogers\tools\services\es\IndexType;
use alanrogers\tools\services\es\Maintenance;
use alanrogers\tools\services\es\SearchFactory;
use alanrogers\tools\services\ServiceLocator;
use craft\queue\BaseJob;
use craft\queue\QueueInterface;
use yii\queue\Queue;
use yii\queue\RetryableJobInterface;

/**
 * Queue job for performing these actions on the indexes configured:
 * - Create an index, with the configured mapping and settings
 * - Update an index mapping (Note this may not be possible to complete as some field types cannot be converted)
 * - Update settings for an index
 * - Delete an index
 */
class ElasticSearchIndex extends BaseJob implements RetryableJobInterface
{
    public const int TTR = 300; // 5 mins
    public const int MAX_ATTEMPTS = 5;

    public const string ACTION_CREATE_INDEX = 'create';
    public const string ACTION_UPDATE_INDEX_MAPPING = 'update-mapping';
    public const string ACTION_UPDATE_SETTINGS = 'update-settings';
    public const string ACTION_DELETE_INDEX = 'delete';

    public const array ALLOWED_ACTIONS = [
        self::ACTION_CREATE_INDEX,
        self::ACTION_UPDATE_INDEX_MAPPING,
        self::ACTION_UPDATE_SETTINGS,
        self::ACTION_DELETE_INDEX
    ];

    /**
     * @var null | string
     */
    public ?string $index = null;

    /**
     * Must be set to one of the `ElasticSearchIndex::ACTION_*` class constants.
     * @var string|null
     */
    public ?string $action = null;

    private QueueInterface|Queue $queue;

    /**
     * @throws ESException
     */
    public function execute($queue): void
    {
        $this->queue = $queue;

        try {
            $this->start();
        } catch (ESException $e) {
            ServiceLocator::getInstance()->error->reportBackendException($e);
            throw $e;
        }
    }

    public function getTtr(): int
    {
        return self::TTR;
    }

    public function canRetry($attempt, $error): bool
    {
        $can_retry = $attempt <= self::MAX_ATTEMPTS;
        if (!$can_retry) {
            $msg = sprintf('Ran out of attempts when adjusting ES index / settings for section "%s".', $this->index);
            ServiceLocator::getInstance()->error->reportBackendException($error, true, $msg);
        }
        return $can_retry;
    }

    public function getDescription() : string
    {
        return sprintf('Elastic Search index action "%s" on index "%s".', $this->action, $this->index);
    }

    /**
     * @throws ESException
     */
    public function start(): void
    {
        $maintenance = new Maintenance($this->index);

        $mutex = $maintenance->acquireMutex();
        if (!$mutex) {
            throw new ESException('Could not acquire ES maintenance Mutex.');
        }

        $es_log = ARLogger::getInstance('elastic-search');
        $es_log->info('[Start] Updating ES Indexes...');

        if (!in_array($this->action, self::ALLOWED_ACTIONS)) {
            $maintenance->releaseMutex();
            $msg = sprintf('Cannot perform invalid action: %s', $this->action);
            $es_log->error($msg);
            throw new ESException($msg);
        }

        $index = Config::getInstance()->getIndexByName($this->index);
        if (!$index) {
            $maintenance->releaseMutex();
            $msg = sprintf('Supplied index "%s" does not exist', $this->index);
            $es_log->error($msg);
            throw new ESException($msg);
        }

        if ($index->type === IndexType::ALL) {
            $maintenance->releaseMutex();
            $msg = 'Cannot directly alter "all" index. Must be done for each index individually.';
            $es_log->error($msg);
            throw new ESException($msg);
        }

        $search = SearchFactory::getSearch($this->index);
        if (!$search) {
            $maintenance->releaseMutex();
            $msg = sprintf('Cannot find search instance for index "%s".', $this->index);
            $es_log->error($msg);
            throw new ESException($msg);
        }

        $index_name = $index->indexName();
        $es = $search->getES();
        $es->setThrowExceptions(true);

        $mapping = $index->fieldMapping();
        $settings = Config::getInstance()->getGlobalIndexSettings();
        $success = false;
        $action_exception = null;

        try {
            switch ($this->action) {
                case self::ACTION_CREATE_INDEX :
                    $success = $es->createIndex($index_name, $mapping, $settings);
                    break;
                case self::ACTION_UPDATE_INDEX_MAPPING :
                    $success = $es->updateIndexMapping($index_name, $mapping);
                    break;
                case self::ACTION_UPDATE_SETTINGS :
                    $success = $es->updateIndexSettings($index_name, $settings);
                    break;
                case self::ACTION_DELETE_INDEX :
                    $success = $es->deleteIndex($index_name);
                    break;
            }
        } catch (ESException $e) {
            $action_exception = $e;
        }

        if ($action_exception || !$success) {
            $maintenance->releaseMutex();
            $msg = sprintf('Could not perform the "%s" action on the "%s" index.', $this->action, $this->index);
            $es_log->error($msg);
            $es_log->error('Errors: ' . json_encode($es->getErrors()));
            throw new ESException($msg, $action_exception?->getCode() ?? null, $action_exception);
        }

        $es_log->info('[End] Updating ES Indexes.');
        $maintenance->releaseMutex();
    }
}
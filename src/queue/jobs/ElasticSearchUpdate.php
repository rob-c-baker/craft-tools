<?php declare(strict_types=1);

namespace alanrogers\tools\queue\jobs;

use alanrogers\tools\services\ARLogger;
use alanrogers\tools\services\es\Config;
use alanrogers\tools\services\es\ESException;
use alanrogers\tools\services\es\IndexType;
use alanrogers\tools\services\es\SearchFactory;
use alanrogers\tools\services\ServiceLocator;
use Craft;
use craft\console\Application as Console;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\queue\BaseJob;
use craft\queue\QueueInterface;
use Throwable;
use yii\queue\Queue;
use yii\queue\RetryableJobInterface;

class ElasticSearchUpdate extends BaseJob implements RetryableJobInterface
{
    public const TTR = 7200; // 2 hours
    public const MAX_ATTEMPTS = 5;

    /**
     * @var null | callable
     */
    private $progress_callback = null;

    /**
     * @var bool
     */
    public bool $delete_index_first = false;

    /**
     * Whether to include associations with disabled categories related to each entry in the section for the index.
     * @var bool
     */
    public bool $index_disabled_related_categories = true;

    /**
     * @var null | string
     */
    public ?string $index = null;

    /**
     * If supplied will update just this specific element Id
     * @var int[]
     */
    public array $element_ids = [];

    /**
     * If set true, will add a refresh=wait_for parameter to the ES modification so that the response will not return until
     * the index is already queryable.
     * @var bool
     */
    public bool $refresh_index = false;

    public QueueInterface $queue;

    public function getDescription() : string
    {
        $msg = 'Elastic Search update';
        if ($this->index || $this->element_ids) {

            $extra = [];

            if ($this->index) {
                $extra[] = 'index: ' . $this->index;
            }

            if ($this->element_ids) {
                $extra[] = 'elements: ' . implode(',', $this->element_ids);
            }
            if ($extra) {
                $msg .= ' (' . implode(' | ', $extra) . ')';
            }

        }
        return $msg;
    }

    /**
     * @param QueueInterface|Queue $queue
     * @throws ESException
     */
    public function execute($queue) : void
    {
        $this->queue = $queue;
        $this->start();
    }

    /**
     * @param string $index
     * @return $this
     */
    public function setIndex(string $index): self
    {
        $this->index = $index;
        return $this;
    }

    /**
     * @param int[] $element_ids
     * @return $this
     */
    public function setElementIds(array $element_ids) : self
    {
        $this->element_ids = $element_ids;
        return $this;
    }

    /**
     * Sets whether an index is deleted before being re-loaded
     * @param bool $state
     * @return $this
     */
    public function setDeleteIndexFirst(bool $state) : self
    {
        $this->delete_index_first = $state;
        return $this;
    }

    /**
     * @throws ESException
     */
    public function start(): void
    {
        $count = 0;
        $es_log = ARLogger::getInstance('elastic-search');
        $es_log->info('[Start] Updating Elastic Search...');

        if (!Craft::$app instanceof Console) {
            $this->setProgress($this->queue, (float) $count, 'Updating Elastic Search');
        }

        if (!$this->index) {
            $es_log->error('No index supplied to update');
            throw new ESException('No index supplied to update');
        }

        $index = Config::getInstance()->getIndexByName($this->index);
        if (!$index) {
            $msg = sprintf('Supplied index "%s" does not exist', $this->index);
            $es_log->error($msg);
            throw new ESException($msg);
        }

        if ($index->type === IndexType::ALL) {
            $msg = 'Cannot directly update "all" index. Must be done for each index individually.';
            $es_log->error($msg);
            throw new ESException($msg);
        }

        $search = SearchFactory::getSearch($this->index);

        if ($search) {

            $index_name = $index->indexName();
            $es = $search->getES();

            if ($this->delete_index_first) {
                $es->deleteIndex($index->name);
            }

            $mapping = $index->fieldMapping();
            $settings = Config::getInstance()->getGlobalIndexSettings();

            if (!$es->indexExists($index_name)) {
                $es->createIndex($index_name, $mapping, $settings);
            } else {
                $es->updateIndexMapping($index_name, $mapping, $settings);
            }

            $eager_fields = $index->eagerLoads([
                'index_disabled_related_categories' => $this->index_disabled_related_categories
            ]);

            if ($this->element_ids) {
                $ids = $this->element_ids;
                $log_msg = 'Using passed in element_ids: %s';
            } else {
                $ids = $this->getEntriesQuery()->ids();
                $log_msg = 'Using element_ids from entries query: %s';
            }

            $es_log->info(sprintf($log_msg, implode(',', $ids)));

            foreach ($ids as $id) {

                $entry = $this->getEntriesQuery()->id($id)->with($eager_fields)->one();
                if (!$entry) {
                    $es_log->info('[Skipping] Could not find entry with id: ' . $id);
                    $count++;
                    if (!Craft::$app instanceof Console) {
                        $this->setProgress($this->queue, (float) $count, 'Updating Elastic Search');
                    }
                    continue;
                }

                if (!$search->isAllowedInIndex($entry)) {
                    if ($es->existsInIndex($index_name, $entry->id)) {
                        // not allowed but present. Remove from index.
                        $es->deleteFromIndex($index_name, $entry->id);
                    }
                } else {

                    try {
                        $data = $search->transformEntryData($entry);
                    } catch (ESException $e) {
                        // report the exception and carry on
                        ServiceLocator::getInstance()->error->reportBackendException($e, true);
                        $data = [];
                    }

                    if ($data) {
                        $override_params = [];
                        if ($this->refresh_index) {
                            $override_params['refresh'] = 'wait_for';
                        }
                        if ($es->existsInIndex($index_name, $id)) {
                            $es->updateInIndex($index_name, $id, $data, $override_params);
                        } else {
                            $es->addToIndex($index_name, $id, $data, $override_params);
                        }
                    }
                }

                $count++;
                if (Craft::$app instanceof Console) {
                    if ($this->progress_callback) {
                        call_user_func($this->progress_callback, $count);
                    }
                } else {
                    $this->setProgress($this->queue, (float) $count, 'Updating Elastic Search');
                }
            }

            $es_log->info(
                sprintf('[Finished] Updating Elastic Search index %s (count: %d)...', $index_name, $count)
            );
        }
    }

    /**
     * Gets the total number of entries we are working on.
     * @return int
     */
    public function getTotalEntries(): int
    {
       return (int) $this->getEntriesQuery()->count();
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
            $msg = sprintf('Ran out of attempts when updating ES search index for section "%s".', $this->index);
            ServiceLocator::getInstance()->error->reportBackendException($error, true, $msg);
        }
        return $can_retry;
    }

    /**
     * @param Callable $callback
     */
    public function setProgressCallback(callable $callback): void
    {
        $this->progress_callback = $callback;
    }

    /**
     * @return EntryQuery
     */
    private function getEntriesQuery() : EntryQuery
    {
        $query = Entry::find()->withStructure(false);

        if ($this->index && $this->index !== '*' && $this->index !== IndexType::SAYT->value) {
            $query->section($this->index);
        }

        return $query;
    }
}
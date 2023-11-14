<?php declare(strict_types=1);

namespace alanrogers\tools\console\controllers;

use alanrogers\tools\queue\jobs\ElasticSearchIndex;
use alanrogers\tools\queue\jobs\ElasticSearchUpdate;
use alanrogers\tools\services\es\ESException;
use Craft;
use craft\helpers\Console;
use Exception;
use yii\console\Controller;
use yii\console\ExitCode;

class ElasticSearchController extends Controller
{
    /**
     * Whether to push the job to the queue - default: false|0
     * @var bool
     */
    public bool $queue = false;

    /**
     * Populated via command line, true when --deleteindex parameter is present
     * @var bool
     */
    public bool $deleteindex = false;

    /**
     * Will associate disabled categories with the entry being indexed, if they exist and is enabled for the particular
     * section being indexed.
     * @var bool
     */
    public bool $index_disabled_related_categories = true;

    /**
     * Add to the allowed command line options allowed
     * @param string $action_id
     * @return string[]
     */
    public function options($action_id) : array
    {
        $options = parent::options($action_id);
        if ($action_id === 'update') {
            $options[] = 'deleteindex';
            $options[] = 'index_disabled_related_categories';
        }
        $options[] = 'queue';
        return $options;
    }

    /**
     * Updates either specific sections or all sections into the ElasticSearch instance.
     * Called like this:
     * ./craft ar/elastic-search/update [index_name] [--deleteindex=[1|0]]
     * @param string $index
     * @return int
     * @throws ESException
     */
    public function actionUpdate(string $index) : int
    {
        $es_update = new ElasticSearchUpdate();
        $es_update->setIndex($index);
        $es_update->setDeleteIndexFirst(false);

        $es_update->index_disabled_related_categories = $this->index_disabled_related_categories;

        if ($this->deleteindex) {
            $result = $this->confirm(sprintf('This will delete the index "%s" before re-loading. Are you sure?', $index));
            if (!$result) {
                return ExitCode::OK;
            }
            $es_update->setDeleteIndexFirst(true);
        }

        try {
            $total_entries = $es_update->getTotalEntries();
        } catch (Exception $e) {
            throw new ESException($e->getMessage(), (int) $e->getCode(), $e);
        }

        if ($total_entries > 0) {

            if (!$this->queue) {
                $es_update->setProgressCallback(function($complete_count) use ($total_entries) {
                    Console::updateProgress($complete_count, $total_entries);
                });
                Console::startProgress(0, $total_entries);
            }

            if ($this->queue) {
                Craft::$app->getQueue()->push($es_update);
            } else {
                $es_update->execute(Craft::$app->getQueue());
                Console::endProgress();
            }

            return ExitCode::OK;
        }

        Console::error('No entries found to process, is the index name correct?');
        return ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * @throws ESException
     */
    public function actionCreate(string $index) : int
    {
        $create_job = self::getIndexJob($index, ElasticSearchIndex::ACTION_CREATE_INDEX);

        if ($this->queue) {
            Craft::$app->getQueue()->push($create_job);
        } else {
            $create_job->execute(Craft::$app->getQueue());
        }

        return ExitCode::OK;
    }

    /**
     * @throws ESException
     */
    public function actionUpdateMapping(string $index) : int
    {
        $result = $this->confirm(sprintf('The index "%s" will be closed so mapping can be altered, this may halt any other actions happening at the same time. Are you sure?', $index));
        if (!$result) {
            return ExitCode::OK;
        }

        $update_index_job = self::getIndexJob($index, ElasticSearchIndex::ACTION_UPDATE_INDEX_MAPPING);

        if ($this->queue) {
            Craft::$app->getQueue()->push($update_index_job);
        } else {
            $update_index_job->execute(Craft::$app->getQueue());
        }

        return ExitCode::OK;
    }

    /**
     * @throws ESException
     */
    public function actionUpdateSettings(string $index) : int
    {
        $result = $this->confirm(sprintf('The index "%s" will be closed so settings can be altered, this may halt any other actions happening at the same time. Are you sure?', $index));
        if (!$result) {
            return ExitCode::OK;
        }

        $update_settings_job = self::getIndexJob($index, ElasticSearchIndex::ACTION_UPDATE_SETTINGS);

        if ($this->queue) {
            Craft::$app->getQueue()->push($update_settings_job);
        } else {
            $update_settings_job->execute(Craft::$app->getQueue());
        }

        return ExitCode::OK;
    }

    /**
     * @throws ESException
     */
    public function actionDelete(string $index) : int
    {
        $result = $this->confirm(sprintf('This will delete the index "%s" - it cannot be recovered, it must be re-created. Are you sure?', $index));
        if (!$result) {
            return ExitCode::OK;
        }

        $update_settings_job = self::getIndexJob($index, ElasticSearchIndex::ACTION_DELETE_INDEX);

        if ($this->queue) {
            Craft::$app->getQueue()->push($update_settings_job);
        } else {
            $update_settings_job->execute(Craft::$app->getQueue());
        }

        return ExitCode::OK;
    }

    private static function getIndexJob(string $index, string $action) : ElasticSearchIndex
    {
        return new ElasticSearchIndex([
            'index' => $index,
            'action' => $action
        ]);
    }
}
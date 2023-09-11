<?php declare(strict_types=1);

namespace alanrogers\tools\console\controllers;

use alanrogers\tools\queue\jobs\ElasticSearchUpdate;
use alanrogers\tools\services\es\ESException;
use Craft;
use craft\helpers\Console;
use yii\console\Controller;
use yii\console\ExitCode;

class ElasticSearchController extends Controller
{
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
        $options[] = 'deleteindex';
        $options[] = 'index_disabled_related_categories';
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

        $total_entries = $es_update->getTotalEntries();

        if ($total_entries > 0) {

            $es_update->setProgressCallback(function($complete_count) use ($total_entries) {
                Console::updateProgress($complete_count, $total_entries);
            });

            Console::startProgress(0, $total_entries);

            $es_update->execute(Craft::$app->getQueue());

            Console::endProgress();
            return ExitCode::OK;
        }

        Console::error('No entries found to process, is the index name correct?');
        return ExitCode::UNSPECIFIED_ERROR;
    }
}
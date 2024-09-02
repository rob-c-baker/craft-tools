<?php declare(strict_types=1);

namespace alanrogers\tools\services\es;

use alanrogers\tools\queue\jobs\ElasticSearchDelete;
use alanrogers\tools\queue\jobs\ElasticSearchUpdate;
use Craft;
use craft\base\Element;
use craft\elements\Entry;
use craft\events\ElementEvent;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;
use craft\services\Elements;
use yii\base\Event;

class Events
{
    /**
     * Registers events that are listened to for modifying indexes
     */
    public static function registerEvents() : void
    {
        if (!Config::getInstance()->isEnabled()) {
            return;
        }

        // after save
        Event::on(Entry::class, Element::EVENT_AFTER_SAVE, function(ModelEvent $e)
        {
            /** @var Entry $entry */
            $entry = $e->sender;

            // Only if we want this in Elastic search...
            if ($entry->getStatus() === Entry::STATUS_LIVE
                && !ElementHelper::isDraftOrRevision($entry)
                && $entry->getSection() !== null) {
                self::afterEntrySave($entry);
            }
        });

        // before delete
        Event::on(Elements::class, Elements::EVENT_BEFORE_DELETE_ELEMENT, function(ElementEvent $e)
        {
            /** @var $entry */
            $entry = $e->element;
            if (!($entry instanceof Entry)) {
                return;
            }

            // NOTE: If something gets into ElasticSearch, there are no conditions for this, so it will get deleted there too.
            if ($entry->getSection() !== null) {
                self::beforeEntryDelete($entry);
            }
        });
    }

    /**
     * Should be called when an element is saved, so it's contents can be injected into index.
     * Only throws exception in devMode
     * @param Entry $entry
     * @throws ESException
     */
    private static function afterEntrySave(Entry $entry): void
    {
        $section_job = null;
        $sayt_jobs = [];

        if ($entry->getSection() === null) {
            // probably an entry-type linked to a matrix field, skip
            return;
        }

        // Make sure this is a section / index defined in ES...
        $index = Config::getInstance()->getIndexByName($entry->getSection()->handle);
        if (!$index) {
            return;
        }

        if ($index->auto_index) {
            // for the section specific index
            $section_job = new ElasticSearchUpdate([
                'delete_index_first' => false,
                'index' => $index->indexName(false),
                'element_ids' => [ $entry->id ],
                'refresh_index' => true
            ]);
        }

        // for the SAYT search(es) that uses an index not directly related to a section (spans all sections)
        foreach (Config::getInstance()->getIndexes() as $index) {
            if ($index->type === IndexType::SAYT && $index->auto_index) {
                $sayt_jobs[] = new ElasticSearchUpdate([
                    'delete_index_first' => false,
                    'index' => $index->indexName(false),
                    'element_ids' => [ $entry->id ],
                    'refresh_index' => true
                ]);
            }
        }

        if (Craft::$app->getConfig()->getGeneral()->devMode) {
            $section_job?->execute(Craft::$app->getQueue());
            if ($sayt_jobs) {
                foreach ($sayt_jobs as $job) {
                    $job->execute(Craft::$app->getQueue());
                }
            }
        } else {
            if ($section_job) {
                Craft::$app->getQueue()->delay(0)->ttr(300)->priority(100)->push($section_job);
            }
            if ($sayt_jobs) {
                $queue = Craft::$app->getQueue();
                foreach ($sayt_jobs as $job) {
                    $queue->delay(0)->ttr(300)->priority(100)->push($job);
                }
            }
        }
    }

    /**
     * Should be called when an element is deleted, so it can be removed from index
     * @param Entry $el
     * @throws ESException
     */
    private static function beforeEntryDelete(Entry $el): void
    {
        $index = Config::getInstance()->getIndexByName($el->getSection()->handle);
        if (!$index) {
            return;
        }

        $dev_mode = Craft::$app->getConfig()->getGeneral()->devMode;

        if ($index->auto_index) {
            $job = new ElasticSearchDelete([
                'index' => $index->indexName(false),
                'id' => $el->id
            ]);
            if ($dev_mode) {
                $job->execute(Craft::$app->getQueue());
            } else {
                Craft::$app->getQueue()->delay(0)->ttr(300)->priority(100)->push($job);
            }
        }

        // delete from the sayt index(es)
        $sayt_jobs = [];
        foreach (Config::getInstance()->getIndexes() as $index) {
            if ($index->type === IndexType::SAYT && $index->auto_index) {
                $sayt_jobs[] = new ElasticSearchDelete([
                    'index' => $index->indexName(false),
                    'id' => $el->id,
                    'ignore_404' => true
                ]);
            }
        }

        if ($sayt_jobs) {
            foreach ($sayt_jobs as $sayt_job) {
                if ($dev_mode) {
                    $sayt_job->execute(Craft::$app->getQueue());
                } else {
                    Craft::$app->getQueue()->delay(0)->ttr(300)->priority(100)->push($sayt_job);
                }
            }
        }
    }
}
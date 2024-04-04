<?php declare(strict_types=1);

namespace alanrogers\tools\console\controllers;

use alanrogers\tools\models\sitemaps\XMLSitemap;
use alanrogers\tools\queue\jobs\XMLSitemap as XMLSitemapJob;
use alanrogers\tools\services\AlanRogersCache;
use alanrogers\tools\services\ServiceLocator;
use alanrogers\tools\services\sitemap\SitemapConfig;
use alanrogers\tools\services\sitemap\SitemapException;
use alanrogers\tools\services\sitemap\SitemapType;
use Craft;
use yii\base\InvalidConfigException;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

class SiteMapController extends Controller
{
    /**
     * Generate an XML sitemap for the supplied section handle.
     * @param string $identifier The handle for the section that is being generated
     * @param bool $use_queue Whether to send jobs to the queue or run them straight away. (default: false)
     * @throws InvalidConfigException
     * @throws SitemapException
     */
    public function actionGenerate(string $identifier, bool $use_queue=false) : int
    {
        $config = SitemapConfig::getConfig($identifier);
        if (!$config) {
            Console::error(sprintf('Cannot generate sitemap for: %s, it is not in the allowed list.', $identifier));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        /** @var XMLSitemap $model */
        $model = new $config->model_class($config);

        if ($config->type === SitemapType::SECTION) {
            $section_handle = $config->getName(true);
            if (!Craft::$app->getSections()->getSectionByHandle($section_handle)) {
                Console::error(sprintf('Attempted section sitemap generation, but no section found for: %s', $config->getName()));
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $cache = ServiceLocator::getInstance()->cache;
        $cache_key = 'site-map-generating-' . $config->getName();
        $existing_job_id = $cache->get($cache_key);

        if ($existing_job_id) {
            Console::error(sprintf('Sitemap already being generated for: %s.', $config->getName()));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $total_items = $model->totalItems();

        if ($total_items > SitemapConfig::MAX_SIZE) {
            // we need to split this sitemap
            $config->chunk_count = (int) ceil($total_items / SitemapConfig::MAX_SIZE);
            for ($i = 1; $i <= $config->chunk_count; $i++) {
                // duplicate the config and set the bits relevant to the chunk
                $c = clone $config;
                $c->start = ($i - 1) * SitemapConfig::MAX_SIZE + 1;
                $c->end = min($i * SitemapConfig::MAX_SIZE, $total_items);
                $c->chunk_index = $i - 1;
                $this->generateSitemap($c, $cache, $cache_key, $use_queue);
            }
        } else {
            $this->generateSitemap($config, $cache, $cache_key, $use_queue);
        }

        Console::output(sprintf('...Sitemap generation complete for %s.', $config->getName()));
        return ExitCode::OK;
    }

    /**
     * @throws InvalidConfigException|SitemapException
     */
    private function generateSitemap(SitemapConfig $config, AlanRogersCache $cache, string $cache_key, bool $use_queue): void
    {
        $job = new XMLSitemapJob($config, $cache_key);
        $cache->set($cache_key, '__COMMAND-LINE-INVOCATION__', $job->getTtr());

        $config_name = $config->getName();
        if ($config->start) {
            $config_name .= ' : ' . $config->start;
        }
        if ($config->end) {
            $config_name .= ' - ' . $config->end;
        }

        if ($use_queue) {
            Console::output(sprintf('Sitemap job added to the queue for: %s...', $config_name));
            Craft::$app->getQueue()->delay(0)->push($job);
        } else {
            Console::output(sprintf('Sitemap generating for: %s...', $config_name));
            $job->execute(Craft::$app->getQueue());
        }
    }

    /**
     * Generates ALL sitemaps for all sections that have them.
     * @param bool $use_queue Whether to send jobs to the queue or run them straight away. (default: false)
     * @return int
     * @throws InvalidConfigException|SitemapException
     */
    public function actionGenerateAll(bool $use_queue=false) : int
    {
        if ($use_queue) {
            Console::output('Queueing all sitemaps...');
        } else {
            Console::output('Generating all sitemaps...');
        }

        $sitemap_configs = SitemapConfig::getAllConfigs();
        $ok_count = 0;

        foreach ($sitemap_configs as $config) {
            $result = $this->actionGenerate($config->getName(), $use_queue);
            if ($result === ExitCode::OK) {
                $ok_count++;
            }
        }

        return $ok_count === count($sitemap_configs) ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }
}
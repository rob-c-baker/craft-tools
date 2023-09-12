<?php declare(strict_types=1);

namespace alanrogers\tools\console\controllers;

use alanrogers\tools\queue\jobs\XMLSitemap as XMLSitemapJob;
use alanrogers\tools\services\ServiceLocator;
use alanrogers\tools\services\sitemap\SitemapConfig;
use alanrogers\tools\services\sitemap\SitemapType;
use Craft;
use craft\helpers\StringHelper;
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
     */
    public function actionGenerate(string $identifier, bool $use_queue=false) : int
    {
        $config = SitemapConfig::getConfig($identifier);
        if (!$config) {
            Console::error(sprintf('Cannot generate sitemap for: %s, it is not in the allowed list.', $identifier));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($config->type === SitemapType::SECTION) {
            $section_handle = StringHelper::camelCase($config->name);
            if (!Craft::$app->getSections()->getSectionByHandle($section_handle)) {
                Console::error(sprintf('Attempted section sitemap generation, but no section found for: %s', $config->name));
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $cache = ServiceLocator::getInstance()->cache;
        $cache_key = 'site-map-generating-' . $config->name;

        $existing_job_id = $cache->get($cache_key);

        if ($existing_job_id) {
            Console::error(sprintf('Sitemap already being generated for: %s.', $config->name));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $job = new XMLSitemapJob($config);

        $cache->set($cache_key, '__COMMAND-LINE-INVOCATION__', $job->getTtr());

        if ($use_queue) {
            Console::output(sprintf('Sitemap job added to the queue for: %s...', $config->name));
            Craft::$app->getQueue()->delay(0)->push($job);
        } else {
            Console::output(sprintf('Sitemap generating for: %s...', $config->name));
            $job->execute(Craft::$app->getQueue());
        }

        Console::output(sprintf('...Sitemap generation complete for %s.', $config->name));
        return ExitCode::OK;
    }

    /**
     * Generates ALL sitemaps for all sections that have them.
     * @param bool $use_queue Whether to send jobs to the queue or run them straight away. (default: false)
     * @return int
     * @throws InvalidConfigException
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
            $result = $this->actionGenerate($config->name, $use_queue);
            if ($result === ExitCode::OK) {
                $ok_count++;
            }
        }

        return $ok_count === count($sitemap_configs) ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
    }
}
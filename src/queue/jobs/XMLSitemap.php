<?php declare(strict_types=1);

namespace alanrogers\tools\queue\jobs;

use alanrogers\tools\services\ServiceLocator;
use alanrogers\tools\services\sitemap\SitemapConfig;
use alanrogers\tools\services\sitemap\SitemapGenerator;
use Craft;
use craft\console\Application as Console;
use craft\queue\BaseJob;
use craft\queue\QueueInterface;
use yii\base\InvalidConfigException;
use yii\queue\RetryableJobInterface;

/**
 * @property-read int $ttr
 */
class XMLSitemap extends BaseJob implements RetryableJobInterface
{
    public const int TTR = 21600; // 6 hours
    public const int MAX_ATTEMPTS = 3;

    /**
     * @var SitemapConfig
     */
    public SitemapConfig $config;

    /**
     * @var QueueInterface
     */
    public QueueInterface $queue;

    /**
     * @param SitemapConfig $config
     */
    public function __construct(SitemapConfig $config)
    {
        $this->config = $config;
        parent::__construct();
    }

    /**
     * @inheritDoc
     * @throws InvalidConfigException
     */
    public function execute($queue) : void
    {
        $this->queue = $queue;
        $this->start();
    }

    public function getDescription(): string
    {
        $desc = 'Generating XML sitemap identified by: ' . $this->config->name;
        if ($this->config->start) {
            $desc .= ' : ' . $this->config->start;
        }
        if ($this->config->end) {
            $desc .= ' - ' . $this->config->end;
        }
        return $desc;
    }

    /**
     * @throws InvalidConfigException
     */
    private function start(): void
    {
        $count = 0;

        $this->config->progress_callback = function($total, $processed)
        {
            if ($total > 0) {
                $this->setProgress($this->queue, (float) ($processed / $total), $this->getDescription());
            } else {
                $this->setProgress($this->queue, 1.0, $this->getDescription());
            }
        };

        register_shutdown_function([ self::class, 'releaseCacheKey'], $this->config->cache_key);

        $service = new SitemapGenerator($this->config);

        if (!Craft::$app instanceof Console) {
            $this->setProgress($this->queue, (float) $count, $this->getDescription());
        }

        $service->generate();
    }

    public static function releaseCacheKey(string $cache_key) : void
    {
        ServiceLocator::getInstance()->cache->delete($cache_key);
    }

    /**
     * @inheritDoc
     */
    public function getTtr() : int
    {
        return self::TTR;
    }

    /**
     * @inheritDoc
     */
    public function canRetry($attempt, $error) : bool
    {
        $can_retry = $attempt <= self::MAX_ATTEMPTS;
        if (!$can_retry) {
            ServiceLocator::getInstance()->error->reportBackendException($error, true);
        }
        return $can_retry;
    }

}
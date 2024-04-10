<?php declare(strict_types=1);

namespace alanrogers\tools\queue\jobs;

use alanrogers\tools\services\ServiceLocator;
use alanrogers\tools\services\sitemap\SitemapConfig;
use alanrogers\tools\services\sitemap\SitemapException;
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

    private bool $finished = false;

    /**
     * @var QueueInterface
     */
    public QueueInterface $queue;

    /**
     * @param SitemapConfig $config
     */
    public function __construct(
        public SitemapConfig $config,
        private ?string $cache_key=null
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     * @throws InvalidConfigException|SitemapException
     */
    public function execute($queue) : void
    {
        $this->queue = $queue;
        $this->start();
    }

    public function getDescription(): string
    {
        $desc = 'Generating XML sitemap identified by: ' . $this->config->getName();
        if ($this->config->start) {
            $desc .= ' : ' . $this->config->start;
        }
        if ($this->config->end) {
            $desc .= ' - ' . $this->config->end;
        }
        return $desc;
    }

    /**
     * @throws InvalidConfigException|SitemapException
     */
    private function start(): void
    {
        // Set a memory limit as this is invoked by the command line either directly or as a queue job
        // and by default memory limit is disabled on the command line, so we don't want to exhaust the
        // server's memory
        ini_set('memory_limit', '128M');

        $count = 0;

        $this->config->progress_callback = function($total, $processed)
        {
            if ($total > 0) {
                $this->setProgress($this->queue, (float) ($processed / $total), $this->getDescription());
            } else {
                $this->setProgress($this->queue, 1.0, $this->getDescription());
            }
        };

        $service = new SitemapGenerator($this->config);

        // in case the queue job is stopped by the OS / user
        register_shutdown_function([ $this, 'finishGenerating' ], $service);

        if (!Craft::$app instanceof Console) {
            $this->setProgress($this->queue, (float) $count, $this->getDescription());
        }

        $service->generate();

        $this->finishGenerating($service);
    }

    public function finishGenerating(SitemapGenerator $generator) : void
    {
        if ($this->finished) {
            return;
        }
        $this->finished = true;
        $generator->setSitemapGenerating(false);
        if ($this->cache_key !== null) {
            ServiceLocator::getInstance()->cache->delete($this->cache_key);
        }
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
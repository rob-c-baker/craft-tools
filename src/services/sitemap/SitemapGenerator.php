<?php declare(strict_types=1);

namespace alanrogers\tools\services\sitemap;

use alanrogers\arimager\ARImager;
use alanrogers\arimager\models\TransformedImageInterface;
use alanrogers\tools\helpers\SitemapHelper;
use alanrogers\tools\queue\jobs\XMLSitemap;
use alanrogers\tools\services\ServiceLocator;
use alanrogers\tools\models\sitemaps\XMLSitemap as XMLSitemapModel;
use Craft;
use craft\elements\Asset;
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use Throwable;
use yii\base\InvalidConfigException;
use yii\caching\Cache;

class SitemapGenerator
{
    public const string CACHE_KEY_PREFIX = 'xml-sitemap-';

    public const string SITEMAP_GENERATING_CACHE_KEY = 'sitemaps-generating-now';

    /**
     * Number of seconds until cache expires
     */
    private const int CACHE_TTL = 172800; // 172800 == 2 days in seconds

    /**
     * @var XMLSitemapModel|null
     */
    private ?XMLSitemapModel $model;


    /**
     * @var int
     */
    private int $processed_items = 0;

    /**
     * @var null|callable
     */
    private $progress_callback = null;

    /**
     * XMLSitemapGenerator constructor.
     * @param SitemapConfig $config
     * @param Cache|null $cache
     */
    public function __construct(
        private readonly SitemapConfig $config,
        private ?Cache $cache=null
    ) {
        if (!$this->config->getName()) {
            throw new InvalidArgumentException('You must pass an identifier string in the "identifier" key of the config array.');
        }

        $this->model = new $this->config->model_class($this->config);

        $this->config->cache_key = self::getCacheKey($this->config->getName(), $this->config->start, $this->config->end);

        if ($this->cache === null) {
            $this->cache = ServiceLocator::getInstance()->cache;
        }
    }

    public function getModel(): XMLSitemapModel
    {
        return $this->model;
    }

    /**
     * @param string $identifier
     * @param int|null $start
     * @param int|null $end
     * @return string
     */
    public static function getCacheKey(string $identifier, ?int $start = null, ?int $end = null) : string
    {
        return self::CACHE_KEY_PREFIX . SitemapHelper::sitemapFilename($identifier, $start, $end);
    }

    /**
     * Only throws exception in devMode
     * @throws SitemapException
     * @noinspection PhpUnused
     */
    public function getXML() : XMLSitemapModel
    {
        if ($this->config->use_cache) {
            $this->model->xml = $this->getCachedXML();
            if ($this->model->xml) {
                $this->model->generated = true;
                return $this->model;
            }
        }

        if ($this->config->shouldQueue()) {

            // Not generated yet, return something empty for now:
            $this->model->generated = false;

            $lines = [
                '<?xml version="1.0" encoding="UTF-8"?>',
                '<?xml-stylesheet type="text/xsl" href="/sitemap-empty.xsl"?>',
                '<!-- This sitemap has not been generated yet. -->',
                '<urlset>',
                '</urlset>'
            ];

            // Also add job to generate sitemap to queue?
            $this->maybeAddQueueJob();

        } else {

            // set to not use queue, generate straight away:

            try {
                $lines = $this->generate();
                $this->model->generated = true;
            } catch (InvalidConfigException $e) {
                $lines = [
                    '<?xml version="1.0" encoding="UTF-8"?>',
                    '<?xml-stylesheet type="text/xsl" href="/sitemap-empty.xsl"?>',
                    '<!-- There was an error generating the sitemap. -->',
                    '<urlset>',
                    '</urlset>'
                ];
                $this->model->generated = false;
                if (Craft::$app->getConfig()->getGeneral()->devMode) {
                    throw new SitemapException('There was an error generating the sitemap.', $e->getCode(), $e);
                } else {
                    ServiceLocator::getInstance()->error->reportBackendException($e);
                }
            }
        }

        $this->model->xml = implode("\n", $lines);

        return $this->model;
    }

    /**
     * @return string[] An array of XML lines
     * @throws SitemapException
     * @throws InvalidConfigException
     */
    public function generate() : array
    {
        $total_items = $this->model->totalItems($this->config->start, $this->config->end);
        $dev_mode = Craft::$app->getConfig()->getGeneral()->devMode;

        /** @noinspection HttpUrlsUsage */
        /** @noinspection XmlUnusedNamespaceDeclaration */
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'
        ];

        if ($total_items === 0) {
            $msg = 'Zero entries found while generating XML sitemap for: ' . $this->config->getName();
            if ($this->config->start !== null) {
                $msg .= ' (from ' . $this->config->start . ' to ' . ($this->config->end ?? '?' ) . ')';
            }
            if ($dev_mode) {
                throw new SitemapException($msg);
            } else {
                ServiceLocator::getInstance()->error->reportBackEndError($msg);
            }
            $lines[] = '</urlset>';
            return $lines;
        }

        $with = [];

        if ($this->progress_callback) {
            call_user_func($this->progress_callback, $total_items, $this->processed_items);
        }

        if ($this->config->image_field) {
            $with[] = $this->config->image_field;
        }

        $main_img_transform = null;

        if ($this->config->image_transform) {
            $main_img_transform = ARImager::$plugin->transforms::getTransform($this->config->image_transform);
            if (empty($main_img_transform['transform'])) {
                $msg = sprintf('Cannot find image transformation "%s" while generating XML sitemap for "%s".', $this->config->image_transform, $this->config->getName());
                if ($dev_mode) {
                    throw new SitemapException($msg);
                } else {
                    ServiceLocator::getInstance()->error->reportBackEndError($msg);
                }
            }
            $main_img_transform = $main_img_transform['transform'];
        }

        $now = new DateTime();
        $max_memory_usage = memory_get_usage();
        $url_count = 0;

        foreach ($this->model->getURLs($with, $this->config->start, $this->config->end) as $url) {

            if ($dev_mode) {
                $url_count++;
                $current_memory_usage = memory_get_usage();
                if ($current_memory_usage > $max_memory_usage) {
                    $max_memory_usage = $current_memory_usage;
                }
                if ($url_count % 100 === 0) {
                    echo sprintf(
                        "Processed: %d, Memory Usage: Current: %.2f Mb, Max: %.2f Mb\n",
                        $url_count,
                        $current_memory_usage / 1024 / 1024,
                        $max_memory_usage / 1024 / 1024
                    );
                }
            }

            $date_updated = $url->date_updated ?? $url->date_created ?? $now;

            $lines[] = '<url>';

            $lines[] = '<loc>';
            $lines[] = htmlspecialchars($url->url, ENT_XML1, 'UTF-8');
            $lines[] = '</loc>';
            $lines[] = '<lastmod>';
            $lines[] = $date_updated->format(DateTimeInterface::W3C);
            $lines[] = '</lastmod>';

            if ($url->image_field && $this->model->includeImages($url)) {

                // limit image count
                $max_count = $this->model->getMaxImageCount($url);
                $count = 0;

                /** @var Asset $image */
                foreach ($url->image_field as $idx => $image) {

                    if ($main_img_transform) { // transforming?
                        try {
                            /** @var TransformedImageInterface|null $transformed */
                            $transformed = ARImager::getInstance()->imager->transformImage($image, $main_img_transform);
                        } catch (Throwable $e) {
                            // report and send email if not in DEV environment
                            ServiceLocator::getInstance()->error->reportBackendException($e, !Craft::$app->getConfig()->getGeneral()->devMode);
                            $transformed = null;
                        }
                    } else { // not transforming, use the existing Asset Url
                        $transformed = $image;
                    }

                    if ($transformed) {
                        $lines[] = '<image:image>';
                        $lines[] = '<image:loc>';
                        $lines[] = htmlspecialchars($transformed->getUrl(), ENT_XML1, 'UTF-8');
                        $lines[] = '</image:loc>';
                        if (!empty($image->imageCaption)) {
                            $lines[] = '<image:caption>';
                            $lines[] = htmlspecialchars($image->imageCaption, ENT_XML1, 'UTF-8');
                            $lines[] = '</image:caption>';
                        }
                        $lines[] = '</image:image>';
                        $count++;
                        unset($image, $url->image_field[$idx], $transformed); // free some memory
                    }

                    if ($max_count !== null && $max_count > -1 && $count >= $max_count) {
                        break;
                    }
                }
            }

            $lines[] = '</url>';

            $this->processed_items++;

            if ($this->progress_callback && $this->processed_items % 10 === 0) {// update every 10 items
                call_user_func($this->progress_callback, $total_items, $this->processed_items);
            }
        }

        // reset the ordering cache
        $this->model->clearQueryOrderingIds();

        $lines[] = '</urlset>';

        // Add a generated date/time
        $lines[] = '<!-- Generated: ' . $now->format(DateTimeInterface::W3C) . ' -->';

        $this->model->xml = implode("\n", $lines);
        if ($this->config->use_cache) {
            $this->setCachedXML($this->model->xml);
        }

        if ($this->progress_callback) { // indicate that we are done!
            call_user_func($this->progress_callback, $total_items, $total_items);
        }

        return $lines;
    }

    /**
     * @param string $xml
     * @return void
     */
    private function setCachedXML(string $xml) : void
    {
        $this->cache->set($this->config->cache_key, $xml, self::CACHE_TTL);
    }

    /**
     * @return string
     */
    private function getCachedXML() : string
    {
        if ($this->cache->exists($this->config->cache_key)) {
            return $this->cache->get($this->config->cache_key);
        }
        return '';
    }

    /**
     * So we only generate a limited number of sitemaps at a time (due to server resources) we need to know which
     * sitemaps are currently being generated. This sets the current sitemap's state (stored in Redis cache) to
     * generating/true or not generating/false.
     * @param bool $state
     * @return void
     */
    public function setSitemapGenerating(bool $state) : void
    {
        $currently_generating = $this->cache->get(self::SITEMAP_GENERATING_CACHE_KEY);
        if (!is_array($currently_generating)) {
            $currently_generating = [];
        }
        $key = SitemapHelper::sitemapFilename($this->config->getName(), $this->config->start, $this->config->end);
        $currently_generating[$key] = $state;
        $this->cache->set(self::SITEMAP_GENERATING_CACHE_KEY, $currently_generating, self::CACHE_TTL);
    }

    /**
     * Determines if the current sitemap is currently being generated.
     * @return bool
     */
    public function isSiteMapGenerating() : bool
    {
        $currently_generating = $this->cache->get(self::SITEMAP_GENERATING_CACHE_KEY);
        if (!is_array($currently_generating)) {
            return false;
        }
        $key = SitemapHelper::sitemapFilename($this->config->getName(), $this->config->start, $this->config->end);
        return $currently_generating[$key] ?? false;
    }

    /**
     * Wrapped in a function, so it only tries to generate one sitemap per identifier at a time.
     */
    public function maybeAddQueueJob() : void
    {
        if ($this->isSiteMapGenerating()) {
            return;
        }
        $this->setSitemapGenerating(true);
        $job = new XMLSitemap($this->config);
        Craft::$app->getQueue()->push($job);
    }
}
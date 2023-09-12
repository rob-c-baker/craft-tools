<?php declare(strict_types=1);

namespace alanrogers\tools\services\sitemap;

use alanrogers\arimager\ARImager;
use alanrogers\arimager\models\TransformedImageInterface;
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

class SitemapGenerator
{
    public const CACHE_KEY_PREFIX = 'xml-sitemap-';

    /**
     * Number of seconds until cache expires
     */
    private const CACHE_TTL = 172800; // 172800 == 2 days in seconds

    /**
     * @var XMLSitemap|null
     */
    private ?XMLSitemap $model;

    /**
     * @var SitemapConfig
     */
    private SitemapConfig $config;

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
     */
    public function __construct(SitemapConfig $config)
    {
        $this->config = $config;

        if (!$this->config->name) {
            throw new InvalidArgumentException('You must pass an identifier string in the "identifier" key of the config array.');
        }

        $this->model = new $this->config->model_class($this->config);

        $this->config->cache_key = self::getCacheKey($this->config->name);
    }

    /**
     * @param string $identifier
     * @return string
     */
    public static function getCacheKey(string $identifier) : string
    {
        return self::CACHE_KEY_PREFIX . $identifier;
    }

    /** @noinspection PhpUnused */
    public function getXML() : XMLSitemapModel
    {
        if ($this->config->use_cache) {
            $this->model->xml = $this->getCachedXML();
            if ($this->model->xml) {
                $this->model->generated = true;
                return $this->model;
            }
        }

        if ($this->config->use_queue) {

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
            self::maybeAddQueueJob($this->config);

        } else {

            // set to not use queue, generate straight away:

            try {
                $lines = $this->generate();
                $this->model->generated = true;
            } catch (InvalidConfigException) {
                $lines = [
                    '<?xml version="1.0" encoding="UTF-8"?>',
                    '<?xml-stylesheet type="text/xsl" href="/sitemap-empty.xsl"?>',
                    '<!-- There was an error generating the sitemap. -->',
                    '<urlset>',
                    '</urlset>'
                ];
                $this->model->generated = false;
            }
        }

        $this->model->xml = implode("\n", $lines);

        return $this->model;
    }

    /**
     * @throws InvalidConfigException
     * @return string[] An array of XML lines
     */
    public function generate() : array
    {
        $total_items = $this->model->totalItems();

        /** @noinspection HttpUrlsUsage */
        /** @noinspection XmlUnusedNamespaceDeclaration */
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'
        ];

        if ($total_items === 0) {
            ServiceLocator::getInstance()->error->reportBackEndError('Zero entries found while generating XML sitemap for: ' . $this->config->name, true);
            $lines[] = '</urlset>';
            return $lines;
        }

        $with = [];

        if ($this->progress_callback) {
            call_user_func($this->progress_callback, $total_items, $this->processed_items);
        }

        if (!$this->config->image_field) {
            ServiceLocator::getInstance()->error->reportBackEndError(
                sprintf('No `image_field_handle` supplied for sitemap generation for "%s". Images will not be listed in the sitemap.', $this->config->name)
            );
        } else {
            $with[] = $this->config->image_field;
        }

        $main_img_transform = null;

        if ($this->config->image_transform) {
            $main_img_transform = ARImager::$plugin->transforms::getTransform($this->config->image_transform);
            if (empty($main_img_transform['transform'])) {
                ServiceLocator::getInstance()->error->reportBackEndError(
                    sprintf('Cannot find image transformation "%s" while generating XML sitemap for "%s".', $this->config->image_transform, $this->config->name),
                    true
                );
            }
            $main_img_transform = $main_img_transform['transform'];
        }

        $urls = $this->model->getURLs($with);
        $this->model->filterURLs($urls);

        foreach ($urls as $url) {

            $date_updated = $url->date_updated ?? $url->date_created ?? new DateTime();

            $lines[] = '<url>';

            $lines[] = '<loc>';
            $lines[] = htmlspecialchars($url->url, ENT_XML1, 'UTF-8');
            $lines[] = '</loc>';
            $lines[] = '<lastmod>';
            $lines[] = $date_updated->format(DateTimeInterface::W3C);
            $lines[] = '</lastmod>';

            if ($this->model->includeImages($url->entry)) {

                // limit image count
                $max_count = $this->model->getMaxImageCount($url->entry);

                $count = 0;

                /** @var Asset $image */
                foreach ($url->image_field as $image) {

                    if ($main_img_transform) { // transforming?
                        try {
                            /** @var TransformedImageInterface|null $transformed */
                            $transformed = ARImager::getInstance()->imager->transformImage($image, $main_img_transform);
                        } catch (Throwable $e) {
                            // report and send email if not in DEV environment
                            ServiceLocator::getInstance()->error->reportBackendException($e, getenv('ENVIRONMENT') !== 'dev');
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
                    }

                    if ($max_count > -1 && $count >= $max_count) {
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
        $lines[] = '<!-- Generated: ' . (new DateTime())->format(DateTimeInterface::W3C) . ' -->';

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
        ServiceLocator::getInstance()->cache->set($this->config->cache_key, $xml, self::CACHE_TTL);
    }

    /**
     * @return string
     */
    private function getCachedXML() : string
    {
        if (ServiceLocator::getInstance()->cache->exists($this->config->cache_key)) {
            return ServiceLocator::getInstance()->cache->get($this->config->cache_key);
        }
        return '';
    }

    /**
     * Wrapped in a function, so it only tries to generate one sitemap per identifier at a time.
     * Returns the queue job id already running or the one it just pushed.
     * @param SitemapConfig $config the config to pass to the job and then the XML generator
     * @return int
     */
    public static function maybeAddQueueJob(SitemapConfig $config) : int
    {
        $queue = Craft::$app->getQueue();
        $cache = ServiceLocator::getInstance()->cache;
        $cache_key = 'site-map-generating-' . $config->name;
        $job_id = $cache->get($cache_key);

        if ($job_id === false) {
            $existing_job_id = $cache->get($cache_key);
            if ($existing_job_id) {
                if (is_callable([ $queue, 'release'])) {
                    $queue->release($existing_job_id);
                }
                $cache->delete($cache_key);
            }
            $config->cache_key = $cache_key;
            $job = new XMLSitemap($config);
            $job_id = $queue->push($job);
            $cache->set($cache_key, $job_id, $job->getTtr());
        }

        return (int) $job_id;
    }
}
<?php declare(strict_types=1);

namespace alanrogers\tools\services\sitemap;

use alanrogers\tools\models\sitemaps\XMLSitemap;
use alanrogers\tools\services\ServiceLocator;
use Craft;
use craft\elements\Entry;
use craft\helpers\StringHelper;
use DateTime;
use DateTimeInterface;

class SitemapIndexGenerator
{
    public const string CACHE_KEY = 'xml-sitemap-index';

    /**
     * Number of seconds until cache expires
     */
    private const int CACHE_TTL = 172800; // 172800 == 2 days in seconds

    /**
     * Array containing `SitemapConfig`s to render
     * @var string[]
     */
    private array $sitemap_configs;

    /**
     * Each line of the generated XML
     * @var array
     */
    private array $xml = [
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>'
    ];

    private bool $is_generated = false;

    /**
     * Whether to cache the XML
     * @var bool
     */
    private bool $use_cache;

    /**
     * @param SitemapConfig[] $sitemap_configs
     */
    public function __construct(array $sitemap_configs, bool $use_cache=true)
    {
        $this->sitemap_configs = $sitemap_configs;
        $this->use_cache = $use_cache;
    }

    /**
     * @throws SitemapException
     */
    private function generateXML() : void
    {
        /** @noinspection HttpUrlsUsage */
        $this->xml[] = '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $base_url = $_SERVER['SITE_URL'] . '/sitemaps/';
        $totals = [];

        foreach ($this->sitemap_configs as $idx => $config) {

            /** @var XMLSitemap $model */
            $model = new $config->model_class($config);

            if ($config->type === SitemapType::SECTION) {

                $section_handle = StringHelper::camelCase($config->name);
                $section = Craft::$app->getSections()->getSectionByHandle($section_handle);

                if (!$section) {
                    throw new SitemapException(sprintf('Section "%s" does not exist - cannot create sitemap index', $section_handle));
                }
            }

            $modified = $model->getIndexModifiedDate();
            $totals[$config->name] = $model->totalItems();

            if ($totals[$config->name] > SitemapConfig::MAX_SIZE) {
                // we need to split this sitemap
                $number_of_sitemaps = ceil($totals[$config->name] / SitemapConfig::MAX_SIZE);
                for ($i = 1; $i <= $number_of_sitemaps; $i++) {
                    $start = ($i - 1) * SitemapConfig::MAX_SIZE + 1;
                    $end = min($i * SitemapConfig::MAX_SIZE, $totals[$config->name]);
                    $url = $base_url . $config->name . '-' . $start . '-' . $end . '.xml';
                    $this->addSitemapToXML($url, $modified);
                }
            } else {
                $url = $base_url . $config->name . '.xml';
                $this->addSitemapToXML($url, $modified);
            }
        }

        $this->xml[] = '</sitemapindex>';
        $this->is_generated = true;

        if ($this->use_cache) {
            ServiceLocator::getInstance()->cache->set(self::CACHE_KEY, $this->xml, self::CACHE_TTL);
        }
    }

    private function addSitemapToXML(string $url, ?DateTime $modified=null) : void
    {
        $this->xml[] = '<sitemap>';
        $this->xml[] = '<loc>' . htmlspecialchars($url, ENT_XML1, 'UTF-8') . '</loc>';
        if ($modified) {
            $this->xml[] = '<lastmod>' . htmlspecialchars($modified->format(DateTimeInterface::W3C), ENT_XML1, 'UTF-8') . '</lastmod>';
        }
        $this->xml[] = '</sitemap>';
    }

    /**
     * Returns the XML for the sitemap index
     * @return string
     * @throws SitemapException
     */
    public function getXML() : string
    {
        if (!$this->is_generated) {
            if ($this->use_cache) {
                if (ServiceLocator::getInstance()->cache->exists(self::CACHE_KEY)) {
                    $this->xml = ServiceLocator::getInstance()->cache->get(self::CACHE_KEY);
                    $this->is_generated = true;
                } else {
                    $this->generateXML();
                }
            } else {
                $this->generateXML();
            }
        }
        return implode("\n", $this->xml);
    }

    /**
     * Gets the date for when the last edit was made to any entry in the specified section
     * @param string $section_handle
     * @return DateTime|null
     */
    private function getSectionModifiedDate(string $section_handle) : ?DateTime
    {
        $entry = Entry::find()
            ->section($section_handle)
            ->withStructure(false)
            ->structureId()
            ->orderBy('dateUpdated DESC')
            ->limit(1)
            ->one();

        return $entry?->dateUpdated;
    }
}
<?php declare(strict_types=1);

namespace alanrogers\tools\models\sitemaps;

use alanrogers\tools\services\ServiceLocator;
use alanrogers\tools\services\sitemap\SitemapConfig;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\helpers\StringHelper;
use DateTime;
use yii\base\Model;

class XMLSitemap extends Model
{
    const DEFAULT_MAX_IMAGE_COUNT = 5;

    /**
     * @var SitemapConfig 
     */
    public SitemapConfig $config;

    /**
     * The XML itself
     * @var string
     */
    public string $xml = '';

    /**
     * Whether a generated version was detected
     * @var bool
     */
    public bool $generated = false;

    /**
     * @param SitemapConfig $config
     */
    public function __construct(SitemapConfig $config)
    {
        $this->config = $config;
        parent::__construct();
    }

    /**
     * Loads an array of entry ids with our desired ordering applied.
     * This should be done once at the beginning of a sitemap generation so that if the database order changes
     * during generation we still get a sane sitemap with no entries missed out or duplicated.
     * @return array
     */
    public function getQueryOrderingIds() : array
    {
        $cache_key = 'sitemap_query_order_' . $this->config->name;
        if (ServiceLocator::getInstance()->cache->exists($cache_key)) {
            return ServiceLocator::getInstance()->cache->get($cache_key);
        }
        $ids = $this->_entriesQuery()->orderBy('dateUpdated DESC')->ids();
        // cached for a day - should protect a bit against un-spotted errors preventing the call to `$this->clearQueryOrderingIds()`
        ServiceLocator::getInstance()->cache->set($cache_key, $ids, 86400);
        return $ids;
    }

    /**
     * Clears the previously loaded and cached ids defining the ordering. @see $this->getQueryOrderingIds()
     * MUST be called before the job finishes or an error occurs.
     * @return void
     */
    public function clearQueryOrderingIds() : void
    {
        ServiceLocator::getInstance()->cache->delete('sitemap_query_order_' . $this->config->name);
    }

    /**
     * @return int
     */
    public function totalItems() : int
    {
        return (int) $this->loadEntriesQuery()->count();
    }

    /**
     * Gets the URLs for the generator
     * @param array $with
     * @return SitemapURL[]
     */
    public function getURLs(array $with=[]) : array
    {
        $batch = [];
        foreach ($this->loadEntriesQuery($with)->all() as $item) {
            $batch[] = new SitemapURL([
                'entry' => $item,
                'image_field' => $this->config->image_field ? $item->{$this->config->image_field} : null,
                'date_updated' => $item->dateUpdated,
                'date_created' => $item->dateCreated,
                'url' => $item->getUrl()
            ]);
        }
        return $batch;
    }

    /**
     * @param array $with
     * @return EntryQuery
     */
    protected function loadEntriesQuery(array $with=[]) : EntryQuery
    {
        $query = $this->_entriesQuery()
            ->id($this->getQueryOrderingIds())
            ->fixedOrder();

        if ($with) {
            $query->with($with);
        }

        return $query;
    }



    /**
     * Whether to render links to images on the sitemap for this entry
     * @param Entry $entry
     * @return bool
     */
    public function addImagesToSiteMap(Entry $entry) : bool
    {
        if (empty($entry->seoOptions)) {
            return true;
        }
        /** @var MetaBundle $seo_options */
        $seo_options = $entry->seoOptions;
        return $seo_options->metaSitemapVars->sitemapAssets === null ? true : $seo_options->metaSitemapVars->sitemapAssets;
    }

    /**
     * Filter an array of entries by some criteria optionally defined in extended classes
     * @param SitemapURL[] $urls
     */
    public function filterURLs(array &$urls) : void
    {
        foreach ($urls as $idx => $url) {
            if ($url->entry && !SitemapHelper::isEntryAllowedOnSiteMap($url->entry)) {
                unset($urls[$idx]);
            }
        }
    }

    /**
     * Whether to include images , optionally considers a passed in entry
     * @param Entry|null $entry
     * @return bool
     */
    public function includeImages(?Entry $entry) : bool
    {
        return $entry && $this->addImagesToSiteMap($entry);
    }

    /**
     * The maximum number of images to put on the sitemap (for each entry)
     * -1 means no limit.
     * @param Entry|null $entry
     * @return int
     */
    public function getMaxImageCount(?Entry $entry) : int
    {
        return self::DEFAULT_MAX_IMAGE_COUNT;
    }

    /**
     * @return DateTime|null
     */
    public function getIndexModifiedDate() : ?DateTime
    {
        $entry = Entry::find()
            ->section(StringHelper::camelCase($this->config->name))
            ->withStructure(false)
            ->structureId()
            ->orderBy('dateUpdated DESC')
            ->limit(1)
            ->one();
        return $entry?->dateUpdated;
    }

    /**
     * @return EntryQuery
     */
    protected function _entriesQuery() : EntryQuery
    {
        return Entry::find()
            ->section(StringHelper::camelCase($this->config->name))
            ->withStructure(false);
    }
}
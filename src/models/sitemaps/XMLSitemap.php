<?php declare(strict_types=1);

namespace alanrogers\tools\models\sitemaps;

use alanrogers\tools\helpers\SitemapHelper;
use alanrogers\tools\services\ServiceLocator;
use alanrogers\tools\services\sitemap\SitemapConfig;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\helpers\StringHelper;
use DateTime;
use nystudio107\seomatic\models\MetaBundle;
use yii\base\Model;
use yii\db\Expression;

/**
 * Model based on an XML sitemap containing entries.
 */
class XMLSitemap extends Model
{
    const int DEFAULT_MAX_IMAGE_COUNT = 5;

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
        $ids = $this->_elementQuery()->orderBy('dateUpdated DESC')->ids();
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
     * @param int|null $start
     * @param int|null $end
     * @return int
     */
    public function totalItems(?int $start=null, ?int $end=null) : int
    {
        $query = $this->loadElementQuery();
        if ($start !== null) {
            $query->offset($start > 0 ? $start - 1 : 0);
        }
        if ($end !== null) {
            $query->limit($end);
        }
        return (int) $query->count();
    }

    /**
     * Gets the URLs for the generator
     * @param array $with
     * @param int|null $start
     * @param int|null $end
     * @return SitemapURL[]
     */
    public function getURLs(array $with=[], ?int $start=null, ?int $end=null) : array
    {
        $batch = [];
        $query = $this->loadElementQuery($with);
        if ($start !== null) {
            $query->offset($start > 0 ? $start - 1 : 0);
        }
        if ($end !== null) {
            $query->limit($end);
        }
        foreach ($query->all() as $item) {
            $batch[] = new SitemapURL([
                'element' => $item,
                'image_field' => $this->config->image_field ? $item->{$this->config->image_field} : null,
                'date_updated' => $item->dateUpdated,
                'date_created' => $item->dateCreated,
                'url' => $this->getElementURL($item)
            ]);
        }
        return $batch;
    }

    protected function getElementURL(ElementInterface $element) : string
    {
        if ($this->config->element_url !== null) {
            return ($this->config->element_url)($element);
        }
        return $element->getUrl();
    }

    /**
     * @param array $with
     * @return ElementQuery
     */
    protected function loadElementQuery(array $with=[]) : ElementQuery
    {
        $query = $this->_elementQuery()
            ->id($this->getQueryOrderingIds())
            ->fixedOrder();

        if ($with) {
            $query->with($with);
        }

        return $query;
    }

    /**
     * Whether to render links to images on the sitemap for this entry
     * @param Element $element
     * @return bool
     */
    public function addImagesToSiteMap(Element $element) : bool
    {
        $seomatic_field_handle = SitemapConfig::getSEOMaticFieldHandle();
        if (empty($element->$seomatic_field_handle)) {
            return true;
        }
        /** @var MetaBundle $seo_options */
        $seo_options = $element->$seomatic_field_handle;
        return $seo_options->metaSitemapVars->sitemapAssets === null || (bool) $seo_options->metaSitemapVars->sitemapAssets;
    }

    /**
     * Filter an array of entries by some criteria optionally defined in extended classes
     * @param SitemapURL[] $urls
     */
    public function filterURLs(array &$urls) : void
    {
        foreach ($urls as $idx => $url) {
            if ($url->element && !SitemapHelper::isElementAllowedOnSiteMap($url->element)) {
                unset($urls[$idx]);
            }
        }
    }

    /**
     * Whether to include images , optionally considers a passed in entry
     * @param SitemapURL $url
     * @return bool
     */
    public function includeImages(SitemapURL $url) : bool
    {
        return $url->element && $this->addImagesToSiteMap($url->element);
    }

    /**
     * The maximum number of images to put on the sitemap (for each entry / category / product / element)
     * -1 means no limit.
     * @param SitemapURL $url
     * @return int|null `null` for no limit
     */
    public function getMaxImageCount(SitemapURL $url) : ?int
    {
        if ($this->config->max_image_count !== null) {
            return ($this->config->max_image_count)($url);
        }
        return self::DEFAULT_MAX_IMAGE_COUNT;
    }

    /**
     * @param int|null $from
     * @param int|null $to
     * @return DateTime|null
     */
    public function getIndexModifiedDate(?int $from=null, ?int $to=null) : ?DateTime
    {
        if ($this->config->index_modified_date !== null) {
            return ($this->config->index_modified_date)($this, $from, $to);
        }
        $query = $this->_elementQuery()
            ->select([ 'dateUpdated' ])
            ->structureId()
            ->withStructure(false)
            ->orderBy('dateUpdated DESC');
        if ($from !== null) {
            $query->offset($from > 0 ? $from - 1 : 0);
        }
        if ($to !== null) {
            $query->limit($to);
        }

        $max_query = (new Query())
            ->select(new Expression('MAX(dateUpdated) as dateUpdated'))
            ->from($query);

        $row = $max_query->one();
        return DateTime::createFromFormat('Y-m-d H:i:s', $row['dateUpdated']);
    }

    /**
     * By default, returns an entries query, but if an element query is in the config, it will use that.
     * @return ElementQuery|EntryQuery
     */
    protected function _elementQuery() : ElementQuery|EntryQuery
    {
        if ($this->config->element_query !== null) {
            return ($this->config->element_query)($this);
        }
        return Entry::find()
            ->section(StringHelper::camelCase($this->config->name))
            ->withStructure(false);
    }
}
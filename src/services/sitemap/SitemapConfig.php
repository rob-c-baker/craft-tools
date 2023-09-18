<?php declare(strict_types=1);

namespace alanrogers\tools\services\sitemap;

use alanrogers\tools\models\sitemaps\SitemapURL;
use alanrogers\tools\models\sitemaps\XMLSitemap;
use alanrogers\tools\services\ServiceLocator;
use Closure;
use craft\elements\db\ElementQuery;
use craft\helpers\ArrayHelper;
use DateTime;
use yii\base\Component;

class SitemapConfig extends Component
{
    /**
     * Cache the whole sitemap config here:
     * @var array|null
     */
    private static ?array $sitemap_config = null;

    /**
     * The name / identifier of the sitemap as it appears in the sitemap index
     * @var string
     */
    public string $name;

    /**
     * The FQ class name of the sitemap model class to use
     * @var string
     */
    public string $model_class;

    /**
     * @var SitemapType
     */
    public SitemapType $type;

    /**
     * The handle of the field containing images
     * @var string|null
     */
    public ?string $image_field = null;

    /**
     * The name of the image transform to use
     * @var string|null
     */
    public ?string $image_transform = null;

    /**
     * @var bool
     */
    public bool $use_queue = true;

    /**
     * @var bool
     */
    public bool $use_cache = true;

    /**
     * A callback to override the element query used to fetch elements for XMLSitemapURLs.
     * @var (callable(XMLSiteMap): ElementQuery)|null
     */
    public $element_query = null;

    /**
     * A callback to override the way a modified date for a sitemap index is established
     * @var (callable(XMLSiteMap): DateTime)|null
     */
    public $index_modified_date = null;

    /**
     * A callback to override the way the maximum image count for a Sitemap URL is established.
     * @var (callable(SitemapURL): int|null)|null
     */
    public $max_image_count = null;

    /**
     * @var string
     */
    public string $cache_key = 'xml-sitemap-';

    /**
     * @var Closure|null
     */
    public ?Closure $progress_callback = null;

    /**
     * So we can use Closures / callbacks - that cannot be serialised
     * @return array
     */
    public function __serialize()
    {
        return [
            'name' => $this->name,
            'model_class' => $this->model_class,
            'type' => $this->type,
            'image_field' => $this->image_field,
            'image_transform' => $this->image_transform,
            'use_queue' => $this->use_queue,
            'use_cache' => $this->use_cache,
            'cache_key' => $this->cache_key
        ];
    }

    public function __unserialize(array $data)
    {
        $this->name            = $data['name'];
        $this->model_class     = $data['model_class'];
        $this->type            = $data['type'];
        $this->image_field     = $data['image_field'];
        $this->image_transform = $data['image_transform'];
        $this->use_queue       = $data['use_queue'];
        $this->use_cache       = $data['use_cache'];
        $this->cache_key       = $data['cache_key'];

        // add the callbacks back in:
        $config = self::getConfig($this->name);
        $this->element_query = $config->element_query;
        $this->index_modified_date = $config->index_modified_date;
        $this->max_image_count = $config->max_image_count;
    }

    private static function loadAllConfig() : void
    {
        self::$sitemap_config = ServiceLocator::getInstance()->config->getAllItems('sitemaps');
    }

    /**
     * @return array<SitemapConfig>
     */
    public static function getAllConfigs() : array
    {
        $configs = [];
        if (self::$sitemap_config === null) {
            self::loadAllConfig();
        }
        foreach (self::$sitemap_config['sitemaps'] ?? [] as $name => $config) {
            $config['name'] = $name;
            $configs[] = new SitemapConfig($config);
        }
        return $configs;
    }

    /**
     * @param string $name
     * @return SitemapConfig|null
     */
    public static function getConfig(string $name) : ?SitemapConfig
    {
        $sitemap_configs = self::getAllConfigs();
        $sitemap_config = ArrayHelper::firstWhere($sitemap_configs, 'name', $name);
        return $sitemap_config ?: null;
    }

    public static function isEnabled() : bool
    {
        if (self::$sitemap_config === null) {
            self::loadAllConfig();
        }
        return !self::$sitemap_config ? false : (self::$sitemap_config['enabled'] ?? false);
    }

    public static function isCacheEnabled() : bool
    {
        if (self::$sitemap_config === null) {
            self::loadAllConfig();
        }
        return !self::$sitemap_config ? false : (self::$sitemap_config['use_cache'] ?? false);
    }

    public static function getSEOMaticFieldHandle() : string
    {
        if (self::$sitemap_config === null) {
            self::loadAllConfig();
        }
        return !self::$sitemap_config ? 'seoOptions' : (self::$sitemap_config['seomatic_field_handle'] ?? 'seoOptions');
    }
}
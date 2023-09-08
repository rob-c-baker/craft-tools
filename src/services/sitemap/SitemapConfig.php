<?php declare(strict_types=1);

namespace alanrogers\tools\services\sitemap;

use alanrogers\tools\services\ServiceLocator;
use Closure;
use craft\helpers\ArrayHelper;
use yii\base\Component;

class SitemapConfig extends Component
{
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
     * @var string
     */
    public string $cache_key = 'xml-sitemap-';

    /**
     * @var Closure|null
     */
    public ?Closure $progress_callback = null;

    /**
     * @return array<SitemapConfig>
     */
    public static function getAllConfigs() : array
    {
        $configs = [];
        $sitemap_config = ServiceLocator::getInstance()->config->getAllItems('sitemaps');
        foreach ($sitemap_config as $name => $config) {
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
}
<?php declare(strict_types=1);

namespace alanrogers\tools\models;

use alanrogers\tools\models\seo\Links;
use alanrogers\tools\models\seo\OG;
use alanrogers\tools\models\seo\Meta;
use alanrogers\tools\models\seo\Robots;
use alanrogers\tools\models\seo\Sitemap;
use alanrogers\tools\models\seo\Twitter;
use alanrogers\tools\services\Config;
use craft\base\ElementInterface;
use craft\base\Model;
use Override;

/**
 * Represents a set of SEO fields for an item.
 */
class SEOFieldModel extends Model
{
    public ?string $title = null;

    public ?string $site_name = null;

    public ?string $canonical = null;

    /**
     * Items that belong in a standard <meta> tag.
     * @var Meta
     */
    public Meta $meta;

    /**
     * Items that are rendered as <link> tags.
     * @var Links
     */
    public Links $links;

    /**
     * Controlling the content of the `robots` meta tag.
     * @var Robots
     */
    public Robots $robots;

    /**
     * Items that belong in a `twitter:` namespaced <meta> tag.
     * @var Twitter
     */
    public Twitter $twitter;

    /**
     * Items that belong in a `og:` (OpenGraph)namespaced <meta> tag.
     * @var OG
     */
    public OG $og;

    /**
     * Controlling appearance in the sitemap
     * @var Sitemap
     */
    public Sitemap $sitemap;

    /**
     * The associated element, if there is one.
     * @var ElementInterface|null
     */
    private ?ElementInterface $element = null;

    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->createModels($config);
        $this->loadConfigs();
        $this->loadDefaults();
    }

    private function createModels(array $data): void
    {
        // Note if we don't fall into any of the below conditions, it means the call to the constructor with the
        // config already had instances with the right keys.

        if (!isset($data['links'])) {
            $this->links = new Links($this);
        } elseif (!($data['links'] instanceof Links)) {
            $this->links = new Links($this, $data['links']);
        }

        if (!isset($data['meta'])) {
            $this->meta = new Meta($this);
        } elseif (!($data['meta'] instanceof Meta)) {
            $this->meta = new Meta($this, $data['meta']);
        }

        if (!isset($data['robots'])) {
            $this->robots = new Robots($this);
        } elseif (!($data['robots'] instanceof Robots)) {
            $this->robots = new Robots($this, $data['robots']);
        }

        if (!isset($data['twitter'])) {
            $this->twitter = new Twitter($this);
        } elseif (!($data['twitter'] instanceof Twitter)) {
            $this->twitter = new Twitter($this, $data['twitter']);
        }

        if (!isset($data['og'])) {
            $this->og = new OG($this);
        } elseif (!($data['facebook'] instanceof OG)) {
            $this->og = new OG($this, $data['og']);
        }

        if (!isset($data['sitemap'])) {
            $this->sitemap = new Sitemap($this);
        } elseif (!($data['sitemap'] instanceof Sitemap)) {
            $this->sitemap = new Sitemap($this, $data['sitemap']);
        }
    }

    private function loadConfigs(): void
    {
        $this->default_config = new Config([
            'base_path' => __DIR__ . '/../config/',
            'default_config_name' => 'seo-defaults'
        ]);
    }

    private function loadDefaults(): void
    {

    }

    #[Override]
    public function setAttributes($values, $safeOnly = true): void
    {
        foreach ($this->attributes() as $key) {
            if (isset($values[$key])) {
                if ($this->$key instanceof Model) {
                    $this->$key->setAttributes($values[$key], $safeOnly);
                    unset($values[$key]); // don't want this to be messed up below
                }
            }
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function setElement(?ElementInterface $element): SEOFieldModel
    {
        $this->element = $element;
        return $this;
    }

    public function rules() : array
    {

    }
}
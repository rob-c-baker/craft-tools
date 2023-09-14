<?php declare(strict_types=1);

namespace alanrogers\tools\models\sitemaps;

use craft\base\Element;
use DateTime;
use yii\base\Model;

/**
 * Represents a single XML sitemap URL
 */
class SitemapURL extends Model
{
    /**
     * @var DateTime|null
     */
    public ?DateTime $date_created = null;

    /**
     * @var DateTime|null
     */
    public ?DateTime $date_updated = null;

    /**
     * @var string
     */
    public string $url;

    /**
     * @var mixed|null
     */
    public mixed $image_field = null;

    /**
     * For URLs that have an associated entry, this is it.
     * @var Element|null
     */
    public ?Element $element = null;
}
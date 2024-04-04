<?php

namespace alanrogers\tools\helpers;

use alanrogers\tools\services\sitemap\SitemapConfig;
use alanrogers\tools\services\sitemap\SitemapException;
use Craft;
use craft\base\Element;
use nystudio107\seomatic\helpers\Sitemap;
use nystudio107\seomatic\Seomatic;

class SitemapHelper extends Sitemap implements HelperInterface
{
    /**
     * Whether an entry is allowed / is enabled to be in the sitemap.
     * Note: the content of this method (and this class and how it subclasses Sitemap) was arrived at through communication with
     * SEOMatic dev. Please take extra care and be very careful if changing any of this.
     * @param Element $element
     * @param int|null $site_id
     * @return bool
     */
    public static function isElementAllowedOnSiteMap(Element $element, ?int $site_id=null) : bool
    {
        $seomatic_field_handle = SitemapConfig::getSEOMaticFieldHandle();
        if (empty($element->$seomatic_field_handle)) {
            return true;
        }
        $site_id = $site_id ?? Craft::$app->getSites()->currentSite->id;
        $meta_bundle = Seomatic::$plugin->metaBundles->getGlobalMetaBundle($site_id);
        self::combineFieldSettings($element, $meta_bundle);
        return $meta_bundle->metaSitemapVars->sitemapUrls;
    }

    /**
     * @throws SitemapException
     */
    public static function getChunkRangesFromIdentifier(string $identifier) : array
    {
        // No chunking by default
        $start = null;
        $end = null;

        // spot filenames that have ranges in - so we can render just the right bits
        if (preg_match('/([a-z0-9\-_]+)-([0-9]+)-([0-9]+)$/', $identifier, $matches)) {
            $identifier = $matches[1];
            $start = (int) $matches[2];
            $end = (int) $matches[3];
            if ($start > $end) {
                throw new SitemapException('Start cannot be greater than end.');
            }
            if ($end - ($start - 1) > SitemapConfig::MAX_SIZE) {
                throw new SitemapException('Difference between start and end cannot be greater than ' . SitemapConfig::MAX_SIZE);
            }
        }

        return [ $start, $end, $identifier ];
    }

    public static function sitemapFilename(string $config_name, ?int $start = null, ?int $end = null) : string
    {
        return implode('-', array_filter([
            $config_name,
            $start,
            $end
        ])) . '.xml';
    }
}
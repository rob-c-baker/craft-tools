<?php

namespace alanrogers\tools\helpers;

use alanrogers\tools\helpers\HelperInterface;
use Craft;
use craft\elements\Entry;
use nystudio107\seomatic\helpers\Sitemap;
use nystudio107\seomatic\Seomatic;

class SitemapHelper extends Sitemap implements HelperInterface
{
    /**
     * Whether an entry is allowed / is enabled to be in the sitemap.
     * Note: the content of this method (and this class and how it subclasses Sitemap) was arrived at through communication with
     * SEOMatic dev. Please take extra care and be very careful if changing any of this.
     * @param Entry $entry
     * @param int|null $site_id
     * @return bool
     */
    public static function isEntryAllowedOnSiteMap(Entry $entry, ?int $site_id=null) : bool
    {
        if (empty($entry->seoOptions)) {
            return true;
        }
        $site_id = $site_id ?? Craft::$app->getSites()->currentSite->id;
        $meta_bundle = Seomatic::$plugin->metaBundles->getGlobalMetaBundle($site_id);
        self::combineFieldSettings($entry, $meta_bundle);
        return $meta_bundle->metaSitemapVars->sitemapUrls;
    }
}
<?php

use alanrogers\tools\models\sitemaps\SitemapURL;
use alanrogers\tools\models\sitemaps\XMLSitemap;
use alanrogers\tools\services\sitemap\SitemapType;
use craft\elements\db\ElementQuery;
use craft\elements\Entry;

return [

    // Whether to produce sitemaps
    'enabled' => true,

    // Whether to use the cache - helpful during debugging
    'use_cache' => true,

    // The handle for the seomatic field on the element
    'seomatic_field_handle' => 'seoOptions',

    'sitemaps' => [
        '[section-handle]' => [
            'model_class' => XMLSitemap::class,
            'type' => SitemapType::SECTION,
            'image_field' => '[imageFieldHandle]',
            'image_transform' => '[named_image_transform]',
        ],
        '...' => [
            // The following an optional way of overriding the element query
            'element_query' => function(XMLSitemap $model): ElementQuery {
                return new ElementQuery(Entry::class);
            },
            // The following an optional way of overriding the modified date for a sitemap index
            'index_modified_date' => function(XMLSitemap $model): DateTime {
                return new DateTime();
            },
            // The following an optional way of overriding the maximum image count for a sitemap URL
            'max_image_count' => function(SitemapURL $url): ?int {
                return 5;
            }
        ],
    ]
];
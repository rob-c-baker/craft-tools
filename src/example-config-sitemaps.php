<?php

use alanrogers\tools\models\sitemaps\XMLSitemap;
use alanrogers\tools\services\sitemap\SitemapType;

return [
    '[section-handle]' => [
        'model_class' => XMLSitemap::class,
        'type' => SitemapType::SECTION,
        'image_field' => '[imageFieldHandle]',
        'image_transform' => '[named_image_transform]',
    ],
    '...' => [

    ],
];
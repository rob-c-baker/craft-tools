<?php declare(strict_types=1);

namespace alanrogers\tools\services\sitemap;

enum SitemapType: string
{
    case SECTION = 'section';
    case CUSTOM = 'custom';
}
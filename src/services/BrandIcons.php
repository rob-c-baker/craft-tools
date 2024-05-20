<?php

namespace alanrogers\tools\services;

use Craft;
use Exception;
use RuntimeException;

class BrandIcons
{
    private static ?IconLibrary $icon_library = null;

    /**
     * @param string $name
     * @param array $attributes
     * @return string
     * @throws RuntimeException
     */
    public static function inlineIconSVG(string $name, array $attributes=[]) : string
    {
        if (!self::$icon_library) {
            self::$icon_library = IconLibrary::factory(
                'brand',
                Craft::$app->vendorPath,
                'simple-icons/simple-icons/icons'
            );
        }
        return self::$icon_library->getIconData($name, IconLibrary::TYPE_SVG, $attributes);
    }
}
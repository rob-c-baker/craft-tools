<?php declare(strict_types=1);

namespace alanrogers\tools\services;

use Craft;

class BrandIcons
{
    private static ?IconLibrary $icon_library = null;

    /**
     * @param string $name
     * @param array $attributes
     * @param string|null $elements
     * @param string|null $position
     * @return string
     */
    public static function inlineIconSVG(string $name, array $attributes=[], ?string $elements=null, ?string $position=IconLibrary::POSITION_PREPEND) : string
    {
        if (!self::$icon_library) {
            self::$icon_library = IconLibrary::factory(
                'brand',
                Craft::$app->vendorPath,
                'simple-icons/simple-icons/icons'
            );
        }
        // We will usually want to have the current CSS colour applying to the SVG attributes
        $attributes = [
            'fill' => 'currentColor',
            ...$attributes
        ];
        return self::$icon_library->getIcon($name, IconLibrary::TYPE_SVG, $attributes, $elements, $position);
    }
}
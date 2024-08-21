<?php declare(strict_types=1);

namespace alanrogers\tools\services;

use Exception;
use RuntimeException;
use yii\base\Component;

class MaterialDesignIcons extends Component
{
    private static ?IconLibrary $icon_library = null;

    /**
     * Sets path relative to `STATIC_PATH`
     * @param string $path
     * @throws Exception
     */
    public static function setSVGPath(string $path): void
    {
        self::$icon_library = IconLibrary::factory('mdi', getenv('STATIC_PATH'), trim($path, '/'));
    }

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
            throw new RuntimeException('You must set the path to the SVGs with MaterialDesignIcons::setSVGPath($path) before calling this method.');
        }
        return self::$icon_library->getIcon($name, IconLibrary::TYPE_SVG, $attributes, $elements, $position);
    }
}
<?php
declare(strict_types=1);

namespace alanrogers\tools\services;

use DOMDocument;
use DOMElement;
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
     * @return string
     * @throws RuntimeException
     */
    public static function inlineIconSVG(string $name, array $attributes=[]) : string
    {
        if (!self::$icon_library) {
            throw new RuntimeException('You must set the path to the SVGs with MaterialDesignIcons::setSVGPath($path) before calling this method.');
        }
        return self::$icon_library->getIconData($name, IconLibrary::TYPE_SVG, $attributes);
    }
}
<?php

namespace alanrogers\tools\services;

use DOMDocument;
use DOMElement;
use yii\base\Component;

class MaterialDesignIcons extends Component
{
    private static string $svg_path = '';

    /**
     * Remember those that we have already loaded in this array
     * @var array
     */
    private static array $loaded = [];

    /**
     * Sets path relative to WEB_ROOT
     * @param string $path
     */
    public static function setSVGPath(string $path): void
    {
        $path = rtrim(getenv('STATIC_PATH'), '/') . '/'  . trim($path, '/');
        self::$svg_path = realpath($path);
        if (self::$svg_path === false) {
            throw new \RuntimeException('The path supplied does not exist.');
        }
    }

    /**
     * @param string $name
     * @param array $options
     * @return false|string
     */
    public static function inlineIconSVG(string $name, array $options=[])
    {
        if (!self::$svg_path) {
            throw new \RuntimeException('You must set the path to the SVGs with MaterialDesignIcons::setSVGPath($path) before calling this method.');
        }

        $filename = $name . '.svg';
        $path = self::$svg_path . '/' . $filename;

        if (!empty(self::$loaded[$path])) {
            $svg_data = self::$loaded[$path];
        } elseif (is_readable($path)) {
            $svg_data = file_get_contents($path);
            if ($svg_data === false) {
                throw new \RuntimeException(sprintf('Could not read the filename for icon "%s".', $name));
            }
            self::$loaded[$path] = $svg_data;
        } else {
            throw new \RuntimeException(sprintf('The icon "%s" does not exist in the SVG path.', $name));
        }

        if (!empty($options)) { // there are at least some options

            $svg = new DOMDocument('1.0', 'utf-8');
            if (!$svg->loadXML($svg_data)) {
                throw new \RuntimeException(sprintf('Could not load svg DOM for icon "%s".', $name));
            }

            /** @var DOMElement $svg_el */
            $svg_el = $svg->getElementsByTagName('svg')[0] ?? null;

            if (!$svg_el) {
                throw new \RuntimeException(sprintf('Could not find svg element in file for icon "%s".', $name));
            }

            if (isset($options['width'])) {
                $svg_el->setAttribute('width', (int) $options['width']);
                unset($options['width']);
            }

            if (isset($options['height'])) {
                $svg_el->setAttribute('height', (int) $options['height']);
                unset($options['height']);
            }

            if (isset($options['viewBox'])) {
                $svg_el->setAttribute('viewBox', $options['viewBox']);
                unset($options['viewBox']);
            }

            if (isset($options['role'])) {
                $svg_el->setAttribute('role', $options['role']);
                unset($options['role']);
            }

            if (isset($options['transform'])) {
                $svg_el->setAttribute('transform', $options['transform']);
                unset($options['transform']);
            }

            // any options left are other SVG element attributes
            /** @noinspection NotOptimalIfConditionsInspection */
            if (!empty($options)) {
                foreach ($options as $n => $value) {
                    $svg_el->setAttribute($n, $value);
                }
            }

            $svg_data = $svg->saveXML($svg_el);
        }

        return $svg_data;
    }
}
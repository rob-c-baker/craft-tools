<?php
declare(strict_types=1);

namespace alanrogers\tools\services;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use RuntimeException;

class IconLibrary
{
    public const TYPE_SVG = 'svg';
    public const TYPE_PNG = 'png';
    public const TYPE_GIF = 'gif';

    const ALLOWED_TYPES = [
        self::TYPE_SVG,
        self::TYPE_PNG,
        self::TYPE_GIF
    ];

    private static array $instances = [];

    /**
     * @var string
     */
    private string $name;

    /**
     * @var string Absolute path to the icon library directory. No trailing slash.
     */
    private string $path;

    /**
     * Cached icon names and their corresponding file data
     * @var array<string, string>
     */
    private array $loaded = [];

    /**
     * @param string $name
     * @param string $path
     */
    private function __construct(string $name, string $path)
    {
        $this->setPath($path);
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Sets the path to the directory for the current icon library
     * @param string $path The path to the icon directory
     * @return self
     */
    public function setPath(string $path) : self
    {
        $real_path = realpath($path);
        if ($real_path === false) {
            throw new InvalidArgumentException(sprintf('Icon Path: "%s" does not exist.', $path));
        }
        $this->path = $real_path;
        return $this;
    }

    /**
     * Gets the filesystem path to the icon
     * @param string $name
     * @param string $type a TYPE_* class constant
     * @return string|null
     */
    public function getIconPath(string $name, string $type=self::TYPE_SVG) : ?string
    {
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Invalid type supplied: "%s".', $type));
        }

        $filename = $name . '.' . $type;

        if (isset($this->loaded[$filename]['path'])) {
            return $this->loaded[$filename]['path'];
        }

        $path = implode('/', [ $this->path, $filename ]);

        if (!is_readable($path)) {
            throw new InvalidArgumentException(sprintf(
                'File "%s" with type "%s" is not readable at path: "%s".',
                $name,
                $type,
                $path
            ));
        }

        $this->loaded[$filename] = [
            'path' => $path,
            'data' => null
        ];

        return $path;
    }

    /**
     * Gets the contents of the icon file.
     * @param string $name
     * @param string $type a TYPE_* class constant
     * @param array $attributes
     * @return string|null
     */
    public function getIconData(string $name, string $type=self::TYPE_SVG, array $attributes=[]) : ?string
    {
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Invalid type supplied: "%s".', $type));
        }

        $filename = $name . '.' . $type;

        if (isset($this->loaded[$filename]['path'])) {
            $data = $this->loaded[$filename]['path'];
        } else {
            $path = $this->getIconPath($name, $type);
            $data = file_get_contents($path);

            if (isset($this->loaded[$filename])) {
                $this->loaded[$filename]['data'] = $data;
            } else {
                $this->loaded[$filename] = [
                    'path' => $path,
                    'data' => $data
                ];
            }
        }

        if ($type === self::TYPE_SVG && !empty($attributes)) {
            $data = $this->applyXMLAttributes($name, $data, $attributes);
        }

        return $data;
    }

    /**
     * @param string $name
     * @param string $xml_str
     * @param array $attributes
     * @return string
     */
    public function applyXMLAttributes(string $name, string $xml_str, array $attributes=[]) : string
    {
        $svg = new DOMDocument('1.0', 'utf-8');
        if (!$svg->loadXML($xml_str)) {
            throw new RuntimeException(sprintf('Could not load DOM for icon "%s".', $name));
        }

        /** @var DOMElement $svg_el */
        $svg_el = $svg->getElementsByTagName('svg')[0] ?? null;

        if (!$svg_el) {
            throw new RuntimeException(sprintf('Could not find svg element in file for icon "%s".', $name));
        }

        foreach ($attributes as $n => $value) {
            $svg_el->setAttribute($n, $value);
        }

        return $svg->saveXML($svg_el);
    }

    /**
     * Gets a new or existing instance of `IconLibrary` based on `$paths` / `$name`
     * @param string|null $name Name of this library
     * @param string ...$paths A number of paths to join that will result in a icon directory path
     * @return IconLibrary
     */
    public static function factory(?string $name=null, string ...$paths) : IconLibrary
    {
        if ($name === null) {
            $last_path = array_values(array_slice($paths, -1))[0];
            $name = array_values(array_slice(explode('/', $last_path), -1))[0];
        }

        $path = implode('/', $paths);
        $key = $path . '__' . $name;

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new IconLibrary($path, $name);
        }

        return self::$instances[$key];
    }
}
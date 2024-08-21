<?php declare(strict_types=1);

namespace alanrogers\tools\services;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;
use RuntimeException;

class IconLibrary
{
    public const string TYPE_SVG = 'svg';
    public const string TYPE_PNG = 'png';
    public const string TYPE_GIF = 'gif';

    public const string POSITION_APPEND = 'append';
    public const string POSITION_PREPEND = 'prepend';

    const array ALLOWED_TYPES = [
        self::TYPE_SVG,
        self::TYPE_PNG,
        self::TYPE_GIF
    ];

    private static array $instances = [];

    /**
     * Saved `DOMDocument`s for named icons
     * @var array<string, DOMDocument>
     */
    private array $dom_documents = [];

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
     * @param bool $check_path Set false to use in unit testing context so the filesystem is not touched.
     */
    public function __construct(string $name, string $path, bool $check_path=true)
    {
        $this->setPath($path, $check_path);
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
     */
    private function setPath(string $path, bool $check_path) : void
    {
        if ($check_path) {
            $real_path = realpath($path);
            if ($real_path === false) {
                throw new InvalidArgumentException(sprintf('Icon Path: "%s" does not exist.', $path));
            }
            $path = $real_path;
        }

        $this->path = $path;
    }

    /**
     * Gets the filesystem path to the icon
     * @param string|null $name If null, empty string returned
     * @param string $type a TYPE_* class constant
     * @return string|null
     */
    private function getIconPath(?string $name, string $type=self::TYPE_SVG) : ?string
    {
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Invalid type supplied: "%s".', $type));
        }

        if ($name === null) {
            return '';
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
     * Gets the file data for an icon, with any transformations applied.
     * @param string $name The name for an icon with no file extensions
     * @param string $type a TYPE_* class constant
     * @param array $attributes any attributes to set on the document XML node (for TYPE_SVG) - an attribute set to `null` removes the attribute from the markup.
     * @param string|null $elements Any additional XML to inject into the document XML node (for TYPE_SVG)
     * @param string|null $position Either self::POSITION_APPEND or self::POSITION_PREPEND
     * @return string
     */
    public function getIcon(string $name, string $type=self::TYPE_SVG, array $attributes=[], ?string $elements=null, ?string $position=self::POSITION_PREPEND) : string
    {
        $data = null;

        // Only parse the DOM if we need to
        if ($type === self::TYPE_SVG && (!empty($attributes) || $elements !== null)) {

            $dom = $this->getDOM($name);
            /** @var DOMElement $svg */
            $svg = $dom->getElementsByTagName('svg')[0] ?? null;

            if (!$svg) {
                throw new RuntimeException(sprintf('Could not find svg element for icon "%s".', $name));
            }

            if (!empty($attributes)) {
                $this->applyXMLAttributes($svg, $attributes);
            }

            if ($elements !== null) {
                $this->applyXMLElements($svg, $elements, $position);
            }

            $data = $dom->saveXML($svg);
        }

        if ($data === null) {
            $data = $this->getIconData($name, $type);
        }

        return $data;
    }

    /**
     * Gets the contents of the icon file.
     * @param string|null $name If null, empty string returned
     * @param string $type a TYPE_* class constant
     * @return string|null
     */
    private function getIconData(?string $name, string $type=self::TYPE_SVG) : ?string
    {
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(sprintf('Invalid type supplied: "%s".', $type));
        }

        if ($name === null) {
            return '';
        }

        $filename = $name . '.' . $type;

        if (isset($this->loaded[$filename]['data'])) {
            $data = $this->loaded[$filename]['data'];
        } else {
            $path = $this->getIconPath($name, $type);
            $data = file_get_contents($path);
            $this->setLoadedIconData($name, $data, $path, $type);
        }

        return $data;
    }

    /**
     * Sets the loaded icon data in the cache.
     * Can be used for unit testing to inject data without touching the filesystem.
     * @param string $name
     * @param string $data
     * @param string $path
     * @param string $type
     * @return void
     */
    public function setLoadedIconData(string $name, string $data, string $path, string $type=self::TYPE_SVG): void
    {
        $filename = $name . '.' . $type;

        if (isset($this->loaded[$filename])) {
            $this->loaded[$filename]['data'] = $data;
        } else {
            $this->loaded[$filename] = [
                'path' => $path,
                'data' => $data
            ];
        }
    }

    /**
     * @param DOMElement $svg
     * @param array $attributes
     */
    private function applyXMLAttributes(DOMElement $svg, array $attributes=[]): void
    {
        foreach ($attributes as $n => $value) {
            if ($value === null) {
                $svg->removeAttribute($n);
            } else {
                $svg->setAttribute($n, $value);
            }
        }
    }

    /**
     * @param DOMElement $svg The element to inject inside
     * @param string $elements The XML to inject
     * @param string|null $position Either self::POSITION_APPEND or self::POSITION_PREPEND
     * @return void
     */
    private function applyXMLElements(DOMElement $svg, string $elements, ?string $position=self::POSITION_PREPEND): void
    {
        $fragment = $svg->ownerDocument->createDocumentFragment();
        $fragment->appendXML($elements);

        if ($position === self::POSITION_APPEND) {
            $svg->append($fragment);
        } elseif ($position === self::POSITION_PREPEND) {
            $svg->prepend($fragment);
        }
    }

    /**
     * Returns a DOMDocument for the icon which represents the icon on disk. i.e. We clone the instance from the loaded
     * DOM, which is based on the file on disk.
     * @param string $name
     * @return DOMDocument|null
     */
    private function getDOM(string $name): ?DOMDocument
    {
        if (!isset($this->dom_documents[$name])) {
            $this->dom_documents[$name] = new DOMDocument('1.0', 'utf-8');
            $xml_str = $this->getIconData($name);
            if (!$this->dom_documents[$name]->loadXML($xml_str)) {
                throw new RuntimeException(sprintf('Could not load DOM for icon "%s".', $name));
            }
        }
        return clone $this->dom_documents[$name];
    }

    /**
     * Gets a new or existing instance of `IconLibrary` based on `$paths` / `$name`
     * @param string|null $name Name of this library
     * @param string ...$paths A number of paths to join that will result in a icon directory path
     * @return self
     */
    public static function factory(?string $name=null, string ...$paths) : self
    {
        if ($name === null) {
            $last_path = array_values(array_slice($paths, -1))[0];
            $name = array_values(array_slice(explode('/', $last_path), -1))[0];
        }

        $path = implode('/', $paths);
        $key = $path . '__' . $name;

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($name, $path);
        }

        return self::$instances[$key];
    }
}
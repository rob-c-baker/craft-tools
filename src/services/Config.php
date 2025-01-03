<?php declare(strict_types=1);

namespace alanrogers\tools\services;

use craft\helpers\ArrayHelper;
use InvalidArgumentException;
use yii\base\Component;

class Config extends Component
{
    /**
     * @var string|null
     */
    private ?string $base_path;

    /**
     * Complete paths to the config files. Indexed on name.
     * @var array<string, string>
     */
    private array $config_paths = [];

    private string $default_config_name;

    /**
     * @var array<string, mixed>
     */
    private array $config_data = [];

    /**
     * @var array<string, bool>
     */
    private array $loaded = [];

    public function __construct(array $config = [])
    {
        $this->base_path = $config['base_path'] ?? null;
        $this->default_config_name = $config['default_config_name'] ?? 'alan-rogers';
        unset($config['base_path'], $config['default_config_name']);
        parent::__construct($config);
    }

    public function init() : void
    {
        parent::init();

        // this might be set in the inherited constructor (via a unit test?)
        if ($this->base_path === null) {
            /** @noinspection PhpUndefinedConstantInspection */
            $this->base_path = defined('CRAFT_CONFIG_PATH')
                ? CRAFT_CONFIG_PATH . '/'
                : CRAFT_BASE_PATH . '/config/';
        }
    }

    private function ensureConfigPath(string $name, bool $check_file = true): void
    {
        if (!$this->getConfigPath($name)) {
            $path = $this->base_path . $name . '.php';
            if ($check_file && !file_exists($path)) {
                throw new InvalidArgumentException(sprintf('Config "%s" not found.', $name));
            }
            $this->setConfigPath($name, $path);
        }
    }

    public function getConfigPath(string $name): ?string
    {
        return $this->config_paths[$name] ?? null;
    }

    public function setConfigPath(string $config_name, string $path) : static
    {
        $this->config_paths[$config_name] = $path;
        return $this;
    }

    /**
     * Whether this named config (file usually) exists
     * @param string|null $name (matches the filename before the .php prefix)
     * @param bool $check_file
     * @return bool
     */
    public function configExists(?string $name=null, bool $check_file = true) : bool
    {
        try {
            $name_to_check = $name ?? $this->default_config_name;
            $this->ensureConfigPath($name_to_check, $check_file);
            return isset($this->config_paths[$name_to_check]);
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @param string $name (matches the filename before the .php prefix)
     * @throws InvalidArgumentException When the named config is invalid / not found
     */
    private function loadConfig(string $name) : void
    {
        if ($this->configExists($name)) {
            $this->config_data[$name] = require($this->config_paths[$name]);
            $this->loaded[$name] = true;
        }
    }

    /**
     * @param string $name The name of the config entry
     * @param string|null $config_name The name of the config file
     * @return mixed
     * @throws InvalidArgumentException When the named config is invalid / not found
     */
    public function getItem(string $name, ?string $config_name=null) : mixed
    {
        if ($config_name === null) {
            $config_name = $this->default_config_name;
        }
        if (!isset($this->loaded[$config_name]) || !$this->loaded[$config_name]) {
            $this->loadConfig($config_name);
        }
        return $this->config_data[$config_name][$name] ?? null;
    }

    /**
     * Gets items with names in $names in the order identified by $names. If a name does not exist, null will be in
     * that position of the returned array.
     * @param string[] $names
     * @param string|null $config_name
     * @return array
     * @throws InvalidArgumentException When the named config is invalid / not found
     */
    public function getItems(array $names, ?string $config_name=null) : array
    {
        if ($config_name === null) {
            $config_name = $this->default_config_name;
        }
        if (!isset($this->loaded[$config_name]) || !$this->loaded[$config_name]) {
            $this->loadConfig($config_name);
        }
        $items = [];
        foreach ($names as $name) {
            $items[] = $this->config_data[$config_name][$name] ?? null;
        }
        return $items;
    }

    /**
     * Gets the top level array keys and values for a config
     * @param string|null $config_name The name of the config file
     * @return array|null
     * @throws InvalidArgumentException When the named config is invalid / not found
     */
    public function getAllItems(?string $config_name=null) : ?array
    {
        if ($config_name === null) {
            $config_name = $this->default_config_name;
        }
        if (!isset($this->loaded[$config_name]) || !$this->loaded[$config_name]) {
            $this->loadConfig($config_name);
        }
        return $this->config_data[$config_name] ?? null;
    }

    public function hasConfig(string $config_name) : bool
    {
        return isset($this->loaded[$config_name]);
    }

    /**
     * Primarily for injecting a config for testing purposes, sets all loaded config items for specific config name or
     * default if not set.
     * @param array $items
     * @param string|null $config_name
     * @return static
     */
    public function setAllItems(array $items, ?string $config_name=null) : static
    {
        if ($config_name === null) {
            $config_name = $this->default_config_name;
        }
        $this->config_data[$config_name] = $items;
        $this->loaded[$config_name] = true;
        return $this;
    }

    /**
     * Will merge named loaded configs into a new config with the precedence order as specified in `$config_names` named
     * according to `$merged_name`
     * @param array $config_names
     * @param string $merged_name
     * @return $this
     */
    public function mergeNamedConfigs(array $config_names, string $merged_name='merged') : static
    {
        $items = [];
        foreach ($config_names as $config_name) {
            $items = ArrayHelper::merge($items, $this->getAllItems($config_name));
        }
        $this->setAllItems($items, $merged_name);
        return $this;
    }
}
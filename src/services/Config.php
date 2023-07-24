<?php
declare(strict_types=1);

namespace alanrogers\tools\services;

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

    private function ensureConfigPath(string $name): void
    {
        if (!isset($this->config_paths[$name])) {
            $path = $this->base_path . $name . '.php';
            if (!file_exists($path)) {
                throw new InvalidArgumentException(sprintf('Config "%s" not found.', $name));
            }
            $this->config_paths[$name] = $path;
        }
    }

    /**
     * Whether this named config (file usually) exists
     * @param string|null $name (matches the filename before the .php prefix)
     * @return bool
     */
    public function configExists(?string $name=null) : bool
    {
        try {
            $this->ensureConfigPath($name ?? $this->default_config_name);
            return isset($this->config_paths[$name]);
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
}
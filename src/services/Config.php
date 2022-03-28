<?php

namespace alanrogers\tools\services;

use Exception;
use yii\base\Component;

class Config extends Component
{
    /**
     * @var string
     */
    private static string $base_path;

    /**
     * @var array<string, mixed>
     */
    private static array $config_data = [];

    /**
     * @var array<string, bool>
     */
    private static array $loaded = [];

    public function init() : void
    {
        parent::init();

        /** @noinspection PhpUndefinedConstantInspection */
        self::$base_path = defined('CRAFT_CONFIG_PATH')
            ? CRAFT_CONFIG_PATH . '/'
            : CRAFT_BASE_PATH . '/config/';
    }

    /**
     * @param string $name (matches the filename before the .php prefix)
     * @throws Exception When the named config is not found
     */
    private static function loadConfig(string $name) : void
    {
        $path = self::$base_path . $name . '.php';
        if (file_exists($path)) {
            self::$config_data[$name] = require($path);
        } else {
            throw new Exception(sprintf('Config "%s" not found.', $name));
        }
        self::$loaded[$name] = true;
    }

    /**
     * @param string $name The name of the config entry
     * @param string $config_name The name of the config file
     * @return mixed
     * @throws Exception When the named config is not found
     */
    public function getItem(string $name, string $config_name='alan-rogers')
    {
        if (!isset(self::$loaded[$config_name]) || !self::$loaded[$config_name]) {
            self::loadConfig($config_name);
        }
        return self::$config_data[$config_name][$name] ?? null;
    }

    /**
     * Gets items with names in $names in the order identified by $names. If a name does not exist, null will be in
     * that position of the returned array.
     * @param string[] $names
     * @param string $config_name
     * @return array
     * @throws Exception When the named config is not found
     */
    public function getItems(array $names, string  $config_name='alan-rogers') : array
    {
        if (!isset(self::$loaded[$config_name]) || !self::$loaded[$config_name]) {
            self::loadConfig($config_name);
        }
        $items = [];
        foreach ($names as $name) {
            $items[] = self::$config_data[$config_name][$name] ?? null;
        }
        return $items;
    }

    /**
     * Gets the top level array keys and values for a config
     * @param string $config_name The name of the config file
     * @return array|null
     * @throws Exception When the named config is not found
     */
    public function getAllItems(string $config_name='alan-rogers') : ?array
    {
        if (!isset(self::$loaded[$config_name]) || !self::$loaded[$config_name]) {
            self::loadConfig($config_name);
        }
        return self::$config_data[$config_name] ?? null;
    }
}
<?php

namespace alanrogers\tools\services;

use Craft;
use craft\log\FileTarget;
use yii\log\Logger;

class ARLogger
{
    public const DEFAULT_LOG_NAME = 'ar';

    public const LOG_FILE_PATH = '@storage/logs/';

    /**
     * @var array<string, ARLogger>
     */
    private static array $instances = [];

    /**
     * @var string
     */
    private string $name;

    /**
     * ARLogger constructor.
     * @param string $name
     * @param string|null $log_filename defaults to "${name}.log"
     * @param array|null $log_category_patterns defaults to [ $name ]
     */
    public function __construct(string $name, string $log_filename=null, ?array $log_category_patterns=null)
    {
        $this->name = $name;

        if ($log_category_patterns === null) {
            $log_category_patterns = [ $name ];
        }

        if ($log_filename === null) {
            $log_filename = $this->name . '.log';
        }

        // Create a new file target and add the new file target to the log dispatcher
        Craft::getLogger()->dispatcher->targets[] = new FileTarget([
            'logFile' => self::LOG_FILE_PATH . $log_filename,
            'categories' => $log_category_patterns,
            'enableRotation' => false // we assume this is being done by the OS.
        ]);
    }

    /**
     * @param string $name
     * @param array|null $config Optional additional config to set-up log target
     * @return ARLogger
     */
    public static function getInstance(string $name=self::DEFAULT_LOG_NAME, ?array $config=null) : ARLogger
    {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new self($name, $config['logFile'] ?? null, $config['categories'] ?? null);
        }
        return self::$instances[$name];
    }

    /**
     * @param string $message
     * @param string|int $level
     * @param string|null $category
     * @return $this
     */
    public function log(string $message, string $level=Logger::LEVEL_INFO, string $category=null) : ARLogger
    {
        if ($category === null) {
            $category = $this->name;
        }
        Craft::getLogger()->log($message, $level, $category);
        return $this;
    }

    /**
     * @param string $message
     * @param string|null $category defaults to the name of the logger
     * @return $this
     */
    public function info(string $message, string $category=null) : ARLogger
    {
        return $this->log($message, Logger::LEVEL_INFO, $category);
    }

    /**
     * @param string $message
     * @param string|null $category defaults to the name of the logger
     * @return $this
     */
    public function warning(string $message, string $category=null) : ARLogger
    {
        return $this->log($message, Logger::LEVEL_WARNING, $category);
    }

    /**
     * @param string $message
     * @param string|null $category defaults to the name of the logger
     * @return $this
     */
    public function error(string $message, string $category=null) : ARLogger
    {
        return $this->log($message, Logger::LEVEL_ERROR, $category);
    }
}
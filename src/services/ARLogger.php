<?php
declare(strict_types=1);

namespace alanrogers\tools\services;

use Craft;
use yii\log\FileTarget;
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
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $log_filename = $this->name . '.log';

        // Create a new target and add the new file target to the log dispatcher
        $target = new FileTarget([
            'logFile' => self::LOG_FILE_PATH . $log_filename,
            'categories' => [ $this->name ],
            'enableRotation' => false
        ]);
        Craft::getLogger()->dispatcher->targets[$this->name] = $target;
    }

    /**
     * @param string $name
     * @return ARLogger
     */
    public static function getInstance(string $name=self::DEFAULT_LOG_NAME) : ARLogger
    {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new self($name);
        }
        return self::$instances[$name];
    }

    /**
     * @param string $message
     * @param int $level
     * @return $this
     */
    public function log(string $message, int $level=Logger::LEVEL_INFO) : ARLogger
    {
        Craft::getLogger()->log($message, $level, $this->name);
        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function info(string $message) : ARLogger
    {
        return $this->log($message, Logger::LEVEL_INFO);
    }

    /**
     * @param string $message
     * @return $this
     */
    public function warning(string $message) : ARLogger
    {
        return $this->log($message, Logger::LEVEL_WARNING);
    }

    /**
     * @param string $message
     * @return $this
     */
    public function error(string $message) : ARLogger
    {
        return $this->log($message, Logger::LEVEL_ERROR);
    }
}
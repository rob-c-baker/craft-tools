<?php
declare(strict_types=1);

namespace alanrogers\tools\services;

use Craft;
use craft\log\MonologTarget;
use yii\log\Logger;
use Psr\Log\LogLevel;

class ARLogger
{
    public const DEFAULT_MAX_FILES = 10;
    public const DEFAULT_LOG_NAME = 'ar';

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
    public function __construct(string $name=self::DEFAULT_LOG_NAME)
    {
        $this->name = $name;

        // already an instance with this name created, so the MonologTarget will have already been added
        if (isset(Craft::getLogger()->dispatcher->targets[$this->name])) {
            return;
        }

        if (isset(Craft::getLogger()->dispatcher->monologTargetConfig['maxFiles'])) {
            $max_files = (int) Craft::getLogger()->dispatcher->monologTargetConfig['maxFiles'];
        } else {
            $max_files = self::DEFAULT_MAX_FILES;
        }

        // Create a new target and add the new file target to the log dispatcher
        $target = new MonologTarget([
            'name' => $this->name,
            'categories' => [ $this->name ],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => false,
            'maxFiles' => $max_files
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
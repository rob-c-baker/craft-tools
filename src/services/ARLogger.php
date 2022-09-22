<?php
declare(strict_types=1);

namespace alanrogers\tools\services;

use Craft;
use craft\log\MonologTarget;
use yii\log\Logger;

class ARLogger
{
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
    public function __construct(string $name)
    {
        $this->name = $name;

        // Create a new file target and add the new file target to the log dispatcher
        Craft::getLogger()->dispatcher->targets[$this->name] = new MonologTarget([
            'name' => $this->name,
            'categories' => [ $this->name ],
            'logContext' => false,
            'allowLineBreaks' => false,
            'maxFiles' => 20 // greater than set in logrotate
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
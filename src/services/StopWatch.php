<?php declare(strict_types=1);

namespace alanrogers\tools\services;

class StopWatch
{
    private const string DEFAULT_CATEGORY = '__default';

    private const string DEFAULT_NAME = '__default';

    /**
     * @var array<string, StopWatch>
     */
    protected static array $watches = [];

    /**
     * @var array<string, float>
     */
    protected array $start_times = [];

    final protected function __construct() {}

    /**
     * Starts a named or default timer
     * @param string $name
     * @return void
     */
    public function start(string $name = self::DEFAULT_NAME): void
    {
        $this->start_times[$name] = microtime(true);
    }

    /**
     * Returns time in microseconds since the named or default timer started.
     * Returns null if the named timer has not been started.
     * @param string $name
     * @return float|null
     */
    public function elapsed(string $name = self::DEFAULT_NAME): ?float
    {
        if (!isset($this->start_times[$name])) {
            return null;
        }
        return microtime(true) - $this->start_times[$name];
    }

    /**
     * Stops and removes the named timer returning the number of microseconds since started.
     * @param string $name
     * @return float
     */
    public function stop(string $name = self::DEFAULT_NAME): float
    {
        $elapsed = $this->elapsed($name);
        unset($this->start_times[$name]);
        return $elapsed;
    }

    /**
     * @param string $category
     * @return static
     */
    public static function getWatch(string $category = self::DEFAULT_CATEGORY): static
    {
        if (!isset(static::$watches[$category])) {
            static::$watches[$category] = new static();
        }
        return static::$watches[$category];
    }

    /**
     * Returns a time that is more human-readable given a time in microseconds.
     * Format looks like: [hours]h [minutes]m [seconds]s [milliseconds]ms
     * @param float $time
     * @return string
     */
    public static function humanReadable(float $time): string
    {
        $time = $time / 1000; // into ms

        $uSecs = $time % 1000;
        $time = floor($time / 1000);

        $seconds = $time % 60;
        $time = floor($time / 60);

        $minutes = $time % 60;
        $time = floor($time / 60);

        $hours = $time;

        if ($hours > 0) {
            return sprintf('%02dh %02dm %02ds %dms', $hours, $minutes, $seconds, $uSecs);
        }
        if ($minutes > 0) {
            return sprintf('%02dm %02ds %dms', $minutes, $seconds, $uSecs);
        }
        if ($seconds > 0) {
            return sprintf('%02ds %dms', $seconds, $uSecs);
        }
        return sprintf('%dms', $uSecs);
    }
}
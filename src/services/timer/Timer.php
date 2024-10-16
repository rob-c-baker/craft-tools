<?php declare(strict_types=1);

namespace alanrogers\tools\services\timer;

use LogicException;

/**
 * Timer based on https://github.com/Ayesh/php-timer
 */
class Timer
{
    public const string FORMAT_MILLISECONDS = 'ms';
    public const string FORMAT_SECONDS = 's';
    public const string FORMAT_HUMAN = 'h';

    private const array TIMES = [
        'hour'   => 3600000,
        'minute' => 60000,
        'second' => 1000,
    ];

    /**
     * Stores all the timers statically.
     * @var Stopwatch[]
     */
    static private array $timers = [];

    /**
     * Start or resume the timer.
     *
     * Call this method to start the timer with a given key. The default key
     * is "default", and used in @param string $key
     *
     * @see Timer::read() and reset()
     * methods as well
     *
     * Calling this with the same $key will not restart the timer if it has already
     * started.
     */
    public static function start(string $key = 'default'): void
    {
        if (isset(self::$timers[$key])) {
            self::$timers[$key]->start();
            return;
        }
        self::$timers[$key] = new StopWatch();
    }

    /**
     * Resets a specific timer, or default timer if not passed the $key parameter.
     * To reset all timers, call
     * @param string $key
     * @see Timer::resetAll().
     */
    public static function reset(string $key = 'default'): void
    {
        unset(self::$timers[$key]);
    }

    /**
     * Resets ALL timers.
     * To reset a specific timer, @see Timer::reset().
     */
    public static function resetAll(): void
    {
        self::$timers = [];
    }

    /**
     * Returns the time elapsed in the format requested in the $format parameter.
     * To access a specific timer, pass the same key that
     *
     * @param string $key The key that the timer was started with. Default value is
     *   "default" throughout the class.
     * @param string|bool $format The default format is milliseconds. See the class constants for additional
     *   formats.
     *
     * @return string The formatted time, formatted by the formatter string passed for $format.
     * @throws LogicException
     * If the timer was not started, a \LogicException will be thrown. Use @see Timer::start()
     * to start a timer.
     */
    public static function read(string $key = 'default', string|bool $format = self::FORMAT_MILLISECONDS): string
    {
        if (isset(self::$timers[$key])) {
            return self::formatTime(self::$timers[$key]->read(), $format);
        }
        throw new LogicException('Reading timer when the given key timer was not initialized.');
    }

    /**
     * Formats the given time the processor into the given format.
     * @param float $value
     * @param string|bool $format
     * @return string
     */
    private static function formatTime(float $value, string|bool $format=false): string
    {
        return match ($format) {
            static::FORMAT_MILLISECONDS => (string) round($value * 1000, 2),
            static::FORMAT_SECONDS => (string) round($value, 3),
            static::FORMAT_HUMAN => static::secondsToTimeString($value),
            default => (string) ($value * 1000),
        };
    }

    private static function secondsToTimeString(float $time): string
    {
        $ms = (int) round($time * 1000);
        return Formatter::formatTime($ms);
    }

    /**
     * Stops the timer with the given key. Default key is "default"
     * @param string $key
     * @throws LogicException If the attempted timer has not started already.
     */
    public static function stop(string $key = 'default'): void
    {
        if (!isset(self::$timers[$key])) {
            throw new LogicException('Stopping timer when the given key timer was not initialized.');
        }
        self::$timers[$key]->stop();
    }

    /**
     * Return a list of timer names. Note that resetting a timer removes the timer.
     * @return string[]
     */
    public static function getTimers(): array
    {
        return array_keys(self::$timers);
    }
}
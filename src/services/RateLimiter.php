<?php

namespace alanrogers\tools\services;

use Craft;
use yii\redis\Connection;

/**
 * Requires a redis connection passed in or one is collected from the App config.
 */
class RateLimiter
{
    /**
     * Maximum number of hits within `self::TIME_PERIOD_DEFAULT`
     */
    private const MAX_HITS_DEFAULT = 5;

    /**
     * Time in seconds before the rate limiter is reset
     */
    private const TIME_PERIOD_DEFAULT = 60;

    /**
     * The maximum number of hits before rate limiting happens
     * @var int
     */
    private int $max_hits = self::MAX_HITS_DEFAULT;

    /**
     * The time limit in seconds after which the rate limiting is reset.
     * @var int
     */
    private int $time_period = self::TIME_PERIOD_DEFAULT;

    /**
     * The name of the feature that we are rate limiting (otherwise rate limiting is effectively global!)
     * @var string
     */
    private string $name;

    /**
     * @var Connection|null
     */
    private Connection $redis;

    /**
     * @param string $name
     * @param Connection|null $redis
     * @param int|null $max_hits Maximum number of hits within `$time_period`. Defaults to `RateLimiter::MAX_HITS_DEFAULT`.
     * @param int|null $time_period The time limit in seconds after which the rate limiting is reset. Defaults to `RateLimiter::TIME_PERIOD_DEFAULT`.
     */
    public function __construct(string $name, ?Connection $redis=null, ?int $max_hits=null, ?int $time_period=null)
    {
        if ($redis) {
            $this->redis = $redis;
        } else {
            /** @noinspection PhpUndefinedFieldInspection */
            $this->redis = Craft::$app->redis;
        }

        $this->name = $name;

        if ($max_hits !== null) {
            $this->max_hits = $max_hits;
        }

        if ($time_period !== null) {
            $this->time_period = $time_period;
        }
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function isRateLimited(string $identifier) : bool
    {
        if ($this->getHitCount($identifier) > $this->max_hits) {
            return true;
        }

        $this->incrementHits($identifier);
        return false;
    }

    /**
     * Gets the number of hits in the current `$this->time_period` for `$identifier`.
     * @param string $identifier Something identifying the user i.e. an IP address or session id
     * @return int
     */
    public function getHitCount(string $identifier) : int
    {
        $cache_key = self::cacheKey($this->name, $identifier);
        return (int) $this->redis->get($cache_key);
    }

    /**
     * Increments hits in the current `$this->time_period` for `$identifier`.
     * @param string $identifier
     * @return int current count
     */
    public function incrementHits(string $identifier) : int
    {
        $cache_key = self::cacheKey($this->name, $identifier);

        // Use a LUA script, so we get increment and expiry as a single atomic operation
        $lua_script = <<<'LUA'
local current
current = redis.call("incr",KEYS[1])
if current == 1 then
    redis.call("expire",KEYS[1],ARGV[1])
end
return current
LUA;
        return (int) $this->redis->eval($lua_script, 1, $cache_key, $this->time_period);
    }

    /**
     * Generates a cache key
     * @param string $name
     * @param string $identifier
     * @return string
     */
    private static function cacheKey(string $name, string $identifier) : string
    {
        return 'rl_' . $name . '_' . $identifier;
    }
}
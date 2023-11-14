<?php

namespace alanrogers\tools\services\es;

use Craft;
use yii\caching\CacheInterface;

class Maintenance
{
    private const MUTEX_KEY = 'es_maintenance_mutex_';
    private const MUTEX_CACHE_KEY = 'es_maintenance_active_';

    private string $index;
    private CacheInterface $cache;

    public function __construct(string|null $index)
    {
        $this->index = (string) $index;
        $this->cache = Craft::$app->getCache();

        // In case something breaks - the cache value needs to be removed
        register_shutdown_function(function() {
            $this->cache->delete(self::MUTEX_CACHE_KEY . $this->index);
        });
    }

    /**
     * @param int $timeout In seconds to wait for Mutex to be released.
     * @return bool
     */
    public function acquireMutex(int $timeout=30): bool
    {
        $acquired = Craft::$app->getMutex()->acquire(self::MUTEX_KEY . $this->index, $timeout);
        if ($acquired) {
            $this->cache->set(self::MUTEX_CACHE_KEY . $this->index, true);
        }
        return $acquired;
    }


    public function releaseMutex(): bool
    {
        $released = Craft::$app->getMutex()->release(self::MUTEX_KEY . $this->index);
        $this->cache->delete(self::MUTEX_CACHE_KEY . $this->index);
        return $released;
    }

    /**
     * Gives a non-atomic (but close) assertion as-to whether the index is currently under maintenance.
     * @return bool
     */
    public function isUnderMaintenance() : bool
    {
        return $this->cache->exists(self::MUTEX_CACHE_KEY . $this->index);
    }
}
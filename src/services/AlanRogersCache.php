<?php declare(strict_types=1);

namespace alanrogers\tools\services;

use craft\helpers\Json;
use yii\redis\Cache;
use yii\redis\Connection;

/**
 * Class AlanRogersCache
 * @package modules\ar\services
 */
class AlanRogersCache extends Cache
{
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->redis = new Connection([
            'unixSocket' => $_SERVER['REDIS_SOCKET'] ?: null,
            'hostname' => $_SERVER['REDIS_HOST'],
            'port' => $_SERVER['REDIS_PORT'],
            'database' => 1 // Craft uses database 0, for AR, we use database 1
        ]);
    }

    /**
     * Override the buildKey() method - we don't want or need md5() hashes in redis - we will
     * use the string we passed in.
     * @param mixed $key
     * @return string
     */
    public function buildKey($key) : string
    {
        if (!is_string($key)) {
            $key = Json::encode($key);
        }
        return $key;
    }
}
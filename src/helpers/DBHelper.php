<?php

namespace alanrogers\tools\helpers;

use Craft;
use PDO;
use yii\db\BatchQueryResult;
use yii\db\Connection;
use yii\db\QueryInterface;

class DBHelper
{
    public static function each(QueryInterface $query, string $init_sql = '', int $batchSize = 100): BatchQueryResult
    {
        return self::_batch($query, $init_sql, $batchSize, true);
    }

    public static function batch(QueryInterface $query, string $init_sql = '', int $batchSize = 100): BatchQueryResult
    {
        return self::_batch($query, $init_sql, $batchSize, false);
    }

    private static function _batch(QueryInterface $query, string $init_sql, int $batch_size, bool $each): BatchQueryResult
    {
        $db = Craft::$app->getDb();
        $unbuffered = $db->getIsMysql() && Craft::$app->getConfig()->getDb()->useUnbufferedConnections;

        if ($unbuffered) {
            $db = Craft::$app->getComponents()['db'];
            if (!is_object($db) || is_callable($db)) {
                $db = Craft::createObject($db);
            }
            $db->on(Connection::EVENT_AFTER_OPEN, function() use ($db, $init_sql) {
                $db->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                // execute any initialisation SQL
                if ($init_sql) {
                    $db->createCommand($init_sql)->execute();
                }
            });
        }

        /** @var BatchQueryResult $result */
        $result = Craft::createObject([
            'class' => BatchQueryResult::class,
            'query' => $query,
            'batchSize' => $batch_size,
            'db' => $db,
            'each' => $each,
        ]);

        if ($unbuffered) {
            $result->on(BatchQueryResult::EVENT_FINISH, function() use ($db) {
                $db->close();
            });
            $result->on(BatchQueryResult::EVENT_RESET, function() use ($db) {
                $db->close();
            });
        }

        return $result;
    }
}
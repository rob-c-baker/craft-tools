<?php

namespace alanrogers\tools\records;

use craft\db\ActiveRecord;

/**
 * Class Error
 * @package modules\ar\records
 * @property int $id
 * @property string $env
 * @property string $type ENUM('BACKEND', 'FRONTEND')
 * @property string $url
 * @property string $ip_address
 * @property string $message
 * @property string $stack (nullable)
 * @property string $other_data (nullable)
 */
class Error extends ActiveRecord
{
    public static function tableName() : string
    {
        return 'ar_error_reports';
    }
}
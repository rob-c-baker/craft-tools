<?php
declare(strict_types=1);

namespace alanrogers\tools\services;

use alanrogers\tools\services\errors\ErrorModel;
use alanrogers\tools\services\errors\ErrorType;
use alanrogers\tools\services\errors\reporters\Email;
use craft\errors\DeprecationException;
use Exception;
use Throwable;
use yii\base\Component;

class Error extends Component
{
    /**
     * @param string $message
     * @param string $stack
     * @param string $ip_address
     * @param array $other_data
     * @return bool
     */
    public function reportFrontEndError(string $message, string $stack, string $ip_address, array $other_data=[]) : bool
    {
        $error_model = ErrorModel::fromError($message, ErrorType::FRONTEND, 0, [
            'stack' => $stack,
            'ip_address' => $ip_address,
            'other_data' => $other_data
        ]);
        return ServiceLocator::getInstance()->error_handler->report($error_model);
    }

    /**
     * @param string $message
     * @param bool $send_email (defaults to false)
     * @param array $other_data
     * @return bool
     */
    public function reportBackEndError(string $message, bool $send_email=false, array $other_data=[]) : bool
    {
        $error_model = ErrorModel::fromError($message, ErrorType::BACKEND, 0, $other_data);
        if (!$send_email) {
            $error_model->preventReport(Email::class);
        }
        return ServiceLocator::getInstance()->error_handler->report($error_model);
    }

    /**
     * @param Throwable $e
     * @param bool $send_email
     * @param string|null $extra_message
     * @return bool
     */
    public function reportBackendException(Throwable $e, bool $send_email=false, ?string $extra_message=null) : bool
    {
        $error_model = ErrorModel::fromException($e);
        $error_model->extra_message = $extra_message;
        if (!$send_email) {
            $error_model->preventReport(Email::class);
        }
        return ServiceLocator::getInstance()->error_handler->report($error_model);
    }

    /**
     * Throws an exception with a message. This is not possible in Twig normally but this method enables that
     * @param string $exception_class_name
     * @param string $message
     * @return void
     */
    public function throwException(string $exception_class_name, string $message) : void
    {
        throw new $exception_class_name($message);
    }

    /**
     * Purge errors older than the specified number of days
     * @param string|null $type Set to null to purge all types
     * @param int $days_old
     * @return int the number of rows deleted
     * @throws Exception
     */
    public function purgeErrors(?string $type='FRONTEND', int $days_old=30) : int
    {
        throw new DeprecationException('Error::purgeErrors() deprecated - Use \\craft-tools\\services\\errors\\reporters\\Database::pruneRecords() instead.');
    }
}
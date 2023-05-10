<?php
declare(strict_types=1);

namespace alanrogers\tools\services;

use Craft;
use craft\helpers\Json;
use craft\web\Request;
use DateTime;
use Exception;
use alanrogers\tools\queue\jobs\SendCustomEmail;
use Throwable;
use yii\base\Component;
use alanrogers\tools\records\Error as ErrorRecord;

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
        $error_record = new ErrorRecord();

        $error_record->type = 'FRONTEND';
        $error_record->url = $other_data['url'] ?? self::getCurrentURL();
        $error_record->env = strtoupper(getenv('ENVIRONMENT'));
        $error_record->ip_address = $ip_address;
        $error_record->message = $message;
        $error_record->stack = $stack;
        $error_record->other_data = Json::encode($other_data, JSON_INVALID_UTF8_SUBSTITUTE);

        return $error_record->save();
    }

    /**
     * @param string $message
     * @param bool $send_email (defaults to false)
     * @param array $other_data
     * @return bool
     */
    public function reportBackEndError(string $message, bool $send_email=false, array $other_data=[]) : bool
    {
        $error_record = new ErrorRecord();

        $error_record->type = 'BACKEND';
        $error_record->url = self::getCurrentURL();
        $error_record->env = strtoupper(getenv('ENVIRONMENT'));
        $error_record->ip_address = '127.0.0.1';
        $error_record->message = $message;
        $error_record->stack = '';
        $error_record->other_data = Json::encode($other_data, JSON_INVALID_UTF8_SUBSTITUTE);

        $result = $error_record->save();

        if ($send_email) {

            if (Craft::$app->getRequest()->isConsoleRequest) {
                $message = '[Console Request]' . "\n\n" . $message;
            } else {
                $message = 'URL: ' . Craft::$app->getRequest()->getAbsoluteUrl() . "\n\n" . $message;
            }

            if ($error_record->other_data) {
                $message .= "\n\n--------------------------------------------------------------------------";
                $message .= "\nOther Data: ";
                $message .= "\n" . $error_record->other_data;
            }

            $env = '[' . strtoupper(getenv('ENVIRONMENT')) . ']';

            $this->sendAdminNotificationEmail(
                $env . ' ERROR reported from alanrogers.com',
                $message
            );
        }

        return $result;
    }

    /**
     * @param Throwable $e
     * @param bool $send_email
     * @param string|null $extra_message
     * @return bool
     */
    public function reportBackendException(Throwable $e, bool $send_email=false, ?string $extra_message=null) : bool
    {
        $error_record = new ErrorRecord();
        $previous = $e->getPrevious();

        $other_data = [ 'file' => $e->getFile(), 'line' => $e->getLine(), 'previous' => $previous ];

        $error_record->type = 'BACKEND';
        $error_record->url = self::getCurrentURL();
        $error_record->ip_address = '127.0.0.1';
        if ($extra_message) {
            $error_record->message = $extra_message . "\r\n\r\n" . $e->getMessage();
        } else {
            $error_record->message = $e->getMessage();
        }
        $error_record->stack = $e->getTraceAsString();
        $error_record->other_data = Json::encode($other_data, JSON_INVALID_UTF8_SUBSTITUTE);

        $result = $error_record->save();

        if ($send_email) {
            $subject = sprintf(
                '[%s] %s EXCEPTION reported from %s',
                strtoupper(getenv('ENVIRONMENT')),
                get_class($e),
                getenv('SITE_URL')
            );
            if (Craft::$app->getRequest()->isConsoleRequest) {
                $message = '[Console Request]' . "\n\n";
            } else {
                $message = 'URL: ' . Craft::$app->getRequest()->getAbsoluteUrl() . "\n\n";
            }
            $message .= $e->getMessage()
                . "\n\n"
                . 'File: "' . $e->getFile() . '" Line: ' . $e->getLine()
                . "\n\n"
                . $e->getTraceAsString();
            if ($extra_message) {
                $message = $extra_message . "\r\n\r\n" . $message;
            }
            if ($previous) {
                $message .= "\n\n"
                    . "-------------------------------------------------------------------------------------"
                    . "\n\n"
                    . "Previous Exception: " . get_class($previous)
                    . "\n\n"
                    . $previous->getMessage()
                    .  "\n\n"
                    . 'File: "' . $previous->getFile() . '" Line: ' . $previous->getLine()
                    . "\n\n"
                    . $previous->getTraceAsString();
            }
            $this->sendAdminNotificationEmail($subject, $message);
        }

        return $result;
    }

    /**
     * Sends an email to admins. Purpose is to warn of an error.
     * @param string $subject
     * @param string $message
     * @return bool
     */
    public function sendAdminNotificationEmail(string $subject, string $message) : bool
    {
        $msg = new SendCustomEmail([
            'to' => 'developers@alanrogers.com',
            'from' => 'noreply@alanrogers.com',
            'text_body' => $message,
            'subject' => $subject,
            'headers' => [
                'X-Priority' => '1 (Highest)',
                'X-MSMail-Priority' => 'High',
                'Importance' => 'High'
            ]
        ]);

        return Craft::$app->getQueue()->priority(100)->push($msg) > 0;
    }

    /**
     * Throws an exception with a message. This is not possible in Twig normally but theis method enables that
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
        $from = new DateTime('-5 year');
        $to = new DateTime('-' . $days_old . ' day');

        $between_condition = [
            'BETWEEN',
            'dateCreated',
            $from->format('Y-m-d H:i:s'),
            $to->format('Y-m-d H:i:s')
        ];

        if ($type) {
            $condition = [
                'AND',
                $between_condition,
                [ 'type' => $type ]
            ];
        } else {
            $condition = $between_condition;
        }

        return ErrorRecord::deleteAll($condition);
    }

    /**
     * @return string|null
     */
    private static function getCurrentURL(): ?string
    {
        $url = null;
        $request = Craft::$app->getRequest();
        if ($request instanceof Request) {
            // pick up the URL if it was a web request
            $qs = $request->queryString;
            $url = $request->pathInfo . ($qs ? '?' . $qs : '');
        }
        return $url;
    }
}
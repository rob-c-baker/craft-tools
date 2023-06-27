<?php

namespace alanrogers\tools\services\errors\reporters;

use alanrogers\tools\helpers\Url;
use alanrogers\tools\services\errors\ErrorModel;
use alanrogers\tools\records\Error as ErrorRecord;
use Craft;
use DateTime;
use Exception;
use Throwable;

class Database implements Reporting
{
    public function initialise(): void {}

    public function report(ErrorModel $error): bool
    {
        $error_record = new ErrorRecord();

        if ($error->exception) {
            $stack_trace = $error->exception->getTraceAsString();
        } else {
            $stack_trace = (new Exception())->getTraceAsString();
        }

        $error_record->type = 'BACKEND';
        $error_record->url = Url::getCurrentURL();
        $error_record->env = getenv('ENVIRONMENT');
        $error_record->ip_address = $error->ip_address;
        if ($error->extra_message) {
            $error_record->message = $error->extra_message . "\r\n\r\n" . $error->message;
        } else {
            $error_record->message = $error->message;
        }
        $error_record->stack = $stack_trace;
        $error_record->other_data = $error->getData(true);

        return $error_record->save();
    }

    /**
     * Delete error records from the database that are at least `$days_old` days old.
     * @param int $days_old
     * @return void
     */
    public function pruneRecords(int $days_old=90): void
    {
        try {
            $start = new DateTime('-10 year');
            $end = new DateTime('-' . $days_old . ' days');
        } catch (Throwable $e) {
            $date_range_error = ErrorModel::fromException($e);
            $email_reporter = new Email();
            $email_reporter->report($date_range_error);
            return;
        }

        $transaction = null;

        try {
            $db = Craft::$app->getDB();
            $transaction = $db->beginTransaction();
            ErrorRecord::deleteAll(
                [ 'BETWEEN', 'dateCreated', $start->format('d/m/y'), $end->format('d/m/y') ]
            );
        } catch (Throwable $e) {
            $transaction?->rollBack();
            $prune_error = ErrorModel::fromException($e);
            $email_reporter = new Email();
            $email_reporter->report($prune_error);
        }

    }
}
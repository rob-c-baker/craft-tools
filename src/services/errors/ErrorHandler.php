<?php

namespace alanrogers\tools\services\errors;

use alanrogers\tools\services\errors\reporters\Database;
use alanrogers\tools\services\errors\reporters\Email;
use alanrogers\tools\services\errors\reporters\Reporting;
use alanrogers\tools\services\errors\reporters\Sentry;
use craft\events\ExceptionEvent;
use yii\base\Event;

class ErrorHandler
{
    public bool $enabled = true;

    /**
     * @var array<class-string, Reporting|null>
     */
    private array $reporters = [
        Database::class,
        Email::class,
        Sentry::class
    ];

    public function __construct()
    {
        $this->registerEvents();
    }

    /**
     * Returns `true` if all reporters' `report()` method returned `true`.
     * @param ErrorModel $model
     * @return bool
     */
    public function report(ErrorModel $model): bool
    {
        $results = [];
        foreach ($this->reporters as $class_name => $reporter) {
            if ($model->isPrevented($class_name)) {
                continue;
            }
            if (!isset($this->reporters[$class_name])) {
                $this->reporters[$class_name] = new $reporter();
            }
            $results[$class_name] = $this->reporters[$class_name]->report($model);
        }
        // return `true` if all `$results` are `true`:
        return in_array(false, $results, true) === false;
    }

    private function registerEvents(): void
    {
        $handler = function (ExceptionEvent $event) {
            if ($this->enabled) {
                $model = ErrorModel::fromException($event->exception);
                $this->report($model);
            }
        };

        // web
        Event::on(
            \craft\web\ErrorHandler::class,
            \craft\web\ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
            $handler
        );

        // console
        Event::on(
            \craft\console\ErrorHandler::class,
            \craft\console\ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
            $handler
        );
    }
}
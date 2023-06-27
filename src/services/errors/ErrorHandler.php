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
    /**
     * @var array{ enabled: boolean, reporters: class-string[] }
     */
    private static array $default_config = [
        'enabled' => true,
        'reporters' => [
            Database::class,
            Email::class,
            Sentry::class
        ]
    ];

    public bool $enabled = false;

    /**
     * @var array<class-string, Reporting|null>
     */
    private array $reporters = [];


    public function __construct(array $config=[]) // @todo how to get config in when triggered via ServiceManager (no constructor params?)
    {
        $config = [ ...self::$default_config, ...$config ];
        $this->enabled = $config['enabled'];
        $this->reporters = array_fill_keys($config['reporters'], null);
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
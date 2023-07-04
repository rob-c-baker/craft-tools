<?php

namespace alanrogers\tools\services\errors;

use alanrogers\tools\events\ErrorHandlerInitEvent;
use alanrogers\tools\services\errors\reporters\Database;
use alanrogers\tools\services\errors\reporters\Email;
use alanrogers\tools\services\errors\reporters\Reporting;
use alanrogers\tools\services\errors\reporters\Sentry;
use craft\base\Component;
use craft\events\ExceptionEvent;
use Throwable;
use yii\base\Event;

class ErrorHandler extends Component
{
    /**
     * @event ErrorHandlerInit The event that is triggered when the error handler is initialised, can be used for
     * configuration and other modifications.
     */
    const EVENT_ERROR_HANDLER_INIT = 'errorHandlerInit';

    public bool $enabled = true;

    /**
     * @var array<class-string, Reporting|null>
     */
    private array $reporters = [];

    /**
     * @var string|int[]
     */
    private array $suppressed_exception_codes = [];

    public function init(): void
    {
        $event = new ErrorHandlerInitEvent([
            'enabled' => true,
            'reporters' => [
                Database::class => null,
                Email::class => null,
                Sentry::class => null
            ]
        ]);

        $this->trigger(self::EVENT_ERROR_HANDLER_INIT, $event);

        $this->enabled = $event->enabled;
        $this->reporters = $event->reporters;

        foreach ($this->reporters as $class_name => $reporter) {
            $this->reporters[$class_name] = new $class_name();
            $this->reporters[$class_name]->initialise();
        }

        $this->registerEvents();
    }

    /**
     * @param string|int[] $codes
     * @return void
     */
    public function setSuppressedExceptionCodes(array $codes) : void
    {
        $this->suppressed_exception_codes = $codes;
    }

    /**
     * @param class-string<Reporting> $class_name
     * @return Reporting|null
     */
    public function getReporter(string $class_name) : ?Reporting
    {
        if (isset($this->reporters[$class_name])) {
            return $this->reporters[$class_name];
        }
        return null;
    }

    /**
     * Returns `true` if all reporters' `report()` method returned `true`.
     * @param ErrorModel $model
     * @return bool
     */
    public function report(ErrorModel $model): bool
    {
        if (!$this->enabled) {
            return false;
        }
        if ($model->exception && !$this->isErrorCodeSuppressed($model->exception)) {
            return true; // ignore but report successful
        }
        $results = [];
        foreach ($this->reporters as $class_name => $reporter) {
            if ($model->isPrevented($class_name)) {
                continue;
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

    /**
     * @param Throwable $exception
     * @return bool
     */
    private function isErrorCodeSuppressed(Throwable $exception) : bool
    {
        $code = $exception->getCode();
        if (isset($exception->statusCode)) {
            $code = $exception->statusCode;
        }
        return in_array($code, $this->suppressed_exception_codes);
    }
}
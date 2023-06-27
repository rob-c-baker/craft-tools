<?php

namespace alanrogers\tools\services\errors;

use Craft;
use craft\helpers\Json;
use craft\web\Request;
use Throwable;

class ErrorModel
{
    /**
     * If handling an exception, this is it.
     * @var Throwable|null
     */
    public ?Throwable $exception = null;

    public ErrorType $type;

    /**
     * If handling a custom generated error, this is the message.
     * @var string|null
     */
    public ?string $message = null;

    /**
     * Any additional message to add to the report
     * @var string|null
     */
    public ?string $extra_message = null;

    /**
     * If handling a custom generated error, this is the code
     * @var int|string|null
     */
    public int|string|null $code = null;

    public ?string $ip_address = null;

    /**
     * Any arbitrary data to be included with the error here.
     * Ends up JSON encoded.
     * @var mixed|null
     */
    private mixed $data = null;

    /**
     * An array of fully qualified class names that will not be called when reporting
     * @var array
     */
    private array $prevent = [];

    /**
     * @param bool $as_json
     * @return mixed
     */
    public function getData(bool $as_json=false): mixed
    {
        if ($as_json) {
            return Json::encode($this->data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT);
        }
        return $this->data;
    }

    /**
     * Is a reporter class identified by it's fully qualified name going to be called?
     * @param string $class_name
     * @return bool
     */
    public function isPrevented(string $class_name) : bool
    {
        return in_array($class_name, $this->prevent, true);
    }

    /**
     * Adds a fully qualified class name to the list of reports that will not be called for this error.
     * @param string $report_class
     * @return void
     */
    public function preventReport(string $report_class) : void
    {
        if (!$this->isPrevented($report_class)) {
            $this->prevent[] = $report_class;
        }
    }

    /**
     * @param mixed $data
     * @param bool $is_json
     * @return $this
     */
    public function setData(mixed $data, bool $is_json=false): ErrorModel
    {
        if ($is_json) {
            $data = Json::decode($data);
        }
        $this->data = $data;
        return $this;
    }

    /**
     * @param Throwable $exception
     * @param mixed|null $data
     * @return ErrorModel
     */
    public static function fromException(Throwable $exception, mixed $data=null): ErrorModel
    {
        $request = Craft::$app->getRequest();
        $instance = new ErrorModel();
        $instance->type = ErrorType::BACKEND;
        $instance->ip_address = $request instanceof Request ? $request->getUserIP() : '(console)';
        $instance->message = $exception->getMessage();
        $instance->exception = $exception;
        $instance->code = $exception->getCode();
        $instance->setData($data);
        return $instance;
    }

    /**
     * @param string $message
     * @param ErrorType|null $type
     * @param int $code
     * @param mixed|null $data
     * @return ErrorModel
     */
    public static function fromError(string $message, ?ErrorType $type=null, int $code=0, mixed $data=null): ErrorModel
    {
        $request = Craft::$app->getRequest();
        $instance = new ErrorModel();
        $instance->type = $type ?? ErrorType::BACKEND;
        $instance->ip_address = $request instanceof Request ? $request->getUserIP() : '(console)';
        $instance->message = $message;
        $instance->code = $code;
        $instance->setData($data);
        return $instance;
    }
}
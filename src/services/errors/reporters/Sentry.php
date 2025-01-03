<?php

namespace alanrogers\tools\services\errors\reporters;

use alanrogers\tools\services\errors\ErrorModel;
use Sentry\Severity;
use Sentry\State\Scope;
use function Sentry\captureException;
use function Sentry\captureMessage;
use function Sentry\withScope;

class Sentry implements Reporting
{
    private bool $enabled = false;

    /**
     * @var int[]
     */
    private array $excluded_status_codes = [];

    public function initialise(): void
    {
        $this->enabled = (bool) ($_SERVER['SENTRY_ENABLED'] ?? false);
        $this->excluded_status_codes = isset($_SERVER['SENTRY_IGNORE_ERROR_CODES'])
            ?  array_map('intval', array_map('trim', explode(',', $_SERVER['SENTRY_IGNORE_ERROR_CODES'])))
            : [ 404, 410 ];
    }

    public function report(ErrorModel $error): bool
    {
        if (!$this->enabled) {
            return true;
        }
        if ($error->exception) {
            $status_code = isset($error->exception->statusCode) ? (int) $error->exception->statusCode : null;
            if (in_array($status_code, $this->excluded_status_codes, true)) {
                return true;
            }
            if (in_array($error->exception->getCode(), $this->excluded_status_codes, true)) {
                return true;
            }
            return (bool) withScope(function(Scope $scope) use ($error) {
                $data = $error->getData();
                if ($data) {
                    $scope->setContext('data', $error->getData());
                }
                return captureException($error->exception);
            });
        }
        $message = $error->extra_message ? ($error->extra_message . "\n\n" . $error->message) : $error->message;
        return (bool) withScope(function(Scope $scope) use ($message, $error) {
            $data = $error->getData();
            if ($data) {
                $scope->setContext('data', $error->getData());
            }
            return captureMessage($message, Severity::error());
        });
    }
}
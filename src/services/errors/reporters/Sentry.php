<?php

namespace alanrogers\tools\services\errors\reporters;

use alanrogers\tools\services\errors\ErrorModel;
use Sentry\Severity;
use function Sentry\captureException;
use function Sentry\captureMessage;

class Sentry implements Reporting
{
    private bool $enabled = false;

    private array $excluded_status_codes = [];

    public function initialise(): void
    {
        $this->enabled = (bool) ($_SERVER['SENTRY_ENABLED'] ?? false);
        $this->excluded_status_codes = [ 404 ];
    }

    public function report(ErrorModel $error): bool
    {
        if (!$this->enabled) {
            return true;
        }
        if ($error->exception) {
            if (isset($error->exception->statusCode)) {
                if (in_array($error->exception->statusCode, $this->excluded_status_codes)) {
                    return true;
                }
            }
            return (bool) captureException($error->exception);
        }
        $message = $error->extra_message ? ($error->extra_message . "\n\n" . $error->message) : $error->message;
        return (bool) captureMessage($message, Severity::error());
    }
}
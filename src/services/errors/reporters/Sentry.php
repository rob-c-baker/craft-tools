<?php

namespace alanrogers\tools\services\errors\reporters;

use alanrogers\tools\events\ErrorHandlerSentryConfigEvent;
use alanrogers\tools\services\errors\ErrorModel;
use Craft;
use craft\base\Component;
use Sentry\Severity;
use function Sentry\captureException;
use function Sentry\captureMessage;
use function Sentry\init;

class Sentry extends Component implements Reporting
{
    const EVENT_DEFINE_SENTRY_CONFIG = 'defineSentryConfig';

    private array $excluded_status_codes = [];

    public function initialise(): void
    {
        $event = new ErrorHandlerSentryConfigEvent([
            'dsn' => $_SERVER['SENTRY_DSN'],
            'environment' => $_SERVER['ENVIRONMENT'] ?? 'production',
            'release' => gethostname() . '@' . ($_SERVER['COMMIT_REF'] ?? 'master'),
            'excluded_status_codes' => [ 400, 404 ]
        ]);

        $this->trigger(self::EVENT_DEFINE_SENTRY_CONFIG, $event);

        if (!isset($_SERVER['SENTRY_DSN'])) {
            Craft::warning('SENTRY_DSN env variable is not defined. Sentry not enabled.');
            return;
        }
        $this->excluded_status_codes = $event->excluded_status_codes;

        init($event->asSentryConfig());
    }

    public function report(ErrorModel $error): bool
    {
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
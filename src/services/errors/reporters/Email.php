<?php

namespace alanrogers\tools\services\errors\reporters;

use alanrogers\tools\queue\jobs\SendCustomEmail;
use alanrogers\tools\services\errors\ErrorModel;
use Craft;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\web\Request;

class Email implements Reporting
{
    public function report(ErrorModel $error): bool
    {
        $request = Craft::$app->getRequest();

        if ($error->exception) {
            $stack_trace = $error->exception->getTraceAsString();
        } else {
            $stack_trace = (new \Exception())->getTraceAsString();
        }

        try {
            $email_content = Craft::$app->getView()->renderTemplate(
                'alanrogers-tools/error-reporting/plain-text-email.twig',
                [
                    'error_model' => $error,
                    'is_console' => Craft::$app->getRequest()->isConsoleRequest,
                    'url' => $request instanceof Request ? $request->getAbsoluteUrl() : null,
                    'stack_trace' => $stack_trace
                ]
            );
        } catch (LoaderError|RuntimeError|SyntaxError|Exception $e) {
            Craft::error('Cannot report error due to template exception: ' . $e->getMessage());
            return false;
        }

        $env = '[' . strtoupper(getenv('ENVIRONMENT')) . ']';
        $type = $error->exception ? get_class($error->exception) . ' EXCEPTION' : 'ERROR';

        return $this->sendAdminNotificationEmail(
            $env . ' ' . $type . ' reported from ' . Craft::$app->getSites()->currentSite->baseUrl,
            $email_content
        );
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
}
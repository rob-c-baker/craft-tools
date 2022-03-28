<?php

namespace alanrogers\tools\queue\jobs;

use Craft;
use craft\queue\BaseJob;
use yii\queue\RetryableJobInterface;
use yii\swiftmailer\Message;

class SendCustomEmail extends BaseJob implements RetryableJobInterface
{
    public const TTR = 60;
    public const MAX_ATTEMPTS = 10;
    public const MAIL_FROM_ADDRESS = 'no-reply@alanrogers.com';
    public const RETURN_PATH_ADDRESS = 'alan@alanrogers.com';
    public const ORGANISATION_HEADER = 'Alan Rogers Travel Group';

    /**
     * @var string
     */
    public string $from = self::MAIL_FROM_ADDRESS;

    /**
     * @var string
     */
    public string $to = '';

    /**
     * @var string
     */
    public string $reply_to = '';

    /**
     * @var string
     */
    public string $subject = '';

    /**
     * @var string
     */
    public string $text_body = '';

    /**
     * @var string
     */
    public string $html_body = '';

    /**
     * @var array
     */
    public array $headers = [];

    /**
     * @inheritDoc
     */
    public function execute($queue)
    {
        $message = new Message();

        $message->setFrom($this->from)
            ->setReturnPath(self::RETURN_PATH_ADDRESS)
            ->setTo($this->to)
            ->setSubject($this->subject);

        if ($this->html_body) {
            $message->setHtmlBody($this->html_body);
        }
        if ($this->text_body) {
            $message->setTextBody($this->text_body);
        }

        // Custom headers (help's prevent winding up in spam folder)
        $message->setHeaders([
            'Organization' => self::ORGANISATION_HEADER,
            'X-Priority' => '3',
            'X-MSMail-Priority' => 'Normal',
            'Importance' => 'Normal'
        ]);

        foreach ($this->headers as $header => $value) {
            $message->setHeader($header, $value);
        }

        if ($this->reply_to) {
            $message->setReplyTo($this->reply_to);
        }

        if (Craft::$app->getMailer()->send($message)) {
            $message = sprintf('Custom email sent to "%s" with subject "%s"', $this->to, $this->subject);
            Craft::info($message, 'AR_EMAIL');
        } else {
            $message = sprintf('Custom email COULD NOT BE SENT to "%s" with subject "%s"', $this->to, $this->subject);
            Craft::error($message, 'AR_EMAIL');
        }
    }

    /**
     * @inheritDoc
     */
    public function getTtr() : int
    {
        return self::TTR;
    }

    /**
     * @inheritDoc
     */
    public function canRetry($attempt, $error) : bool
    {
        return $attempt <= static::MAX_ATTEMPTS;
    }

    public function getDescription() : ?string
    {
        return sprintf('Sending email [%.50s]', $this->subject);
    }

}
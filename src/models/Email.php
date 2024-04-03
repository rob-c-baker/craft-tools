<?php
declare(strict_types=1);

namespace alanrogers\tools\models;

use yii\base\Model;

class Email extends Model
{
    public const string DEFAULT_FROM = 'no-reply@alanrogers.com';

    /**
     * From email field
     * @var string
     */
    public string $from = self::DEFAULT_FROM;

    /**
     * To email field
     * @var string
     */
    public string $to = '';

    /**
     * Reply-to email field
     * @var string|null
     */
    public ?string $reply_to = null;

    /**
     * @var string
     */
    public string $subject = '';

    /**
     * HTML message content
     * @var string
     */
    public string $html = '';

    /**
     * Text message content
     * @var string
     */
    public string $text = '';
}
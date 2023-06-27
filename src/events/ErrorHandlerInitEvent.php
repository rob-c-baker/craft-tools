<?php

namespace alanrogers\tools\events;

use alanrogers\tools\services\errors\reporters\Reporting;
use yii\base\Event;

class ErrorHandlerInitEvent extends Event
{
    /**
     * Whether error handling is enabled
     * @var boolean
     */
    public bool $enabled = true;

    /**
     * @var array<class-string, Reporting|null>
     */
    public array $reporters = [];
}
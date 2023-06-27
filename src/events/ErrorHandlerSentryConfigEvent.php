<?php

namespace alanrogers\tools\events;

use yii\base\Event;

class ErrorHandlerSentryConfigEvent extends Event
{
    public string $dsn = '';
    public string $environment = '';
    public string $release = '';

    public array $excluded_status_codes = [];

    public function asSentryConfig(): array
    {
        return [
            'dsn' => $this->dsn,
            'environment' => $this->environment,
            'release' => $this->release
        ];
    }
}
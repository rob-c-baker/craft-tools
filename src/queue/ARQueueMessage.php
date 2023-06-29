<?php

namespace alanrogers\tools\queue;

class ARQueueMessage
{
    public string $id;
    public string $message;
    public ?string $description;
    public int $ttr;
    public int $delay;
    public mixed $priority;

    public int $timePushed;

    public function __construct(string $id, string $message, ?string $description, int $ttr, int $delay=0, mixed $priority=1024)
    {
        $this->id = $id;
        $this->message = $message;
        $this->description = $description;
        $this->ttr = $ttr;
        $this->delay = $delay;
        $this->priority = $priority;
        $this->timePushed = time();
    }
}
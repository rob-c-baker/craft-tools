<?php

namespace alanrogers\tools\services\errors\reporters;

use alanrogers\tools\services\errors\ErrorModel;

class Sentry implements Reporting
{
    public function report(ErrorModel $error): bool
    {
        // TODO: Implement report() method.
    }
}
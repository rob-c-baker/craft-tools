<?php

namespace alanrogers\tools\services\errors\formatters;

use alanrogers\tools\services\errors\ErrorModel;

interface Formatter
{
    public function format(ErrorModel $model): string;
}
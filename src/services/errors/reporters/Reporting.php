<?php

namespace alanrogers\tools\services\errors\reporters;

use alanrogers\tools\services\errors\ErrorModel;

interface Reporting
{
    public function initialise() : void;
    public function report(ErrorModel $error) : bool;
}
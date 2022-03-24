<?php

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;

class ARRef extends Base
{
    protected function validate($value) : bool
    {
        if (!(bool) preg_match('/^[A-Z]{2}\d{3,5}$/', $value)) {
            $this->addError('Invalid AR Ref.');
            return false;
        }
        return true;
    }
}
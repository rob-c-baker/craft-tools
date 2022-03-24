<?php

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;

class Email extends Base
{
    /**
     * @inheritDoc
     */
    protected function validate($value): bool
    {
        $result = filter_var($value, FILTER_VALIDATE_EMAIL);
        if (!$result) {
            $this->addError(sprintf('%s is not a valid email address.', $value));
        }
        return $result;
    }

}
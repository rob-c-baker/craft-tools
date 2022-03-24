<?php

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use Assert\Assertion;
use Assert\AssertionFailedException;

class Integer extends Base
{
    /**
     * @inheritDoc
     */
    protected function validate($value): bool
    {
        try {
            return Assertion::integerish($value);
        } catch (AssertionFailedException $e) {
            $this->addError('The value must be a whole number (integer).');
            return false;
        }
    }
}
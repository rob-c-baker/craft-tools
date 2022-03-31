<?php

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use Assert\Assertion;
use Assert\AssertionFailedException;

class Latitude extends Base
{
    /**
     * @inheritDoc
     */
    protected function validate($value): bool
    {
        try {
            Assertion::greaterOrEqualThan($value, -90.0);
            Assertion::lessOrEqualThan($value, 90.0);
        } catch (AssertionFailedException $e) {
            $this->addError('Latitude is not within the range -90 to +90');
            return false;
        }
        return true;
    }
}
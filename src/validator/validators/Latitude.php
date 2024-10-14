<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use Assert\Assertion;
use Assert\AssertionFailedException;
use Override;

class Latitude extends Base
{
    /**
     * @inheritDoc
     */
    #[Override]
    protected function validate(mixed $value): bool
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
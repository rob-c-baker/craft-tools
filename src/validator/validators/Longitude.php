<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use Assert\Assertion;
use Assert\AssertionFailedException;
use Override;

class Longitude extends Base
{
    /**
     * @inheritDoc
     */
    #[Override]
    protected function validate(mixed $value): bool
    {
        try {
            Assertion::greaterOrEqualThan($value, -180.0);
            Assertion::lessOrEqualThan($value, 180.0);
        } catch (AssertionFailedException $e) {
            $this->addError('Longitude is not within the range -180 to +180');
            return false;
        }
        return true;
    }
}
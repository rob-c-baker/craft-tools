<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use Override;

class ARRef extends Base
{
    #[Override]
    protected function validate(mixed $value) : bool
    {
        if (!is_string($value)) {
            $this->addError('AR Ref must be a string.');
            return false;
        }
        if (!preg_match('/^[A-Z]{2}\d{3,5}$/', $value)) {
            $this->addError('Invalid AR Ref.');
            return false;
        }
        return true;
    }
}
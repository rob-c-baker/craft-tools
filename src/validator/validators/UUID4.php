<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;

class UUID4 extends Base
{
    /**
     * @inheritDoc
     */
    protected function validate(mixed $value): bool
    {
        if (!is_string($value)) {
            $this->addError('Not a string, so not a valid UUID v4');
            return false;
        }
        $result = preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value);
        if (!$result) {
            $this->addError('Not a valid UUID v4');
        }
        return (bool) $result;
    }
}
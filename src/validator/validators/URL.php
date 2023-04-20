<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;

class URL extends Base
{
    /**
     * @inheritDoc
     */
    protected function validate(mixed $value): bool
    {
        $result = filter_var($value, FILTER_VALIDATE_URL);
        if (!$result) {
            $this->addError('Not a valid URL');
        }
        return $result !== false;
    }

}
<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use Override;

class Email extends Base
{
    /**
     * @inheritDoc
     */
    #[Override]
    protected function validate(mixed $value): bool
    {
        $result = filter_var($value, FILTER_VALIDATE_EMAIL);
        if (!$result) {
            $this->addError(sprintf('%s is not a valid email address.', $value));
        }
        return $result !== false; // filter_var($value, FILTER_VALIDATE_EMAIL) returns the email if it's valid or false
    }

}
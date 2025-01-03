<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use Override;

class Enum extends Base
{
    /**
     * @inheritDoc
     */
    #[Override]
    protected function validate(mixed $value): bool
    {
        if (!isset($this->options['values'])) {
            throw new \InvalidArgumentException('To use the Enum validator you must pass in an $options parameter to the constructor with with an array key of "values" containing the allowed values.');
        }

        $result = in_array($value, $this->options['values'], $this->options['strict'] ?? true);
        if (!$result) {
            $this->addError(sprintf(
                'The value "%.1024s" is not in the allowed values: "%s"',
                $value,
                implode('", "', $this->options['values'])
            ));
        }
        return $result;
    }
}
<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;

class Enum extends Base
{
    /**
     * @inheritDoc
     */
    protected function validate($value): bool
    {
        if (!isset($this->options['values'])) {
            throw new \InvalidArgumentException('To use the Enum validator you must pass in an $options parameter to the constructor with with an array key of "values" containing the allowed values.');
        }

        $result = in_array($value, $this->options['values']);
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
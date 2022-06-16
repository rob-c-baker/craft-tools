<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use Assert\Assertion;
use Assert\AssertionFailedException;

class IntegerMinMax extends Base
{
    /**
     * With no options passed in, this will return true for a value of any positive int
     * up to PHP_INT_MAX which should be 9223372036854775807 in 64bit systems and 2147483647 in 32bit).
     * To make this more useful, pass options in the constructor like: [ 'min' => 0, 'max' => 100 ]
     * ...Leaving an options out is fine - it will revert to default.
     * @inheritDoc
     */
    protected function validate($value): bool
    {
        if (!isset($this->options['min'])) {
            // min defaults to 0, unless otherwise specified
            $this->options['min'] = 0;
        }

        if (!isset($this->options['max'])) {
            // max defaults to PHP_INT_MAX unless otherwise specified
            $this->options['max'] = PHP_INT_MAX;
        }

        $result = false;

        try {
           $result = Assertion::integerish($value);
        } catch (AssertionFailedException $e) {
            $this->addError('The value must be a whole number (integer).');
        }

        $result = $result && ($value >= $this->options['min'] && $value <= $this->options['max']);

        if (!$result) {
            $this->addError(sprintf(
                'The value must be between %d and %d.',
                $this->options['min'],
                $this->options['max']
            ));
        }
        return $result;
    }

}
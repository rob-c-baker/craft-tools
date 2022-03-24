<?php

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;

class ArrayOfValidatedTypes extends Base
{
    /**
     * @inheritDoc
     */
    protected function validate($value): bool
    {
        if (!isset($this->options['validator']) || !$this->options['validator'] instanceof Base) {
            throw new \InvalidArgumentException('To use the ArrayOfValidTypes validator you must pass in an $options parameter to the constructor with with an array key of "validator" containing the validator instance to apply to each array element.');
        }

        $validator = $this->options['validator'];
        $result = true;

        foreach ($value as $idx => $val) {
            $valid = $validator->validate($val);
            if (!$valid) {
                foreach ($validator->getErrors() as $error) {
                    $this->addError(sprintf('At index [%d]: %s', $idx, $error));
                }
                $result = false;
            }
        }

        return $result;
    }

}
<?php

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;

class MinLength extends Base
{
    /**
     * @inheritDoc
     */
    protected function validate($value): bool
    {
        if (!isset($this->options['length'])) {
            throw new \InvalidArgumentException('To use the MinLength validator you must pass in an $options parameter to the constructor with with an array key of "length" containing the minimum allowed length.');
        }

        if (isset($this->options['strip_tags']) && $this->options['strip_tags']) {
            $value = strip_tags($value);
        }

        $result = mb_strlen($value) >= $this->options['length'];
        if (!$result) {
            $report_val = $value;
            if (mb_strlen($report_val) > 200) {
                $report_val = mb_substr($report_val, 0, 200) . '...';
            }
            $this->addError(sprintf(
                'The value "%s" must be no shorter than %d characters.',
                $report_val,
                $this->options['length']
            ));
        }
        return $result;
    }

}
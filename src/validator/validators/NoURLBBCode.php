<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;

/**
 * Ensures no BBCode for including a URL is within the value.
 */
class NoURLBBCode extends Base
{
    public const BB_CODE_URL_REGEX = '/\[url(?:\=("|\'|)?(.*)?\1)?\](.*)\[\/url\]/';

    protected function validate($value): bool
    {
        $result = preg_match(self::BB_CODE_URL_REGEX, $value);
        if ($result) {
            $this->addError('BBCode URLs not allowed.');
        }
        return !$result;
    }
}
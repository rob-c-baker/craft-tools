<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use libphonenumber\NumberParseException;

class PhoneNumber extends Base
{
    /**
     * @inheritDoc
     */
    protected function validate($value): bool
    {
        if (!isset($this->options['iso2'])) {
            throw new \InvalidArgumentException('To use the PhoneNumber validator you must pass in an $options parameter to the constructor with with an array key of "iso2" containing a 2 letter country code.');
        }

        $phone_number_service = new \alanrogers\tools\services\PhoneNumber();
        $is_valid = $phone_number_service->validateFromString((string) $value, $this->options['iso2']);

        if (!$is_valid) {
            // see if we can parse it. If so, consider it OK.
            try {
                $phone_number_service->parse((string)$value, $this->options['iso2']);
                // if nothing thrown the number parsed ok, so:
                $is_valid = true;
            } catch (NumberParseException $e) {
                $this->addError('The number you entered did not pass validation. It needs to be in international format (i.e. +44 (0)1234 567890) and it could not be automatically converted.');
            }
        }

        return $is_valid;
    }
}
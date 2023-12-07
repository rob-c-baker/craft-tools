<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use InvalidArgumentException;
use Isbn\Exception;

class ISBN extends Base
{
    public const VARIANT_13_DIGIT = 'ISBN-13';
    public const VARIANT_10_DIGIT = 'ISBN-10';
    public const VARIANT_BOTH = 'ISBN-10-&-ISBN-13';

    protected function validate(mixed $value): bool
    {
        // Check what type we are validating
        $variant = $this->options['variant'] ?? null;
        if ($variant) {
            if ($variant !== self::VARIANT_13_DIGIT && $variant !== self::VARIANT_10_DIGIT) {
                throw new InvalidArgumentException('ISBN validator: `variant` is invalid.');
            }
        } else {
            $variant = self::VARIANT_BOTH;
        }

        // Basic checks
        if (!is_string($value)) {
            $this->addError('ISBN must be a string.');
            return false;
        }

        if (!$value) {
            $this->addError('Value is empty.');
            return false;
        }

        $isbn = new \Isbn\Isbn();

        if ($variant === self::VARIANT_BOTH) {
            return $isbn->validation->isbn($value);
        } elseif ($variant === self::VARIANT_10_DIGIT) {
            try {
                return $isbn->validation->isbn10($value);
            } catch (Exception $e) {
                $this->addError('Exception while validating.');
                return false;
            }
        }
        // must be self::VARIANT_13_DIGIT
        try {
            return $isbn->validation->isbn13($value);
        } catch (Exception $e) {
            $this->addError('Exception while validating.');
            return false;
        }
    }

}
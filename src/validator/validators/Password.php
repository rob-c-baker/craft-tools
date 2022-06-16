<?php
declare(strict_types=1);

namespace alanrogers\tools\validator\validators;

use alanrogers\tools\validator\Base;
use alanrogers\tools\services\PwnedPassword;

class Password extends Base
{
    private const DEFAULT_MIN_LENGTH = 8;
    private const DEFAULT_MAX_LENGTH = 1024;
    private const DEFAULT_CHECK_CASE = true;
    private const DEFAULT_CHECK_NUMBERS = true;
    private const DEFAULT_CHECK_SYMBOLS = true;
    private const DEFAULT_CHECK_PWNED_DB = true;

    // Note any of the below properties can be overridden in the $options array passed to the constructor.

    /**
     * @var int
     */
    private int $min_length = self::DEFAULT_MIN_LENGTH;

    /**
     * @var int
     */
    private int $max_length = self::DEFAULT_MAX_LENGTH;

    /**
     * @var bool
     */
    private bool $check_case = self::DEFAULT_CHECK_CASE;

    /**
     * @var bool
     */
    private bool $check_numbers = self::DEFAULT_CHECK_NUMBERS;

    /**
     * @var bool
     */
    private bool $check_symbols = self::DEFAULT_CHECK_SYMBOLS;

    /**
     * @var bool
     */
    private bool $check_pwned_db = self::DEFAULT_CHECK_PWNED_DB;

    /**
     * @inheritDoc
     */
    protected function validate($value) : bool
    {
        $is_valid = true;

        $this->setOptionProperties();

        if (strlen((string) $value) < $this->min_length) {
            $this->addError(sprintf('Password needs to be at least %d characters.', $this->min_length));
            $is_valid = false;
        }

        if (strlen((string) $value) > $this->max_length) {
            $this->addError(sprintf('Password needs to be less than %d characters.', $this->max_length));
            $is_valid = false;
        }

        if ($this->check_case && !self::containsBothCases((string) $value)) {
            $this->addError('Password needs to contain both upper and lower case letters.');
            $is_valid = false;
        }

        if ($this->check_numbers && !self::containsNumbers((string) $value)) {
            $this->addError('Password needs to contain at least 1 number.');
            $is_valid = false;
        }

        if ($this->check_symbols && !self::containsSymbols((string) $value)) {
            $this->addError('Password needs to contain at least 1 symbol like @, !, *, !, etc.');
            $is_valid = false;
        }

        if ($this->check_pwned_db) {
            $password_count = PwnedPassword::isPasswordPwned($value);
            if ($password_count > 0) {
                $msg = 'Password appears in a previously exposed 3rd party data breach %d times. See: https://haveibeenpwned.com/Passwords';
                $this->addError(sprintf($msg, $password_count));
                $is_valid = false;
            } /*elseif ($password_count === false) {
                // @todo there was no response from Pwned API
            }*/
        }

        return $is_valid;
    }

    /**
     * @param string $value
     * @return bool
     */
    private static function containsBothCases(string $value) : bool
    {
        return preg_match('/[A-Z]/', $value)
            && preg_match('/[a-z]/', $value);
    }

    /**
     * @param string $value
     * @return bool
     */
    private static function containsNumbers(string $value) : bool
    {
        return (bool) preg_match('/\d/', $value);
    }

    /**
     * @param string $value
     * @return bool
     */
    private static function containsSymbols(string $value) : bool
    {
        return (bool) preg_match('/[^a-zA-Z\d]/', $value);
    }
}
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
    protected int $min_length = self::DEFAULT_MIN_LENGTH;

    /**
     * @var int
     */
    protected int $max_length = self::DEFAULT_MAX_LENGTH;

    /**
     * @var bool
     */
    protected bool $check_case = self::DEFAULT_CHECK_CASE;

    /**
     * @var bool
     */
    protected bool $check_numbers = self::DEFAULT_CHECK_NUMBERS;

    /**
     * @var bool
     */
    protected bool $check_symbols = self::DEFAULT_CHECK_SYMBOLS;

    /**
     * @var bool
     */
    protected bool $check_pwned_db = self::DEFAULT_CHECK_PWNED_DB;

    /**
     * @var array<string, string>
     */
    private static array $criteria_messages = [
        'min_length' => [
            'error' => 'Password needs to be at least %d characters.'
        ],
        'max_length' => [
            'error' => 'Password needs to be less than %d characters.'
        ],
        'check_case' => [
            'error' => 'Password needs to contain both upper and lower case letters.'
        ],
        'check_numbers' => [
            'error' => 'Password needs to contain at least 1 number.'
        ],
        'check_symbols' => [
            'error' => 'Password needs to contain at least 1 symbol like @, !, *, !, etc.'
        ],
        'check_pwned_db' => [
            'error' => 'Password appears in a previously exposed 3rd party data breach %d times. See: https://haveibeenpwned.com/Passwords',
            'explanation' => 'Password must not have been exposed in a 3rd party data breach. See: <a href="https://haveibeenpwned.com/Passwords" target="_blank">https://haveibeenpwned.com/Passwords</a>'
        ]
    ];

    /**
     * Gets the criteria messages for passwords based on what's enabled via defaults and `$this->options`.
     * This is so, for example, we can show the criteria on the front-end before a user tries to set a password.
     * Note: messages may include HTML.
     * @return array
     */
    public function getCriteriaMessages() : array
    {
        // add unconditional ones first
        $messages = [
            sprintf(self::$criteria_messages['min_length']['error'], $this->min_length),
            sprintf(self::$criteria_messages['max_length']['error'], $this->max_length),
        ];

        if ($this->check_case) {
            $messages[] = self::$criteria_messages['check_case']['error'];
        }

        if ($this->check_numbers) {
            $messages[] = self::$criteria_messages['check_numbers']['error'];
        }

        if ($this->check_symbols) {
            $messages[] = self::$criteria_messages['check_symbols']['error'];
        }

        if ($this->check_pwned_db) {
            $messages[] = self::$criteria_messages['check_pwned_db']['explanation'];
        }

        return $messages;
    }

    /**
     * @inheritDoc
     */
    protected function validate(mixed $value) : bool
    {
        $is_valid = true;

        if (!is_string($value)) {
            $this->addError('A password must be a string.');
            return false;
        }

        if (strlen($value) < $this->min_length) {
            $this->addError(sprintf(self::$criteria_messages['min_length']['error'], $this->min_length));
            $is_valid = false;
        }

        if (strlen($value) > $this->max_length) {
            $this->addError(sprintf(self::$criteria_messages['max_length']['error'], $this->max_length));
            $is_valid = false;
        }

        if ($this->check_case && !self::containsBothCases($value)) {
            $this->addError(self::$criteria_messages['check_case']['error']);
            $is_valid = false;
        }

        if ($this->check_numbers && !self::containsNumbers($value)) {
            $this->addError(self::$criteria_messages['check_numbers']['error']);
            $is_valid = false;
        }

        if ($this->check_symbols && !self::containsSymbols($value)) {
            $this->addError(self::$criteria_messages['check_symbols']['error']);
            $is_valid = false;
        }

        if ($this->check_pwned_db) {
            $password_count = PwnedPassword::isPasswordPwned($value);
            if ($password_count > 0) {
                $this->addError(sprintf(self::$criteria_messages['check_pwned_db']['error'], $password_count));
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
        return (bool) preg_match('/\W/', $value);
    }
}
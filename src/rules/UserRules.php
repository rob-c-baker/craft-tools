<?php

namespace alanrogers\tools\rules;

use Craft;

class UserRules
{
    public const PASSWORD_MIN_LENGTH = 8;

    public static function define(): array
    {
        return [
            [
                'password',
                'string',
                'min' => self::PASSWORD_MIN_LENGTH,
                'tooShort' => Craft::t(
                    'craft-tools',
                    'Your password must be at least {min} characters.',
                    ['min' => self::PASSWORD_MIN_LENGTH]
                )
            ],
            [
                'password',
                'match',
                'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#\$%\^&\*])(?=.{7,})/',
                'message' => Craft::t(
                    'craft-tools',
                    'Your password must contain at least one of each of the following: A number, a lower-case character, an upper-case character, and a special character'
                )
            ],
        ];
    }
}
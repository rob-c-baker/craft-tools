<?php

namespace alanrogers\tools\validator;

use InvalidArgumentException;

class Factory
{
    /**
     * @template T of ValidatorInterface
     * @param class-string<T> $name
     * @param mixed $value The value to validate
     * @param array $options
     * @return ValidatorInterface
     */
    public static function create(string $name, $value, array $options=[]) : ValidatorInterface
    {
        if (class_exists($name)) {
            $instance = new $name($value, $options);
            if ($instance instanceof ValidatorInterface) {
                return $instance;
            }
            throw new InvalidArgumentException('The validator class string must inherit from `alanrogers\\tools\\validator\\Base`.');
        }
        throw new InvalidArgumentException(sprintf('The validator `%s` does not exist.', $name));
    }
}
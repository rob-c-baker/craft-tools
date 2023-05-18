<?php
declare(strict_types=1);

namespace alanrogers\tools\traits;

const __DEFAULT_ERROR_TRAIT_KEY = '__no_key__';

trait ErrorManagementTrait
{
    /**
     * @var array<string, string[]>
     */
    protected array $errors = [];

    /**
     * Becomes true if sting keys are used and changes return type of `getErrors()` from `string[]`
     * to `array<string, string[]>`
     * @var bool
     */
    protected bool $uses_keys = false;

    /**
     * @return array<string, string[]>|string[]
     */
    public function getErrors() : array
    {
        if ($this->uses_keys) {
            return $this->errors;
        }
        return $this->errors[__DEFAULT_ERROR_TRAIT_KEY] ?? [];
    }

    /**
     * Overwrites all errors with those passed in
     * @param array<string, string[]>|string[] $errors
     */
    public function setErrors(array $errors) : self
    {
        $this->clearErrors();
        $this->addErrors($errors);
        return $this;
    }

    /**
     * @return bool
     */
    public function hasErrors() : bool
    {
        return !empty($this->errors);
    }

    /**
     * @param string|string[] $msg
     * @param string|null $key If passed in, will add to an error array under that key
     */
    public function addError(array|string $msg, ?string $key=null) : self
    {
        if (is_array($msg)) {
            foreach ($msg as $m) {
                $this->addError($m, $key);
            }
        } else {
            if ($key !== null) {
                $this->uses_keys = true;
                $this->errors[$key][] = $msg;
            } else {
                $this->errors[__DEFAULT_ERROR_TRAIT_KEY][] = $msg;
            }
        }
        return $this;
    }

    /**
     * @param array<string, string[]>|string[] $errors
     */
    public function addErrors(array $errors) : self
    {
        foreach ($errors as $key => $error) {
            if (is_numeric($key)) {
                $this->addError($error);
            } else {
                if (is_array($error)) {
                    foreach ($error as $err) {
                        $this->addError($err, $key);
                    }
                } else {
                    $this->addError($error, $key);
                }
            }
        }
        return $this;
    }

    public function clearErrors() : self
    {
        $this->uses_keys = false;
        $this->errors = [];
        return $this;
    }
}
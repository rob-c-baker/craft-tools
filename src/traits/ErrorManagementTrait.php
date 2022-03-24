<?php

namespace alanrogers\tools\traits;

trait ErrorManagementTrait
{
    /**
     * @var string[]
     */
    protected array $errors = [];

    /**
     * @return string[]
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * Overwrites all errors with those passed in
     * @param array $errors
     * @return $this
     */
    public function setErrors(array $errors) : self
    {
        $this->errors = $errors;
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
     * @return $this
     */
    public function addError($msg, ?string $key=null) : self
    {
        if (is_array($msg)) {
            foreach ($msg as $m) {
                $this->addError($m, $key);
            }
        } else {
            if ($key !== null) {
                $this->errors[$key][] = $msg;
            } else {
                $this->errors[] = $msg;
            }
        }
        return $this;
    }

    /**
     * @param array<string[]> $errors
     * @return $this
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

    /**
     * @return $this
     */
    public function clearErrors() : self
    {
        $this->errors = [];
        return $this;
    }
}
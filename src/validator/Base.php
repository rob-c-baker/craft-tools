<?php

namespace alanrogers\tools\validator;

use alanrogers\tools\traits\ErrorManagementTrait;

abstract class Base implements ValidatorInterface
{
    use ErrorManagementTrait;

    /**
     * Optional set of options that can be used in concrete implementations
     * @var array
     */
    protected array $options = [];

    /**
     * @var null|mixed
     */
    protected $value = null;

    /**
     * Base constructor.
     * @param mixed $value
     * @param array $options (Optional - array of options used in concrete implementations)
     */
    public function __construct($value=null, array $options = [])
    {
        $this->value = $value;
        $this->setOptions($options);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value) : self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options) : self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function setOption(string $name, $value) : self
    {
        $this->options[$name] = $value;
        return $this;
    }

    /**
     * Sets any present class properties with values found in the $this->options array
     */
    protected function setOptionProperties() : void
    {
        foreach ($this->options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        $result = $this->validate($this->value);
        return !$this->hasErrors() && $result;
    }

    /**
     * Performs the validation in the implemented classes
     * @param $value
     * @return bool
     */
    abstract protected function validate($value) : bool;
}
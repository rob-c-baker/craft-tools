<?php
declare(strict_types=1);

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
    protected mixed $value = null;

    /**
     * Base constructor.
     * @param mixed|null $value
     * @param array $options (Optional - array of options used in concrete implementations)
     */
    public function __construct(mixed $value=null, array $options = [])
    {
        $this->value = $value;
        $this->setOptions($options, $options !== []);
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setValue(mixed $value) : self
    {
        // If setting a different value, remove errors and mark for re-validation
        if ($value !== $this->value) {
            $this->clearErrors();
        }
        $this->value = $value;
        return $this;
    }

    /**
     * @param array $options
     * @param bool $set_all_properties
     * @return $this
     */
    public function setOptions(array $options, bool $set_all_properties=false) : self
    {
        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
            if (!$set_all_properties) {
                $this->setOptionProperties($name);
            }
        }
        if ($set_all_properties) {
            $this->setOptionProperties();
        }
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
        $this->setOptionProperties($name);
        return $this;
    }

    /**
     * Sets any present class properties with values found in the $this->options array
     * @param string|null $property If provided, will only set that specific property
     */
    public function setOptionProperties(?string $property=null) : void
    {
        if ($property) {
            if (property_exists($this, $property) && isset($this->options[$property])) {
                $this->$property = $this->options[$property];
            }
        } else {
            foreach ($this->options as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
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
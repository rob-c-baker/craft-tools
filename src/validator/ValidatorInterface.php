<?php
declare(strict_types=1);

namespace alanrogers\tools\validator;

interface ValidatorInterface
{
    /**
     * Base constructor.
     * @param mixed|null $value
     * @param array $options (Optional - array of options used in concrete implementations)
     */
    public function __construct(mixed $value=null, array $options = []);

    /**
     * @return bool
     */
    public function isValid() : bool;

    /**
     * @return mixed
     */
    public function getValue(): mixed;

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value) : self;

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options) : self;

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function setOption(string $name, $value) : self;
}
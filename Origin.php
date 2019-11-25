<?php

namespace DB;

class Origin
{
    /**
     * @var null
     */
    protected $value = null;

    /**
     * Origin constructor.
     * @param $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * original value
     *
     * @return string|null
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Return original value
     *
     * @return string|null
     */
    public function __toString()
    {
        return $this->value();
    }
}
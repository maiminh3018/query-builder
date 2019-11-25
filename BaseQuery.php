<?php

namespace DB;

class BaseQuery
{
    protected $table;

    public function __construct(BaseQuery $parent = null)
    {
        if (!is_null($parent)) {
            $this->inheritParent($parent);
        }
    }

    protected function inheritParent(BaseQuery $parent)
    {
    }

    /**
     * Get all properties which created by query builder
     *
     * @return array
     */
    final public function attributes()
    {
        $attributes = get_object_vars($this);
        // Debug here
//        die(highlight_string("<?php\n" . var_dump($attributes, true)));
        return $attributes;
    }


    /**
     * Not escape value, what mean we get origin value is passed
     *
     * @param $expression
     * @return Origin
     */
    public function raw($expression)
    {
        return new Origin($expression);
    }

}
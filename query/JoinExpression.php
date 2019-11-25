<?php

namespace DB\Query;

class JoinExpression extends Select
{
    protected $where;

    protected $joinOn = [];

    public function orOn($localKey, $operator = '=', $referenceKey, $type = 'or')
    {
        $this->on($localKey, $operator, $referenceKey, $type);
        return $this;
    }

    public function andOn($localKey, $operator = '=', $referenceKey, $type = 'and')
    {
        $this->on($localKey, $operator, $referenceKey, $type);
        return $this;
    }

    public function select($columns)
    {
        $this->columns($columns);
        return $this;
    }

}
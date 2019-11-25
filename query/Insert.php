<?php

namespace DB\Query;

use DB\BaseQuery;
use DB\Builder;

class Insert extends Base
{
    protected $value;

    protected $insertIgnore = false;

    public function __construct(BaseQuery $baseQuery)
    {
        parent::__construct($baseQuery);
    }

    public function ignore($values)
    {
        $this->insertIgnore = true;
        $this->insert($values);
        return $this;
    }

    public function insert(array $values = [])
    {
        if (empty($values)) {
            return $this;
        }
        if (isset($values[0])) {
            //multiple insert
            $column = array_keys(reset($values));
            $this->value[0] = $column;
            foreach ($values as $value) {
                $value = array_values($value);
                $this->value[1][] = $value;
            }
        } else {
            //single insert
            $column = array_keys($values);
            $value = array_values($values);
            $this->value[0] = $column;
            $this->value[1][] = $value;
        }
        //fire insert statement
        $builder = new Builder;
        $builder->executeConverter($this);
        $lastInsertId = $builder->executeQuery();
        return $lastInsertId;
    }

    final public function resetValue()
    {
        $this->value = null;
    }
}
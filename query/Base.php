<?php

namespace DB\Query;

use DB\BaseQuery;

class Base extends BaseQuery
{
    /**
     * Store table name.
     *
     * @var string
     */
    protected $table;

    /**
     * Set table name
     *
     * @param $table
     * @return $this
     */
    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Inherit all properties from parent
     *
     * @param BaseQuery $parent
     */
    protected function inheritParent(BaseQuery $parent)
    {
        parent::inheritParent($parent);
        $this->table = $parent->table;
    }


}
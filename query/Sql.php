<?php

namespace DB\Query;

use DB\BaseQuery;

class Sql extends BaseQuery
{
    /**
     * Query builder must be start assoc with table first
     *
     * @param $table
     * @return object
     */
    public function table($table)
    {
        if ($table instanceof \Closure) {
            throw new \InvalidArgumentException('table must be a string or instance of Origin');
        }
        $query = new Table($this);
        return $query->table($table);
    }

}
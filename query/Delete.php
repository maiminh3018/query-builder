<?php

namespace DB\Query;

use DB\BaseQuery;
use DB\Builder;

class Delete extends Base
{
    protected $where;

    public function __construct(BaseQuery $baseQuery)
    {
        parent::__construct($baseQuery);
        $this->where = $baseQuery->attributes()['where'];
    }

    public function delete()
    {
        $builder = new Builder;
        $builder->executeConverter($this);
        $numRowsAffected = $builder->executeQuery();
        return $numRowsAffected;
    }

}
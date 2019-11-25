<?php

namespace DB\Query;

use DB\BaseQuery;
use DB\Builder;

class Update extends Base
{
    /**
     * @var
     */
    protected $conditions;
    /**
     * @var
     */
    protected $where;

    /**
     * Update constructor.
     * @param BaseQuery $baseQuery
     */
    public function __construct(BaseQuery $baseQuery)
    {
        parent::__construct($baseQuery);
        $this->where = $baseQuery->attributes()['where'];
    }

    /**
     * @param array $conditions
     * @return $this
     */
    public function update(array $conditions = [])
    {
        if (empty($conditions)) {
            return $this;
        }
        if (is_array($conditions)) {
            $this->conditions = $conditions;
            $builder = new Builder;
            $builder->executeConverter($this);
            $numRowsAffected = $builder->executeQuery();
            return $numRowsAffected;
        } else {
            throw new \InvalidArgumentException('Update condition must be an array');
        }
    }

    /**
     * @return $this
     */
    public function resetCondition()
    {
        $this->conditions = null;
        return $this;
    }
}
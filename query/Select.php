<?php

namespace DB\Query;

use DB\BaseQuery;
use DB\Builder;
use DB\Origin;
class Select extends Condition
{
    protected $distinct = false;

    protected $columns = [];

    protected $union;

    protected $join;

    protected $joinOn;

    protected $alias;

    protected $groupBy;

    protected $orderBy;

    protected $having;

    protected $havingRaw;

    protected $readOnly;

    public function __construct(BaseQuery $baseQuery = null)
    {
        parent::__construct($baseQuery);
        $this->readOnly = true;
    }

    public function distinct($distinct = true)
    {
        $this->distinct = $distinct;
        return $this;
    }

    public function columns($columns)
    {
        $this->columns = [];

        if (is_string($columns)) {
            $columns = $this->stringToArray($columns);
            if ($columns == ['*']) {
                $this->columns = '*';
                return $this;
            }
        }
        if (!isset($columns) || empty($columns)) return $this;
        if (is_object($columns)) {
            return $this->addColumn($columns);
        }
        foreach ($columns as $key => $field) {
            if (is_string($key)) {
                $this->addColumn($key, $field);
            } else {
                $this->addColumn($field);
            }
        }
        return $this;
    }

    /**
     * alias of columns function
     *
     * @param $columns
     * @return Select
     */
    public function select($columns)
    {
        return $this->columns($columns);
    }

    //TODO add column may be not use
    public function addColumn($key, $value = null)
    {
        $this->columns[] = [$key, $value];
        return $this;
    }

    public function groupBy($columns)
    {
        if (is_string($columns)) {
            $this->groupBy[] = $columns;
        } elseif (is_array($columns)) {
            foreach ($columns as $column) {
                $this->groupBy[] = $column;
            }
        }
        return $this;
    }

    public function having($column, $operator, $value, $type = 'and')
    {
        $this->having[] = [$column, $operator, $value, $type];
        return $this;
    }

    public function havingRaw($condition)
    {
        $this->havingRaw = $condition;
        return $this;
    }


    public function join($table, $localKey = null, $operator = '=', $referenceKey = null, $type = 'inner')
    {
        if (!in_array($type, ['inner', 'left', 'right'])) {
            throw new \InvalidArgumentException("Unknown join type {$type}");
        }
        //nested join
        if (is_object($table) && $table instanceof \Closure) {
            $subQuery = new JoinExpression();
            call_user_func_array($table, [&$subQuery]);
            $this->join[] = [$type, $subQuery];
            return $this;
        } elseif (is_string($table) && is_object($localKey) && $localKey instanceof \Closure) {
            $subQuery = new JoinExpression();
            call_user_func_array($localKey, [&$subQuery]);
            $this->join[] = [$type, $table, $subQuery];
            return $this;
        }
        $this->join[] = [$type, $table, $localKey, $operator, $referenceKey];
        return $this;
    }

    public function leftJoin($table, $localKey, $operator = '=', $referenceKey = null)
    {
        return $this->join($table, $localKey, $operator, $referenceKey, $type = 'left');
    }

    public function rightJoin($table, $localKey, $operator = '=', $referenceKey = null)
    {
        return $this->join($table, $localKey, $operator, $referenceKey, $type = 'right');
    }

    public function on($localKey, $operator = '=', $referenceKey, $type = 'on')
    {
        $this->joinOn[] = [$type, $localKey, $operator, $referenceKey];
        return $this;
    }

    public function union($query)
    {
        $this->union = ' union ' . $query->toString();
        return $this;
    }

    public function unionAll($query)
    {
        $this->union = ' union all ' . $query->toString();
        return $this;
    }

    public function alias($alias)
    {
        $this->alias = $alias;
        return $this;
    }

    public function orderBy($column, $type = 'asc')
    {
        $this->orderBy[] = [$column, $type];
        return $this;
    }

    public function first()
    {
        return $this->limit(1, 0)->get();
    }

    public function debug()
    {
        return (new Builder())->debug($this);
    }

    public function toString()
    {
        return (new Builder())->queryToString($this);
    }


    public function get()
    {
        $builder = new Builder;
        $builder->executeConverter($this);
        $results = $builder->executeQuery();
        return $results;
    }

}
<?php

namespace DB\Query;

use DB\Builder;
/**
 *
 * @method \DB\Query\Select get()
 * @method \DB\Query\Select toString()
 * @method \DB\Query\Select first()
 * @method \DB\Query\Select debug()
 * @method \DB\Query\Select distinct($distinct = true)
 * @method \DB\Query\Select groupBy($columns)
 * @method \DB\Query\Select having($column, $operator, $value, $type = 'and')
 * @method \DB\Query\Select havingRaw($condition)
 * @method \DB\Query\Select join($table, $localKey = null, $operator = '=', $referenceKey = null, $type = 'inner')
 */
class Condition extends Base
{
    protected $where;

    protected $offset;

    protected $limit;

    public function stringToArray($string)
    {
        $array = array_map('trim', explode(',', $string));
        return $array;
    }

    public function where($column, $operator = null, $value = null, $type = 'and')
    {
        if (!in_array($type, ['and', 'or'])) {
            throw new \InvalidArgumentException('Unknown operator where type:' . $type);
        }

        //if first time condition
        if (empty($this->where)) {
            $type = 'where';
        }
        //if array, what mean we have to many where-> recursive where
        if (is_array($column)) {
            $subQuery = new Condition();
            foreach ($column as $key => $value) {
                $subQuery->where($key, $value, null);
            }
            $this->where[] = [$type, $subQuery];
            return $this;
        }
        //if object, what mean condition has sub-query
        if (is_object($column) && $column instanceof \Closure) {
            $subQuery = new Condition();
            call_user_func_array($column, [&$subQuery]);
            $this->where[] = [$type, $subQuery];
            return $this;
        }

        if (is_object($value) && is_string($operator) && $value instanceof \Closure) {
            $subQuery = new Select();
            call_user_func_array($value, [&$subQuery]);
            $this->where[] = [$type, $column, $operator, $subQuery];
            return $this;
        }

        // if null, what mean "operator is '=' and value=operator"
        if($value === '') {
            $value = Builder::raw("''");
        }
        elseif (!$value) {
            $value = $operator;
            $operator = '=';
        }
        $this->where[] = [$type, $column, $operator, $value];
        return $this;
    }

    public function whereIn($column, array $values = [])
    {
        if (!$values) {
            return $this;
        }
        return $this->where($column, 'in', $values);
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }

    public function andWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'and');
    }

    public function whereNull($column)
    {
        return $this->where($column, 'is', $this->raw('NULL'));
    }

    public function whereNotNull($column)
    {
        return $this->where($column, 'is not', $this->raw('NULL'));
    }

    public function orWhereNull($column)
    {
        return $this->orWhere($column, 'is', $this->raw('NULL'));
    }

    public function orWhereNotNull($column)
    {
        return $this->orWhere($column, 'is not', $this->raw('NULL'));
    }

    public function whereLike($column, $value = '')
    {
        return $this->orWhere($column, 'like', '%' . $value . '%');
    }

    public function whereNotLike($column, $value = '')
    {
        return $this->orWhere($column, 'not like', '%' . $value . '%');
    }

    public function whereBetween($column, array $values = [])
    {
        if (!$values) {
            return $this;
        }
        return $this->where($column, 'between', $values);
    }

    public function whereNotBetween($column, array $values = [])
    {
        if (!$values) {
            return $this;
        }
        return $this->where($column, 'not between', $values);
    }

    public function limit($limit, $offset = null)
    {
        if ($offset) {
            $this->offset = (int)$limit;
            $this->limit = (int)$offset;
        } else {
            $this->limit = (int)$limit;
        }
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = (int)$offset;
        return $this;
    }


}
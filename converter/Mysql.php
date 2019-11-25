<?php

namespace DB\Converter;

use DB\BaseQuery;
use DB\Origin;
use DB\Query\Condition;
use DB\Query\Delete;
use DB\Query\Insert;
use DB\Query\Select;
use DB\Query\Update;

/**
 * Class Mysql
 * @package DB\Converter
 */
class Mysql extends \Db
{
    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var string
     */
    protected $escapeChar = '`%s`';

    /**
     * @var bool
     */
    protected $readOnly = false;

    /**
     * @var int
     */
    protected $flag = 0;

    /**
     * @param BaseQuery $base
     * @return bool|string
     * @throws \Exception
     */
    public function converter(BaseQuery $base)
    {
        //get all attributes
        $this->attributes = $base->attributes();
        if ($base instanceof Select) {
            $queryString = $this->converterSelect();
            $this->readOnly = true;
            $this->flag = 1;
        } elseif ($base instanceof Insert) {
            $queryString = $this->converterInsert();
            $this->flag = 2;
        } elseif ($base instanceof Update) {
            $queryString = $this->convertUpdate();
            $this->flag = 3;
        } elseif ($base instanceof Delete) {
            $queryString = $this->convertDelete();
            $this->flag = 4;
        } else {
            throw new \Exception('Unknown query type :  ' . get_class($base));
        }
        return $queryString;
    }

    /**
     * @param $query
     * @return array|int|null|void
     * @throws \Exception
     */
    public function result($query)
    {
        $conn = $this->connection();
        $exec = mysqli_query($conn, $query);
        if (!$exec) {
            if (APP_ENVIROMENT != 'production') {
                $error = "<b style=\"color:red;font-size:20px;\"> MySQL Error No: " . mysqli_errno($conn) . " - " . mysqli_error($conn) . "</b><br>";
                $error .= "\n\r $query \n\r";
                die($error);
            }
            $error = "MySQL Returned Error";
            throw new \Exception($error);
        }
        if ($this->readOnly) {
            $results = $this->fetchAll($exec);
        } else {
            switch ($this->flag) {
                case 2:
                    $results = mysqli_affected_rows($conn);
                    break;
                case 3:
                    $results = mysqli_affected_rows($conn);
                    break;
                case 4:
                    $results = mysqli_affected_rows($conn);
                    break;
            }
        }
        return $results;
    }

    /**
     * @param resource $rs
     * @param null $class
     * @return array|null|void
     */
    public function fetchAll($rs, $class = null)
    {
        if (!isset($rs->num_rows)) {
            return null;
        } else if (!$rs->num_rows) {
            return null;
        }
        $rows = array();
        while ($row = mysqli_fetch_object($rs)) {
            if ($class != null && is_object($class)) {
                $rows[] = $class->convert($row);
            } else {
                $rows[] = $row;
            }
        }
        mysqli_free_result($rs);
        return $rows;
    }

    /**
     * @return mixed
     */
    protected function connection()
    {
        $type = ($this->readOnly ? 'slave' : 'master');
        $conn = $this->getConnection($type);
        return $conn;
    }

    /**
     * @param $key
     * @param null $anotherObj
     * @return mixed
     */
    protected function getAttr($key, $anotherObj = null)
    {
        if (!$anotherObj) {
            return $this->attributes[$key];
        } else {
            return $anotherObj->attributes()[$key];
        }
    }

    /**
     * @param null $specifyObj
     * @return bool|string
     * @throws \Exception
     */
    public function converterSelect($specifyObj = null)
    {
        $alias = $this->getAttr('alias', $specifyObj);
        $query = $alias ? '(' : '';
        $columns = $this->getAttr('columns', $specifyObj);
        $distinct = $this->getAttr('distinct', $specifyObj);
        $query .= $distinct ? 'select distinct ' : 'select ';
        if ($columns == '*') {
            $query .= '*';
        } else {
            foreach ($columns as $column) {
                list($col, $alias) = $column;
                if (!is_null($alias)) {
                    $query .= $this->escape($col) . ' as ' . $this->escape($alias);
                } else {
                    $query .= $this->escape($col);
                }
                $query .= ', ';
            }

            $query = substr($query, 0, -2);

        }

        $query .= ' from ' . $this->convertFrom($specifyObj);

        if ($this->getAttr('join', $specifyObj)) {
            $query .= $this->convertJoin();
        }

        if ($this->getAttr('joinOn', $specifyObj)) {
            $query .= $this->convertJoinOn($specifyObj);
        }

        if ($where = $this->getAttr('where', $specifyObj)) {
            $query .= $this->convertWhere($where);
        }

        if ($this->getAttr('groupBy', $specifyObj)) {
            $query .= $this->convertGroupBy();
        }
        if ($this->getAttr('orderBy', $specifyObj)) {
            $query .= $this->convertOrderBy();
        }

        if ($this->getAttr('limit', $specifyObj)) {
            $query .= $this->convertLimitOffset();
        }

        if ($union = $this->getAttr('union', $specifyObj)) {
            $query .= $union;
        }
        if ($alias) {
            $query .= ') as ' . $alias;
        }

        return $query;
    }

    /**
     * @param $specifyObj
     * @return null|string
     * @throws \Exception
     */
    public function convertFrom($specifyObj)
    {
        //TODO process nested query table
        $subQuery = $this->getAttr('table', $specifyObj);
        if (is_string($subQuery)) {
            $string = $this->escape($subQuery);
            return $string;
        } elseif ($subQuery instanceof Origin) {
            return $subQuery->value();
        } elseif (is_object($subQuery)) {
            //nested query
//            $string = '('. $this->converterSelect($specifyObj). ')';
            trigger_error('use Builder::raw($opeator) to use nested table');
            throw new \Exception('Current not support nested table');
        }
        throw new \Exception('Complex table nested in Clousure current not support!');
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function convertDelete()
    {
        $string = 'delete from ' . $this->getAttr('table') . ' ';
        $string .= $this->convertWhere($this->getAttr('where'));
        return $string;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function convertUpdate()
    {
        $string = 'update ' . $this->getAttr('table') . ' set ';
        $conditions = $this->getAttr('conditions');
        foreach ($conditions as $key => $value) {
            $string .= $this->escape($key) . ' = ' . $this->extractTypeData($value) . ',';
        }
        $string = rtrim($string, ',');
        $string .= $this->convertWhere($this->getAttr('where'));
        return $string;
    }

    /**
     * @return string
     */
    protected function converterInsert()
    {
        $query = ($this->getAttr('insertIgnore') ? 'insert ignore into' : 'insert into');
        $query .= ' ' . $this->getAttr('table') . ' ';
        if ($values = $this->getAttr('value')) {
            $query .= '(' . implode(',', $this->escapeList($values[0])) . ')';
            $query .= ' values ';
            foreach ($values[1] as $value) {
                $query .= '(' . implode(',', ($this->extractTypeData($value))) . '),';
            }
        }
        $query = rtrim($query, ',');
        return $query;
    }

    /**
     * @param array $array
     * @return array
     * @throws \Exception
     */
    protected function escapeList(array $array)
    {
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $array[$key] = $this->escape($value);
            }
            if (is_null($value)) {
                $array[$key] = 'NULL';
            }
        }
        return $array;
    }

    /**
     * @param $values
     * @return array|string
     */
    protected function extractTypeData($values)
    {
        if (is_string($values)) {
            return "'" . $this->defend($values) . "'";
        }
        if (is_array($values)) {
            foreach ($values as $key => $value) {
                if (is_string($value)) {
                    $values[$key] = "'" . $this->defend($value) . "'";
                }
                if (is_null($value)) {
                    $values[$key] = 'NULL';
                }
            }
        }
        return $values;
    }

    /**
     * @param $wheres
     * @return string
     * @throws \Exception
     */
    protected function convertWhere($wheres)
    {
        $issetWhereClause = false;
        $string = ' ';
        foreach ($wheres as $where) {

            if (@is_object($where[3]) && @$where[3] instanceof Select) {
                //nested query
                $string .= $where[0] . ' ' . $this->escape($where[1]) . ' ' . $where[2] . ' ';
                $string .= '(' . $this->converterSelect($where[3]) . ')';
            } elseif (is_object($where[1]) && $where[1] instanceof Condition) {
                //nested query
                foreach ($this->getAttr('where', $where[1]) as $value) {
                    if ($where[0] == 'where' && !$issetWhereClause) {
                        $value[0] = 'where (';
                        $issetWhereClause = true;
                    } elseif ($value[0] == 'where') {
                        $value[0] = ' ' . $where[0] . ' (';
                    }
                    if (isset($value[3]) && is_string($value[3])) {
                        $value[3] = $this->defend($value[3]);
                    }
                    // check clause is normal or clousure
                    if (isset($value[3]) && (is_string($value[3]) || $value[3] instanceof Origin)) {
                        $value[1] = $this->escape($value[1]);
                        $string .= implode(' ', $value) . ' ';
                    } else {
                        $string .= $this->convertWhere([$value]);
                    }
                }
                $string .= ')';
            } elseif (isset($where[3]) && is_array($where[3])) {
                if ($where[2] == 'in') {
                    $string .= $where[0] . ' ' . $this->escape($where[1]) . ' ' . $where[2] . ' (';
                    foreach ($where[3] as $key => $value) {
                        $string .= $this->singleParamDefend($value) . ',';
                    }
                    $string = substr($string, 0, -1) . ')';
                } elseif (in_array($where[2], ['between', 'not between'])) {
                    if (count($where[3]) > 2) throw new \Exception('Only two values are accepted in where ' . $where[2]);
                    $string .= $where[0] . ' ' . $this->escape($where[1]) . ' ' . $where[2] . ' ' . $this->singleParamDefend($where[3][0]) . ' and ' . $this->singleParamDefend($where[3][1]);
                } else {
                    throw new \Exception('Unkown where clause!');
                }
            } elseif (is_string($where[2])) {
                //simple query
                $where[1] = $this->escape($where[1]);
                $where[3] = $this->singleParamDefend($where[3]);
                $string .= ' ' . implode(' ', $where) . ' ';
            } else {
                throw new \Exception('Whoops! Error query was detected!');
            }
            $issetWhereClause = true;
        }
        return $string;
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function convertJoin()
    {
        $joins = $this->getAttr('join');
        $string = '';

        foreach ($joins as $join) {
            $type = $join[0];
            $table = $join[1];
            $string .= ' ' . $type . ' join ';
            //Nested join
            if (is_object($table) && $table instanceof BaseQuery) {
                $string .= $this->converterSelect($table);
            } elseif (is_object($join[2]) && $join[2] instanceof BaseQuery) {
                $string .= $this->escape($table);
                foreach ($this->getAttr('joinOn', $join[2]) as $value) {
                    $string .= implode(' ', $value) . ' ';
                }
            } else {
                $string .= $this->escape($table) . ' on ';
                list($type, $table, $localKey, $operator, $referenceKey) = $join;
                $string .= $this->escape($localKey) . " $operator " . $this->escape($referenceKey);
            }
        }
        return $string;
    }

    /**
     * @param null $specifyObj
     * @return string
     * @throws \Exception
     */
    protected function convertJoinOn($specifyObj = null)
    {
        $joins = $this->getAttr('joinOn', $specifyObj);
        $string = '';
        foreach ($joins as $key => $value) {
            list($type, $localKey, $operator, $referenceKey) = $value;
            if ($type == 'on') {
                // join type = 'on' only used for join complex (Closure)
                $string .= ' on ' . $this->escape($localKey) . " $operator " . $this->escape($referenceKey);
            } else {
                $string .= ' ' . $type . ' on ' . $this->escape($localKey) . " $operator " . $this->escape($referenceKey);
            }
        }
        return $string;
    }

    /**
     * @return string
     */
    protected function convertGroupBy()
    {
        $string = ' group by ' . implode(',', $this->getAttr('groupBy'));
        if ($this->getAttr('having') && $this->getAttr('havingRaw')) {
            //todo need process with subquery
            throw new \BadMethodCallException('Can not use having and havingRaw in the same query');
        } elseif ($having = $this->getAttr('having')) {
            $string .= ' having ';
            $total = count($having);
            if ($total > 1) {
                foreach ($having as $key => $value) {
                    $string .= $value[0] . ' ' . $value[1] . ' ' . $value[2];
                    if ($total != $key + 1) {
                        $string .= ' and ';
                    }
                }
            } else {
                $having = $having[0];
                $string .= $having[0] . ' ' . $having[1] . ' ' . $having[2];
            }
        } elseif ($havingRaw = $this->getAttr('havingRaw')) {
            $string .= " having {$havingRaw}";
        }
        return $string;
    }

    /**
     * @return string
     */
    protected function convertOrderBy()
    {
        $orderBy = $this->getAttr('orderBy');
        $string = ' order by ';
        foreach ($orderBy as $value) {
            list($column, $type) = $value;
            $string .= $column . ' ' . $type;
        }
        return $string;
    }

    /**
     * @return string
     */
    protected function convertLimitOffset()
    {
        $string = " limit {$this->getAttr('limit')}";
        if ($this->getAttr('offset')) {
            $string .= " offset {$this->getAttr('offset')}";
        }
        return $string;
    }

    /**
     * @param $param
     * @return string
     */
    protected function singleParamDefend($param)
    {
        if (is_string($param)) {
            $param = "'" . $this->defend($param) . "'";
        }
        return $param;
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function addParams($value)
    {
        $this->params[] = $value;
        return $value;
    }

    /**
     * @param $string
     * @return string
     * @throws \Exception
     */
    protected function escape($string)
    {
        if (is_object($string)) {
            if ($this->isNotEscape($string)) {
                return $string->value();
            }
            throw new \Exception('Cannot convert object of class: ' . get_class($string));
        }
        // process 'as' clause
        if (strpos($string, ' as ') !== false) {
            $string = explode(' as ', $string);
            return $this->escape(trim($string[0])) . ' as ' . $this->escape(trim($string[1]));
        }

        // process 'white-space' clause
        if (strpos($string, ' ') !== false) {
            $string = explode(' ', $string);
            $string = array_values(array_filter($string));
            return $this->escape(trim($string[0])) . ' ' . $this->escape(trim($string[1]));
        }

        // process '.' clause
        if (strpos($string, '.') !== false) {
            $string = explode('.', $string);
            foreach ($string as $key => $item) {
                $string[$key] = $this->escapeString($item);
            }
            return implode('.', $string);
        }

        return $this->escapeString($string);
    }

    /**
     * @param $string
     * @return string
     */
    protected function escapeString($string)
    {
        if ($string == '*') return '*';
        return sprintf($this->escapeChar, $string);
    }

    /**
     * @param $expression
     * @return bool
     */
    protected function isNotEscape($expression)
    {
        return $expression instanceof Origin;
    }

    /**
     * @param $input
     * @return string
     */
    protected function defend($input)
    {
        return mysqli_real_escape_string($this->connection(), $input);
    }

}
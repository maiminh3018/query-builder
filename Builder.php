<?php

namespace DB;

use DB\Converter\Mysql;
use DB\Query\Sql;

/**
 * @method static \DB\Query\Table table($table)
 */
class Builder
{
    protected static $driver = [
        'mysql' => [],
        'pdo' => [],
    ];

    protected static $builder;

    protected $converter;

    private $queryString;

    public function __construct()
    {
        $this->converter = new Mysql();
        self::$builder = new Sql();
    }

    public static function __callStatic($method, $arguments)
    {
        return call_user_func_array(array(self::$builder, $method), $arguments);
    }

    public static function raw($expression)
    {
        return new Origin($expression);
    }

    public function executeConverter(BaseQuery $baseQuery)
    {
        return $this->queryString = $this->converter->converter($baseQuery);
    }

    public function executeQuery()
    {
        return $this->converter->result($this->queryString);
    }

    public function queryToString(BaseQuery $baseQuery)
    {
        $query = $this->converter->converter($baseQuery);
        return $query;
    }

    public function debug(BaseQuery $baseQuery)
    {
        $query = $this->converter->converter($baseQuery);
        if (is_string($query)) {
            die($query);
        }
        die(var_dump($query));
    }


}
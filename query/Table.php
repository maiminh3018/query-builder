<?php

namespace DB\Query;

class Table extends Condition
{

    /**
     * @param $columns
     * @return $this
     */
    public function select($columns)
    {
        $select = new Select($this);
        return $select->columns($columns);
    }

    /**
     * @param array $values
     * @return $this
     */
    public function insert(array $values = [])
    {
        $insert = new Insert($this);
        return $insert->insert($values);
    }

    /**
     * @param array $values
     * @return $this
     */
    public function insertIgnore(array $values = [])
    {
        $insert = new Insert($this);
        return $insert->ignore($values);
    }

    /**
     * @param $column
     * @param null $operator
     * @param null $value
     * @param string $type
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $type = 'and')
    {
        return parent::where($column, $operator, $value, $type);
    }

    /**
     * @param array $condition
     * @return $this
     */
    public function update(array $condition = [])
    {
        $update = new Update($this);
        return $update->update($condition);
    }

    /**
     * @return $this
     */
    public function delete()
    {
        $update = new Delete($this);
        return $update->delete();
    }

}
**Usage**
- This library use for MySQL, you can use PDO adapter to custome or any other database type
* Namespace: DB\Builder;

- Initialize ``$DB = new Builder();``

**Query**


**Select**

```php
Builder::table('table')->select('*')->get()
```

- Columns can be: ``['*'], ['col1', 'table.col2', 'table.col3 as column3', 'table.col4 column4']``

- Distinct:
```php
Builder::table('table')->select('*')->distinct()->get()
```

- Union: 
```php
$union = Builder::table('table.column new_column')->select('*');
$results = Builder::table('table.column_2')->select('*')->whereLike('contact_name', 'Y')->union($union)->get();
```

- Advanced function calculator:
``Supported. use Builder::raw('count(*) as total'); ...``

- Table:
```php
$table = Builder::table('table_name as table')->select('*')->get()
```

- Nested table
``"$nestedQuery = Builder::table('table.column new_column')->select('*')->alias('a')->toString();
  $result = Builder::table(Builder::raw($nestedQuery))->select('user_id')->where('user_id','<',100)->get();"``
  
  - result: ``SELECT `user_id` FROM (SELECT * FROM `publisher`.`ox_users` `ox_users`) AS a WHERE `user_id` < 100``

**Condition**

- Where
``Builder::table('table')->select('*')->where('col1','=','val1')->get()``

  - operator can be: '=, >, <, >=, <=, <>, and, or, like, not like, in, not in, ...
  - default operator is '=' where('col1','val1')

- Type where
``"andWhere, orWhere, andNotWhere, orNotWhere, whereIn($col, [$val1, $val2,...]), WhereNotIn
  whereLike, whereNotLike, whereNull, whereNotNull
  whereBetween/whereNotBetween($col, [$range1, $range2])"``

- Nested Where simple
```php
Builder::table('users')
    ->select('*')
    ->where(function ($query) {
            $query->where('col1','col2')->orWhere('col1','>', 'col2');
        })->orWhere('col_x','valX')
    ->get()
```

- Nested Where complex

```php
Builder::table('publisher.ox_users ox_users')
    ->select('*')
    ->join('publisher_user as b', 'ox_users.user_id', '=', 'b.userid')
    ->where('b.contact_name', 'Administrator')
    ->where('user_id', '=', function ($query) {
        $query->table('ox_users')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->limit(1)
            ->orWhere('user_id', '=', function ($query) {
                $query->table('ox_users')
                    ->select('user_id')
                    ->whereNotNull('user_id')
                    ->limit(1);
            })->andWhere('user_id', 'a');
    })
    ->where('b.contact_name', 'Administrator')
    ->orWhere('email_address', 'a')
    ->where('password', 'a')
    ->where('b.active', 1)
    ->limit(1)
    ->get()
```

- Join

``"Builder::table('table')->select('*')
              ->join('publisher_user as b', 'ox_users.user_id', '=', 'b.userid')->get();"``
  - Type Join: leftJoin, rightJoin, innerJoin

- Nested Join type 2
```php
Builder::table((Builder::raw('g_user_role')))
    ->select(['*', Builder::raw('count(*) as total')])
    ->join(function ($query) {
        $query
            ->table('ox_users')
            ->select('*')
            ->where('ox_users.user_id', 'b')
            ->alias('b');
    })->on('g_user_role.user_id', '=', 'b.user_id')
    ->whereNotNull('contact_name', 'Administrator')
    ->limit(3)
    ->offset(1)
    ->get()
```
  - result: 
  ```mysql
  SELECT 
      *, COUNT(*) AS total
  FROM
      g_user_role
          INNER JOIN
      (SELECT 
          *
      FROM
          `ox_users`
      WHERE
          `ox_users`.`user_id` = 'b') AS b ON `g_user_role`.`user_id` = `b`.`user_id`
  WHERE
      `contact_name` IS NOT NULL
  LIMIT 3 OFFSET 1
  ```

- Nested Join type 1
```php
Builder::table('users')
    ->select('*')
    ->join('contacts', function ($join) {
        $join->on('users.id', '=', 'contacts.user_id')
            ->orOn(...)
            ->andOn(...);
    })
    ->get();
```
  - result ``select * from `users` inner join `contacts`on users.id = contacts.user_id``

- "orderBy, limit, offset"
```php
Builder::table('users')
    ->select('*')
    ->orderBy('col', 'asc')
    ->limit(3)
    ->offset(1)
    ->get()
```

- "Group by, having"
```php
Builder::table('ox_users')
    ->select(['language','user_id', Builder::raw(""count(*) as total"")])
    ->groupBy('language')
    ->having('user_id', '>', 2)
    ->get()
    
// or use havingRaw ex. ->havingRaw('user_id > 2')
Builder::table('ox_users')
    ->select(['language','user_id', Builder::raw(""count(*) as total"")])
    ->groupBy('language')
    ->havingRaw('user_id > 2')
    ->get()
```
  - result: ``select `language`, `user_id`, count(*) as total from `ox_users` group by language having user_id > 2``
  
- Original query without process

    ``Builder::raw($query)``

- Query debug:
  - use debug() to end program and show query string,
  - both of them does not execute until we call get() method
  - **Note:** when use ``insert, delete, update``, the query was executed before program debug
  
- Insert
```php
Insert 1 raw
Builder::table('user_role')
    ->insert([
        'user_id' => 1,
        'role' => 1,
        'remember_me' => null
    ])
    
Insert multi raw
Builder::table('usser_role')
    ->insert([
        [
            'user_id' => 1,
            'role' => 1,
            'remember_me' => null
        ],
        [
            [
                'user_id' => 2,
                'role' => 1,
                'remember_me' => null
            ]
        ]
    ])
```

- Insert Ignore
```php
Builder::table('user_role')
    ->insertIgnore([
        'user_id' => 1,
        'role' => 1,
        'remember_me' => null

    ]);
```
  - We can insert multi like **Insert**
  
- Update
```php
Builder::table('user_notification')
    ->where('notification_id', 11)
    ->orWhere('notification_id', 1)
    ->update(['user_id' => 1, 'created_date' => '0000-00-00 00:00:01'])
```
  - result
    ```
    UPDATE g_user_notification_user_read 
    SET 
        user_id = 1,
        created_date = '0000-00-00 00:00:01'
    WHERE
        `notification_id` = 11
            OR `notification_id` = 1
    ```

- Delete
```php
Builder::table('g_user_notification_user_read')
    ->where('notification_id', 11)
    ->orWhere('notification_id', 1)
    ->delete()
```

  - result:
  ```php
  DELETE FROM g_user_notification_user_read 
  WHERE
      `notification_id` = 11
      OR `notification_id` = 1
```  

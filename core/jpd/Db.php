<?php

namespace core\jpd;

use PDO;

/**
 * Db 数据库类
 * @package core\jpd
 */
class Db
{
    // 连接句柄
    private $link;
    // 配置
    private $config;
    // 结果集
    private $query;
    // SQL
    private $sql;
    // 是否输出SQL
    private $traceSql;
    // 表名
    private $table;
    // 字段数组
    private $field = [];
    // 查询条件数组
    private $where = [];
    // 排序数组
    private $order = [];
    // 分组数组
    private $group = [];
    // 分页数组
    private $limit;
    // 插入数组数组
    private $insert = [];
    // 更新数据数组
    private $update = [];
    // 关联数组
    private $join = [];
    // 函数
    private $letter = ['IN', 'ANY', 'SOME', 'ALL'];
    // 比较运算符
    private $compare = [
        'LT' => '<',
        'LE' => '<=',
        'EQ' => '=',
        'NE' => '!=',
        'GE' => '>=',
        'GT' => '>',
    ];

    /**
     * 构造函数
     * @access public
     * @param array $config 数据库的配置信息
     * @return \PDO
     */
    public function __construct($config = [])
    {
        $this->config = array_merge(Config::get('database'), $config);

        $DSN = (!isset($this->config['dsn']) || empty($this->config['dsn'])) ? $this->config['type'] . ':host=' .
        $this->config['hostname'] . ';dbname=' . $this->config['database'] : $this->config['dsn'];

        $this->link = new PDO($DSN, $this->config['username'], $this->config['password'], [\PDO::ATTR_PERSISTENT => false]);

        return $this->link;
    }

    /**
     * 设置表名
     * @access public
     * @param string $table 表名
     * @param string $alias 别名
     * @return \core\jpd\Db
     */
    public function table($table, $alias = '')
    {
        $alias && $alias = ' AS ' . $alias;
        $this->table     = ' `' . $this->config['prefix'] . $table . '`' . $alias;
        return $this;
    }

    /**
     * 多表查询
     * @access public
     * @param string $table     表名
     * @param string $alias     别名
     * @param array  $condition 字段一维数组, 默认等于
     * @param string $tag       标签: LEFT|RIGHT|INNER 默认LEFT
     * @return \core\jpd\Db
     */
    public function join($table, $alias = '', $condition = [], $tag = 'LEFT')
    {
        $join['prefix'] = ' ' . strtoupper($tag) . ' JOIN';
        $join['table']  = ' `' . $this->config['prefix'] . $table . '`';
        $join['alias']  = $alias ? ' AS ' . $alias : '';
        if (is_array($condition)) {
            $join['condition'] = [
                'prefix' => 'ON',
                'data'   => $condition,
            ];
        }

        array_push($this->join, $join);

        return $this;
    }

    /**
     * 返回SQL语句
     * @access public
     * @param boolean $trace 布尔值：true为返回，false为不返回
     * @return \core\jpd\Db
     */
    public function getSql($trace = false)
    {
        $this->traceSql = $trace;
        return $this;
    }

    /**
     * 查询条件
     * 例子：where('`id`', '=', 1);
     * @access public
     * @param string $field     字段名
     * @param string $condition 表达式符号
     * @param string $value     值
     * @return \core\jpd\Db
     */
    public function where($field, $condition = '=', $value = '')
    {
        // 为空则添加WHERE前缀
        if (empty($this->where)) {
            $this->where['prefix'] = 'WHERE';
        }

        // 为字符串，则设置单个条件
        if (is_string($field)) {
            if ('' !== $value) {
                $condition = strtoupper($condition);

                $this->where['conditions'][$field]['value'] = (false !== array_search($condition, $this->letter)) ? "($value)" : $value;

                $this->where['conditions'][$field]['condition'] = in_array($condition, array_keys($this->compare)) ? $this->compare[$condition] : $condition;
            } else {
                $this->where['conditions'][$field]['condition'] = '=';
                $this->where['conditions'][$field]['value']     = $condition;
            }
            $strpos = strpos($field, '.');
            $fields = false !== $strpos ?
            str_replace('.', '_', $field) : $field;
            $this->where['conditions'][$field]['field'] = ':' . $fields;
        }

        // 为数组，则批量设置条件
        if (is_array($field) && 1 <= count($field)) {
            foreach ($field as $key => $condition) {
                $this->where['conditions'][$key]['field'] = ':' . $key;
                if (is_array($condition)) {
                    if (2 == count($condition)) {
                        $condition[0] = strtoupper($condition[0]);
                        if (is_array($condition[1])) {
                            $this->where['conditions'][$key]['field'] = [];
                            foreach ($condition[1] as $item) {
                                array_push($this->where['conditions'][$key]['field'], ':sqlWhere_' . $key . '_' . $item);
                                $this->where['conditions'][$key]['value'][':sqlWhere_' . $key . '_' . $item] = $item;
                            }
                        } else {
                            $this->where['conditions'][$key]['value'] = (false !== array_search($condition[0], $this->letter)) ?
                            "({$condition[1]})" : $condition[1];
                        }
                        $this->where['conditions'][$key]['condition'] = in_array($condition[0], array_keys($this->compare)) ?
                        $this->compare[$condition[0]] : $condition[0];
                    } else {
                        $this->where['conditions'][$key]['condition'] = '=';
                        $this->where['conditions'][$key]['value'] = $condition[0];
                    }
                } else {
                    $this->where['conditions'][$key]['condition'] = '=';
                    $this->where['conditions'][$key]['value']     = $condition;
                }
            }
        }
        return $this;
    }

    /**
     * 指定获取的字段
     * 例子：field('`*`') / field('`id`, `username`')
     * @access public
     * @param string $field 字段名
     * @return \core\jpd\Db
     */
    public function field($field)
    {
        if ('*' === $field) {
            $this->field = '';
            return $this;
        }
        $field       = array_values(array_filter(explode(',', $field)));
        $this->field = array_map(function ($i) {
            $strpos    = strpos($i, '.');
            $fieldTemp = false !== $strpos ?
            substr_replace($i, '`', ($strpos + 1), 0) . '`' :
            '`' . trim($i) . '`';
            return str_replace(':', '` AS `', $fieldTemp);
        }, $field);

        return $this;
    }

    /**
     * 排序
     * 例子：order('`id` DESC, `time` DESC')
     * @access public
     * @param string $order 排序内容
     * @return \core\jpd\Db
     */
    public function order($order = '')
    {
        // 为空，则添加ORDER BY前缀
        if (empty($this->order)) {
            $this->order['prefix'] = 'ORDER BY';
        }

        $order = array_values(array_filter(explode(',', $order)));
        foreach ($order as $i) {
            $i                                         = array_values(array_filter(explode(' ', $i)));
            $this->order['conditions'][$i[0]]['field'] = ":{$i[0]}_order";
            $this->order['conditions'][$i[0]]['order'] = isset($i[1]) ? strtoupper($i[1]) : 'ASC';
        }

        return $this;
    }

    /**
     * 分组
     * 例子：group(`id`)
     * @access public
     * @param string $group 分组内容
     * @return \core\jpd\Db
     */
    public function group($group = '')
    {
        // 为空，则添加GROUP前缀
        if (empty($this->group)) {
            $this->group['prefix'] = 'GROUP BY';
        }

        $group = array_values(array_filter(explode(',', $group)));
        foreach ($group as $i) {
            $strpos = strpos($i, '.');
            $field  = false !== $strpos ?
            substr_replace($i, '`', $strpos + 1, 0) . '`' :
            '`' . trim($i) . '`';
            $this->group['conditions'][$i] = $field;
        }

        return $this;
    }

    /**
     * 分页获取
     * 例子：limit(0, 10): 获取0-10条记录 / limit(10): 获取以开头为开始的10条记录
     * @access public
     * @param integer $offset 偏移量或记录数
     * @param string  $limit  记录数
     * @return \core\jpd\Db
     */
    public function limit($offset, $limit = '')
    {
        // 为空，则添加LIMIT前缀
        if (empty($this->limit)) {
            $this->limit['prefix'] = 'LIMIT';
        }

        if (empty($limit)) {
            $this->limit['sql'] = $offset;
        } else {
            $this->limit['sql'] = $offset . ', ' . $limit;
        }

        return $this;
    }

    /**
     * 执行SQL
     * 例子: query('SELECT * FROM `user`')
     * @access public
     * @param string $sql SQL语句
     * @return \core\jpd\Db
     * @throws Exception
     */
    public function query($sql = '')
    {
        $this->query = $this->link->prepare($sql);

        // 生成SQL
        $this->getTraceSql($sql);

        // 返回SQL
        if ($this->traceSql) {
            return $this->sql;
        }

        $res = $this->query->execute();

        if (!$res) {
            Log::log('ERROR MESSAGE: ' . ($this->query->errorInfo())[2] . ', ERROR SQL: ' . $this->sql, 'SQL');
            throw new Exception('Error Message: ' . ($this->query->errorInfo())[2]);
        }

        return $this->query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 查询单条记录
     * @access public
     * @return mixed
     * @throws Exception
     */
    public function find()
    {
        // 格式化
        $this->format();

        $sqlWhere  = isset($this->where['sql']) ? $this->where['sql'] : '';
        $sqlOrder  = isset($this->order['sql']) ? $this->order['sql'] : '';
        $sqlJoin   = isset($this->join['sql']) ? $this->join['sql'] : '';
        $dataWhere = isset($this->where['data']) ? $this->where['data'] : [];

        $sql = "SELECT{$this->field} FROM{$this->table}{$sqlJoin}{$sqlWhere}{$this->group}{$sqlOrder} LIMIT 1";

        // 批处理
        $this->query = $this->link->prepare($sql);

        // 填充变量
        $executeData = array_merge($dataWhere);

        // 生成SQL
        $this->getTraceSql($sql, $executeData);

        // 返回SQL
        if ($this->traceSql) {
            return $this->sql;
        }

        $res = $this->query->execute($executeData);

        // 清空数据
        $this->destory();

        if (!$res) {
            Log::log('ERROR MESSAGE: ' . ($this->query->errorInfo())[2] . ', ERROR SQL: ' . $this->sql, 'SQL');
            throw new Exception('Error Message: ' . ($this->query->errorInfo())[2]);
        }

        return $this->query->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * 查询数据
     * @access public
     * @return mixed
     * @throws Exception
     */
    public function select()
    {
        // 格式化
        $this->format();

        $sqlWhere  = isset($this->where['sql']) ? $this->where['sql'] : '';
        $sqlOrder  = isset($this->order['sql']) ? $this->order['sql'] : '';
        $sqlJoin   = isset($this->join['sql']) ? $this->join['sql'] : '';
        $dataWhere = isset($this->where['data']) ? $this->where['data'] : [];

        $sql = "SELECT{$this->field} FROM{$this->table}{$sqlJoin}{$sqlWhere}{$this->group}{$sqlOrder}{$this->limit}";

        // 批处理
        $this->query = $this->link->prepare($sql);

        // 填充变量
        $executeData = array_merge($dataWhere);

        // 生成SQL
        $this->getTraceSql($sql, $executeData);

        // 返回SQL
        if ($this->traceSql) {
            return $this->sql;
        }

        $res = $this->query->execute($executeData);

        // 清空数据
        $this->destory();

        if (!$res) {
            Log::log('ERROR MESSAGE: ' . ($this->query->errorInfo())[2] . ', ERROR SQL: ' . $this->sql, 'SQL');
            throw new Exception('Error Message: ' . ($this->query->errorInfo())[2]);
        }

        return $this->query->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 插入数据
     * @access public
     * @param array $data 插入的字段数组
     * @return mixed
     * @throws Exception
     */
    public function insert($data = [])
    {
        if (!empty($data)) {
            $this->insert = $data;
        }

        // 格式化
        $this->format();

        $fieldInsert = isset($this->insert['field']) ? $this->insert['field'] : '';
        $valueInsert = isset($this->insert['value']) ? $this->insert['value'] : '';
        $dataInsert  = isset($this->insert['data']) ? $this->insert['data'] : [];

        $sql = "INSERT INTO{$this->table}{$fieldInsert} VALUES{$valueInsert}";

        // 预处理
        $this->query = $this->link->prepare($sql);

        // 填充变量
        $executeData = array_merge($dataInsert);

        // 生成SQL
        $this->getTraceSql($sql, $executeData);

        // 返回SQL
        if ($this->traceSql) {
            return $this->sql;
        }

        $res = $this->query->execute($executeData);
        // 清空数据
        $this->destory();

        if (!$res) {
            Log::log('ERROR MESSAGE: ' . ($this->query->errorInfo())[2] . ', ERROR SQL: ' . $this->sql, 'SQL');
            throw new Exception('Error Message: ' . ($this->query->errorInfo())[2]);
        }

        return $this->link->lastInsertId();
    }

    /**
     * 更新数据
     * @access public
     * @param array $data 更新字段数组
     * @return mixed
     * @throws Exception
     */
    public function update($data = [])
    {
        if (!empty($data)) {
            $this->update = $data;
        }

        // 格式化
        $this->format();

        $sqlUpdate  = isset($this->update['sql']) ? $this->update['sql'] : '';
        $sqlWhere   = isset($this->where['sql']) ? $this->where['sql'] : '';
        $dataUpdate = isset($this->update['data']) ? $this->update['data'] : [];
        $dataWhere  = isset($this->where['data']) ? $this->where['data'] : [];

        $sql = "UPDATE{$this->table} SET{$sqlUpdate}{$sqlWhere}";

        // 预处理
        $this->query = $this->link->prepare($sql);

        // 填充变量
        $executeData = array_merge($dataUpdate, $dataWhere);

        // 生成SQL
        $this->getTraceSql($sql, $executeData);

        // 返回SQL
        if ($this->traceSql) {
            return $this->sql;
        }

        $res = $this->query->execute($executeData);
        // 清空数据
        $this->destory();

        if (!$res) {
            Log::log('ERROR MESSAGE: ' . ($this->query->errorInfo())[2] . ', ERROR SQL: ' . $this->sql, 'SQL');
            throw new Exception('Error Message: ' . ($this->query->errorInfo())[2]);
        }

        return $this->query->rowCount();
    }

    /**
     * 删除数据
     * @access public
     * @return mixed
     * @throws Exception
     */
    public function delete()
    {
        // 格式化
        $this->format();

        $sqlWhere  = isset($this->where['sql']) ? $this->where['sql'] : [];
        $dataWhere = isset($this->where['data']) ? $this->where['data'] : [];

        $sql = "DELETE FROM{$this->table}{$sqlWhere}";

        // 批处理
        $this->query = $this->link->prepare($sql);

        // 填充变量
        $executeData = array_merge($dataWhere);

        // 生成SQL
        $this->getTraceSql($sql, $executeData);

        // 返回SQL
        if ($this->traceSql) {
            return $this->sql;
        }

        $res = $this->query->execute($executeData);

        // 清空数据
        $this->destory();

        if (!$res) {
            Log::log('ERROR MESSAGE: ' . ($this->query->errorInfo())[2] . ', ERROR SQL: ' . $this->sql, 'SQL');
            throw new Exception('Error Message: ' . ($this->query->errorInfo())[2]);
        }

        return $this->query->rowCount();
    }

    /**
     * 根据参数绑定组装最终的SQL语句 便于调试
     * @access private
     * @param string $sql  带参数绑定的sql语句
     * @param array  $bind 参数绑定列表
     * @return string
     */
    private function getTraceSql($sql, array $bind = [])
    {
        if ($bind) {
            $this->sql = trim(str_replace(array_keys($bind), array_values($bind), $sql));
        }
        return $this->sql;
    }

    /**
     * 格式化
     * @access private
     * @return \core\jpd\Db
     * @throws Exception
     */
    private function format()
    {
        // TABLE
        if (empty($this->table)) {
            throw new Exception('数据表名为空');
        }
        // FIELD
        $this->formatField($this->field);
        // ORDER BY
        $this->formatOrder($this->order);
        // GROUP BY
        $this->formatGroup($this->group);
        // LIMIT
        $this->formatLimit($this->limit);
        // WHERE
        $this->formatWhere($this->where);
        // UPDATE
        $this->formatUpdate($this->update);
        // INSERT
        $this->formatInsert($this->insert);
        // JOIN
        $this->formatJoin($this->join);

        return $this;
    }

    /**
     * FIELD 格式化
     * @access private
     * @param array $field
     */
    private function formatField(&$field)
    {
        $field = ' ' . (empty($field) ? '*' : implode(', ', $field));
    }

    /**
     * ORDER BY 格式化
     * @access private
     * @param array $order
     */
    private function formatOrder(&$order)
    {
        if (empty($order)) {
            $order = '';
        } else {
            $sql = '';
            foreach ($order['conditions'] as $field => $condition) {
                $strpos = strpos($field, '.');
                $field  = false !== $strpos ?
                substr_replace($field, '`', $strpos + 1, 0) . '`' :
                '`' . $field . '`';
                $sql .= "{$field} {$condition['order']}, ";
            }
            $sql   = ' ' . $order['prefix'] . ' ' . rtrim($sql, ', ');
            $order = ['sql' => $sql];
        }
    }

    /**
     * GROUP 格式化
     * @access private
     * @param array $group
     */
    private function formatGroup(&$group)
    {
        $group = empty($group) ? '' : ' ' . $group['prefix'] . ' ' . implode(', ', array_values($group['conditions']));
    }

    /**
     * LIMIT 格式化
     * @access private
     * @param array $limit
     */
    private function formatLimit(&$limit)
    {
        $limit = empty($limit) ? '' : ' ' . $limit['prefix'] . ' ' . $limit['sql'];
    }

    /**
     * WHERE 格式化
     * @access private
     * @param array $where
     */
    private function formatWhere(&$where)
    {
        if (empty($where)) {
            $where = '';
        } else {
            $data = [];
            $sql  = '';
            foreach ($where['conditions'] as $field => $condition) {
                $strpos = strpos($field, '.');
                $field  = false !== $strpos ?
                substr_replace($field, '`', $strpos + 1, 0) . '`' :
                '`' . $field . '`';
                if (is_array($condition['field'])) {
                    $condition['field'] = '(' . implode(', ', $condition['field']) . ')';
                    $sql .= "{$field} {$condition['condition']} {$condition['field']} AND ";
                    $data = array_merge($data, $condition['value']);
                } else {
                    if (!isset($condition['condition'])) {
                        var_dump($condition);die;
                    }
                    $sql .= "{$field} {$condition['condition']} {$condition['field']} AND ";
                    $data[$condition['field']] = $condition['value'];
                }
            }
            $sql   = ' ' . $where['prefix'] . ' ' . rtrim($sql, ' AND ');
            $where = [
                'sql'  => $sql,
                'data' => $data,
            ];
        }
    }

    /**
     * UPDATE 格式化
     * @access private
     * @param array $update
     */
    private function formatUpdate(&$update)
    {
        if (empty($update)) {
            $update = '';
        } else {
            if (is_array($update)) {
                $data = [];
                $sql  = '';
                foreach ($update as $field => $value) {
                    $sql .= "`{$field}` = :{$field}, ";
                    $data[':' . $field] = $value;
                }
                $sql    = ' ' . rtrim($sql, ', ');
                $update = [
                    'sql'  => $sql,
                    'data' => $data,
                ];
            }
        }
    }

    /**
     * INSERT DATA 格式化
     * @param array $insert
     * @throws Exception
     */
    private function formatInsert(&$insert)
    {
        if (empty($insert)) {
            $insert = '';
        } else {
            if (is_array($insert)) {
                $data  = [];
                $field = $value = '';
                $type  = checkArrayType($insert);

                if (1 == $type) {
                    // 索引数组
                    $fieldData = $valueData = [];
                    foreach ($insert as $key => $insertData) {
                        foreach ($insertData as $i => $v) {
                            $field .= "`{$i}`, ";
                            $value .= ":{$i}_$key, ";
                            $data[':' . $i . '_' . $key] = $v;
                        }
                        !$fieldData && array_push($fieldData, '(' . rtrim($field, ', ') . ')');
                        array_push($valueData, '(' . rtrim($value, ', ') . ')');
                        $field = $value = '';
                    }
                    $insert = [
                        'field' => ' ' . implode(', ', $fieldData),
                        'value' => ' ' . implode(',', $valueData),
                        'data'  => $data,
                    ];
                } elseif (2 == $type) {
                    // 关联数组
                    foreach ($insert as $key => $val) {
                        $field .= "`{$key}`, ";
                        $value .= ":{$key}, ";
                        $data[':' . $key] = $val;
                    }
                    $insert = [
                        'field' => ' (' . rtrim($field, ', ') . ')',
                        'value' => ' (' . rtrim($value, ', ') . ')',
                        'data'  => $data,
                    ];
                } else {
                    throw new Exception('批量添加数据不能是混合数组');
                }
            } else {
                throw new Exception('添加数据必须是数组');
            }
        }
    }

    /**
     * JOIN 格式化
     * @access private
     * @param array $join
     */
    private function formatJoin(&$join)
    {
        if (empty($join)) {
            $join = '';
        } else {
            $str = '';
            foreach ($join as $key => $value) {
                $str .= $value['prefix'] . $value['table'] . $value['alias'] . ' ' . $value['condition']['prefix'] . ' ';
                $arr = array_map(function ($item) {
                    $strpos = strpos($item, '.');
                    $field  = false !== $strpos ?
                    substr_replace($item, '`', $strpos + 1, 0) . '`' :
                    '`' . $item . '`';
                    return $field;
                }, $value['condition']['data']);
                $str .= implode(' = ', $arr);
            }
            $join = [
                'sql' => $str,
            ];
        }
    }

    /**
     * 清空缓存
     * @access private
     * @return \core\jpd\Db
     */
    private function destory()
    {
        $this->field = $this->where = $this->order = $this->group = $this->update = $this->insert = [];
        $this->limit = '';
        return $this;
    }

    /**
     * 返回实例化对象
     * @access public
     * @return \PDO
     */
    public function getInstance()
    {
        return $this->link;
    }

    /**
     * 析构函数
     * @access public
     */
    public function __destruct()
    {
        $this->link = null;
    }
}

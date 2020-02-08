<?php

namespace core\jpd;

/**
 * Model 模型类
 * @package core\jpd
 */
class Model
{
    // 实例化句柄数组
    protected static $links = [];
    // 配置信息
    protected static $config = [];
    // 命名空间
    protected $namespace;
    // 模型名
    protected $model;
    // 类名
    protected $class;
    // 表名
    protected $table;
    // 前缀
    protected $prefix;

    /**
     * 构造函数
     */
    public function __construct()
    {
        // 获取数据库配置
        self::$config = Config::get('database');

        // 当前类名
        $this->class = get_called_class();

        // 分解
        $classArr = explode('\\', trim($this->class, '\\'));

        // 模型名
        $this->model = array_pop($classArr);

        // 命名空间
        $this->namespace = '\\' . implode('\\', $classArr);

        // 前缀
        $this->prefix = self::$config['prefix'];

        // 表名
        $this->table = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $this->model));

        // 数据库连接
        if (!isset(self::$links[$this->class])) {
            self::$links[$this->class] = new Db(self::$config);
        }

        // 调用初始化
        $this->initialize();
    }

    /**
     *  初始化函数
     */
    public function initialize()
    {
        self::$links[$this->class]->table($this->table);
    }

    /**
     * 设置表名
     * @access public
     * @param string $name 表名
     * @param string $alias 别名
     * @return \core\jpd\Model
     */
    public function table($name, $alias = '')
    {
        self::$links[$this->class]->table($name, $alias);
        return $this;
    }

    /**
     * 返回SQL语句
     * @access public
     * @param boolean $trace 布尔值：true为返回，false为不返回
     * @return \core\jpd\Model
     */
    public function getSql($trace = false)
    {
        self::$links[$this->class]->getSql($trace);
        return $this;
    }

    /**
     * 多表查询
     * @access public
     * @param string $table     表名
     * @param string $alias     别名
     * @param array  $condition 字段一维数组, 默认等于
     * @param string $tag       标签: LEFT|RIGHT|INNER 默认LEFT
     * @return \core\jpd\Model
     */
    public function join($table, $alias = '', $condition = [], $tag = 'LEFT')
    {
        self::$links[$this->class]->join($table, $alias, $condition, $tag);
        return $this;
    }

    /**
     * 查询数据
     * @access public
     * @return mixed
     */
    public function select()
    {
        return self::$links[$this->class]->select();
    }

    /**
     * 更新数据
     * 例子：update(['name' => 'newName', 'value' => 'newValue']);
     * @access public
     * @param array $data 更新的数据数组
     * @return mixed
     */
    public function update($data)
    {
        return self::$links[$this->class]->update($data);
    }

    /**
     * 删除数据
     * @access public
     * @return mixed
     */
    public function delete()
    {
        return self::$links[$this->class]->delete();
    }

    /**
     * 查询单条数据
     * @access public
     * @return mixed
     */
    public function find()
    {
        return self::$links[$this->class]->find();
    }

    /**
     * 插入数据
     * @access public
     * @param array $data 插入的数据
     * @return mixed
     */
    public function insert($data)
    {
        return self::$links[$this->class]->insert($data);
    }

    /**
     * 查询条件
     * 例子：where('`id`', '=', 1);
     * @access public
     * @param string $field     字段名
     * @param string $condition 表达式符号
     * @param string $value     值
     * @return \core\jpd\Model
     */
    public function where($field, $condition = '', $value = '')
    {
        self::$links[$this->class]->where($field, $condition, $value);
        return $this;
    }

    /**
     * 分页获取
     * 例子：limit(0, 10): 获取0-10条记录 / limit(10): 获取以开头为开始的10条记录
     * @access public
     * @param integer $offset 偏移量或记录数
     * @param string  $limit  记录数
     * @return \core\jpd\Model
     */
    public function limit($offset, $limit = '')
    {
        self::$links[$this->class]->limit($offset, $limit);
        return $this;
    }

    /**
     * 排序
     * 例子：order('`id` DESC, `time` DESC')
     * @access public
     * @param string $order 排序内容
     * @return \core\jpd\Model
     */
    public function order($order = '')
    {
        self::$links[$this->class]->order($order);
        return $this;
    }

    /**
     * 分组
     * 例子：group(`id`)
     * @access public
     * @param string $group 分组内容
     * @return \core\jpd\Model
     */
    public function group($group = '')
    {
        self::$links[$this->class]->order($group);
        return $this;
    }

    /**
     * 指定获取的字段
     * 例子：field('`*`') / field('`id`, `username`')
     * @access public
     * @param string $field 字段名
     * @return \core\jpd\Model
     */
    public function field($field)
    {
        self::$links[$this->class]->field($field);
        return $this;
    }

    /**
     * 执行SQL
     * @access public
     * @param string $sql SQL语句
     * @return mixed
     */
    public function query($sql)
    {
        return self::$links[$this->class]->query($sql);
    }
}

<?php

namespace core\jpd;

/**
 * Validate 数据验证类
 * 1.暂时自定义message还没有全部自定义，最好是用默认，获取错误信息方法需要修改
 * @package core\jpd
 */
class Validate
{
    // 实例
    protected static $instance;
    // 验证规则
    protected $rule = [];
    // 验证的数据
    protected $data = [];
    // 验证提示信息
    protected $message = [
        'require'      => ':参数不能缺少，并不能为空',
        'number'       => ':必须是数值',
        'string'       => ':必须是字符串',
        'array'        => ':不是数组',
        'in'           => ':不在#####之中',
        'notIn'        => ':在#####之中',
        'between'      => ':不在[#####]之中',
        'notBetween'   => ':在[#####]之中',
        'isAllChinese' => ':必须全为中文',
        'confirm'      => ':跟#####不相等',
        'different'    => ':跟#####相等',
        'length'       => ':长度为 #####',
        'max'          => ':最大长度为#####',
        'min'          => ':最小长度为#####',
        'file'         => '',
        'img'          => '',
    ];
    // 错误信息
    protected $error;
    // 验证的后缀
    private $checkSuffix = 'Check';

    /**
     * 构造函数
     * @access public
     * @param array $data    验证数据
     * @param array $rule    验证规则
     * @param array $message 验证提示信息
     */
    public function __construct($data = [], $rule = [], $message = [])
    {
        $this->data    = array_merge($this->data, $data);
        $this->rule    = array_merge($this->rule, $rule);
        $this->message = array_merge($this->message, $message);
    }

    /**
     * 实例化验证
     * @access public
     * @param array $data    验证数据
     * @param array $rule    验证规则
     * @param array $message 验证提示信息
     * @return Validate
     */
    public function make($data = [], $rule = [], $message = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($data, $rule, $message);
        }
        return self::$instance;
    }

    /**
     * 添加验证规则
     * @access public
     * @param string|array $name 字段名称或者规则数组
     * @param string       $rule 验证规则
     * @return Validate
     */
    public function rule($name, $rule = '')
    {
        if (is_array($name)) {
            $this->rule = array_merge($this->rule, $name);
        } else {
            $this->rule[$name] = $rule;
        }
        return $this;
    }

    /**
     * 输出错误信息
     * @access public
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 自动验证
     * @access public
     * @param array $data    验证数据
     * @param array $rules   验证规则
     * @param array $message 错误信息
     * @return bool
     */
    public function check($data = [], $rules = [], $message = [])
    {
        $this->data    = array_merge($this->data, $data);
        $this->message = array_merge($this->message, $message);

        if (empty($rules)) {
            $rules = $this->rule;
        }

        // 分析规则并调用验证
        $items = $this->getRule($rules);
        foreach ($items as $key => $item) {
            $result = $this->checkItem($key, $item);

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    /**
     * 验证单个参数
     * @access private
     * @param string $key   验证的参数
     * @param array  $rules 参数对应的规则字符串或数组
     * @return boolean
     */
    private function checkItem($key, $rules = [])
    {
        $checkSuffix = $this->checkSuffix;

        foreach ($rules as $rule) {
            if (is_array($rule)) {
                $name   = $rule[0] . $checkSuffix;
                $result = $this->$name($rule[0], $key, $rule[1]);
            } else {
                $name   = $rule . $checkSuffix;
                $result = $this->$name($rule, $key);
            }

            if (!$result) {
                return $result;
            }
        }
        return true;
    }

    /**
     * 获取到每个参数对应的规则，并返回
     * @access private
     * @param array $rules 规则数组, [参数=>规则]
     * @return array 分离后的规则数组
     */
    private function getRule($rules)
    {
        $items = [];
        foreach ($rules as $key => $rule) {
            $items[$key] = explode('|', $rule);
            foreach ($items[$key] as $k => $item) {
                if (strpos($item, ':')) {
                    $title           = explode(':', $item);
                    $items[$key][$k] = $title;
                }
            }
        }
        return $items;
    }

    /**
     * 获取错误提醒信息
     * @access private
     * @param string $name  方法名(去后缀)
     * @param string $key   验证的参数
     * @param string $param 内容
     * @return string
     */
    private function getErrorMessage($name, $key, $param = '')
    {
        if ($param) {
            return $key . str_replace('#####', $param, $this->message[$name]);
        }
        return $key . $this->message[$name];
    }

    /**
     * 是否存在，且不为空
     * @access private
     * @param string $name 方法名(去后缀)
     * @param string $key  验证的参数
     * @return mixed
     */
    private function requireCheck($name, $key)
    {
        if (!isset($this->data[$key])) {
            return !$this->error = $this->getErrorMessage($name, $key);
        }
        if (null == $this->data[$key]) {
            return !$this->error = $this->getErrorMessage($name, $key);
        }
        return true;
    }

    /**
     * 是否全为数值
     * @access private
     * @param string $name 方法名(去后缀)
     * @param string $key  验证的参数
     * @return mixed
     */
    private function numberCheck($name, $key)
    {
        if (!is_numeric($this->data[$key])) {
            return !$this->error = $this->getErrorMessage($name, $key);
        }
        return true;
    }

    /**
     * 是否为字符串
     * @access private
     * @param string $name 方法名(去后缀)
     * @param string $key  验证的参数
     * @return mixed
     */
    private function stringCheck($name, $key)
    {
        if (!is_string($this->data[$key])) {
            return !$this->error = $this->getErrorMessage($name, $key);
        }
        return true;
    }

    /**
     * 是否为数组
     * @access private
     * @param string $name 方法名(去后缀)
     * @param string $key  验证的参数
     * @return boolean
     */
    private function arrayCheck($name, $key)
    {
        if (!is_array($this->data[$key])) {
            return !$this->error = $this->getErrorMessage($name, $key);
        }
        return true;
    }

    /**
     * 是否在某个范围内，支持数字和字符串
     * @access private
     * @param string $name  方法名(去后缀)
     * @param string $key   验证的参数
     * @param string $param 范围 以逗号分隔
     * @return mixed
     */
    private function inCheck($name, $key, $param)
    {
        if (!in_array($this->data[$key], explode(',', $param))) {
            return !$this->error = $this->getErrorMessage($name, $key, $param);
        }
        return true;
    }

    /**
     * 是否不在某个范围内，支持数字和字符串
     * @access private
     * @param string $name  方法名(去后缀)
     * @param string $key   验证的参数
     * @param string $param 范围 以逗号分隔
     * @return boolean
     */
    private function notInCheck($name, $key, $param)
    {
        if (in_array($this->data[$key], explode(',', $param))) {
            return !$this->error = $this->getErrorMessage($name, $key, $param);
        }
        return true;
    }

    /**
     * 是否在某个范围内，支持数字
     * @access private
     * @param string $name  方法名(去后缀)
     * @param string $key   验证的参数
     * @param string $param 范围 以逗号分隔
     * @return boolean
     */
    private function betweenCheck($name, $key, $param)
    {
        list($start, $end) = explode(',', $param);
        if (!in_array($this->data[$key], range($start, $end))) {
            return !$this->error = $this->getErrorMessage($name, $key, $param);
        }
        return true;
    }

    /**
     * 是否不在某个范围内，支持数字
     * @access private
     * @param string $name  方法名(去后缀)
     * @param string $key   验证的参数
     * @param string $param 范围 以逗号分隔
     * @return boolean
     */
    private function notBetweenCheck($name, $key, $param)
    {
        list($start, $end) = explode(',', $param);
        if (in_array($this->data[$key], range($start, $end))) {
            return !$this->error = $this->getErrorMessage($name, $key, $param);
        }
        return true;
    }

    /**
     * 是否全为中文
     * @access private
     * @param string $name 方法名(去后缀)
     * @param string $key  验证的参数
     * @return boolean
     */
    private function isAllChineseCheck($name, $key)
    {
        if (0 < preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $this->data[$key])) {
            return !$this->error = $this->getErrorMessage($name, $key);
        }
        return true;
    }

    /**
     * 验证某个字段是否和另外一个字段的值一致
     * @access private
     * @param string $name  方法名(去后缀)
     * @param string $key   验证的参数
     * @param string $param 另外一个参数 默认为 {$key}_confirm
     * @return boolean
     */
    private function confirmCheck($name, $key, $param = '')
    {
        $confirm = empty($param) ? $key . '_confirm' : $param;
        if ($this->data[$key] != $this->data[$confirm]) {
            return !$this->error = $this->getErrorMessage($name, $key, $confirm);
        }
        return true;
    }

    /**
     * 验证某个字段是否和另外一个字段的值不一致
     * @access private
     * @param string $name  方法名(去后缀)
     * @param string $key   验证的参数
     * @param string $param 另外一个参数
     * @return boolean
     */
    private function differentCheck($name, $key, $param)
    {
        if ($this->data[$key] == $this->data[$param]) {
            return !$this->error = $this->getErrorMessage($name, $key, $param);
        }
        return true;
    }

    /**
     * 验证某个字段的值的长度是否在某个范围, 或者指定长度
     * @access private
     * @param string $name  方法名(去后缀)
     * @param string $key   验证的参数
     * @param string $param 值/以逗号分隔的范围值
     * @return boolean
     */
    private function lengthCheck($name, $key, $param)
    {
        $range = $param;
        $len   = strlen($this->data[$key]);

        // 为数组，判断范围
        if (false !== strpos($param, ',')) {
            list($start, $end) = explode(',', $param);
            $param = range($start, $end, 1);
            if (!in_array($len, $param)) {
                $range = '[' . $start . ',' . $end . ']';
                return !$this->error = $this->getErrorMessage($name, $key, $range);
            }

            return true;
        }

        // 为字符串，判断是否相等
        if ($len != $param) {
            return !$this->error = $this->getErrorMessage($name, $key, $range);
        }

        return true;
    }

    /**
     * 验证某个字段的值的最大长度
     * @access private
     * @param string $name  方法名(去后缀)
     * @param string $key   验证的参数
     * @param string $param 范围值
     * @return boolean
     */
    private function maxCheck($name, $key, $param)
    {
        $len = strlen($this->data[$key]);
        if ($len < 0 || $len > $param) {
            return !$this->error = $this->getErrorMessage($name, $key, $param);
        }
        return true;
    }

    /**
     * 验证某个字段的值的最小长度
     * @access private
     * @param string $name  方法名(去后缀)
     * @param string $key   验证的参数
     * @param string $param 范围值
     * @return boolean
     */
    private function minCheck($name, $key, $param)
    {
        $len = strlen($this->data[$key]);
        if ($len < $param) {
            return !$this->error = $this->getErrorMessage($name, $key, $param);
        }
        return true;
    }

}

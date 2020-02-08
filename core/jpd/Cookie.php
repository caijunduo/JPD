<?php

namespace core\jpd;

class Cookie
{
    // 实例化
    protected static $instance;

    // 配置信息
    protected $options = ['expire' => 1440];

    /**
     * 构造函数
     * @acccess private
     * @param array $options 配置信息
     */
    private function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * 实例化
     * @access public
     * @return Cookie
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self(Config::get('cookie'));
        }
        return self::$instance;
    }

    /**
     * 设置COOKIE
     * @param string|array $name   键，为字符串则单个设置，为数组则批量设置
     * @param mixed        $value  值
     * @param int          $expire 保留时间
     * @return bool
     */
    public function set($name, $value = null, $expire = 0)
    {
        $expire = 0 != $expire ? $expire : '';

        // 为字符串，则单个设置
        if (is_string($name)) {
            setcookie($name, $value, $expire);
        }

        // 为数组，则批量设置
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                setcookie($key, $val, $expire);
            }
        }

        return true;
    }

    /**
     * 获取COOKIE
     * @param string $name 键，有值则返回单个，为空返回全部
     * @return bool|string
     */
    public function get($name = '')
    {
        // 为空返回全部
        if (empty($name)) {
            return $_COOKIE;
        }

        // 返回具体COOKIE
        if (is_string($name)) {
            return isset($_COOKIE[$name]) ? $_COOKIE[$name] : '';
        }

        return true;
    }

    /**
     * 删除COOKIE
     * @param string $name 键，有值则删除单个，为空清空全部
     * @return bool
     */
    public function clear($name = '')
    {
        // 为空清空全部
        if (empty($name)) {
            foreach ($_COOKIE as $key => $val) {
                setcookie($key);
            }
        }

        // 删除具体
        if (is_string($name)) {
            setcookie($name);
        }

        return true;
    }
}
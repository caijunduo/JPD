<?php

namespace core\jpd;

use core\jpd\cache\Drive;

/**
 * Cache 缓存类
 * @package core\jpd
 */
class Cache
{

    /**
     * @access public
     * @var array 缓存的实例
     */
    protected static $instance;

    /**
     * @access public
     * @var object 操作句柄
     */
    protected static $handler;

    /**
     * @access public
     * @var integer 缓存读取次数
     */
    public static $readTimes = 0;

    /**
     * @access public
     * @var integer 缓存写入次数
     */
    public static $writeTimes = 0;

    /**
     * 连接缓存驱动
     * @access public
     * @param array $options 配置参数
     * @param bool  $name    缓存连接标识 true 强制重新连接
     * @return Drive
     */
    public static function connect(array $options = [], $name = false)
    {
        $type = !empty($options['type']) ? $options['type'] : 'File';
        if (false === $name) {
            $name = md5(serialize($options));
        }
        if (true === $name || !isset(self::$instance[$name])) {
            $class = false === strpos($type, '\\') ? '\\core\\jpd\\cache\\drive\\' . ucwords($type) : $type;
            // 记录初始化信息
            App::$debug && Log::log('[ CACHE ] INIT ' . $type . ' info', 'CACHE');
            if (true === $name) {
                return new $class($options);
            }
            self::$instance[$name] = new $class($options);
        }
        return self::$instance[$name];
    }

    /**
     * 初始化缓存
     * @access public
     * @param array $options 配置参数
     * @return Drive
     */
    public static function init(array $options = [])
    {
        if (is_null(self::$handler)) {
            if (empty($options)) {
                $options = Config::get('cache');
            }
            self::$handler = self::connect($options);
        }
        return self::$handler;
    }

    /**
     * 判断缓存是否存在
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public static function has($name)
    {
        self::$readTimes++;

        return self::init()->has($name);
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name    缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public static function get($name, $default = null)
    {
        self::$readTimes++;

        return self::init()->get($name, $default);
    }

    /**
     * 写入缓存
     * @access public
     * @param string            $name   缓存变量名
     * @param mixed             $value  存储数据
     * @param integer|\DateTime $expire 有效时间(秒)
     * @return bool
     */
    public static function set($name, $value, $expire = null)
    {
        self::$writeTimes++;

        return self::init()->set($name, $value, $expire);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public static function rm($name)
    {
        self::$writeTimes++;

        return self::init()->rm($name);
    }

    /**
     * 清除缓存
     * @access public
     * @param string $tag 变量名 为空清除全部缓存
     * @return bool
     */
    public static function clear($tag = null)
    {
        self::$writeTimes++;

        return self::init()->clear($tag);
    }

    /**
     * 缓存标签
     * @access public
     * @param string       $tag     标签名
     * @param string|array $keys    缓存标识
     * @param bool         $overlay 是否覆盖
     * @return Drive
     */
    public static function tag($tag, $keys = null, $overlay = false)
    {
        return self::init()->tag($tag, $keys, $overlay);
    }
}

<?php

namespace core\jpd;

/**
 * Config 配置类
 * @package core\jpd
 */
class Config
{

    /**
     * @var array 配置结果集
     */
    private static $config = [];

    /**
     * @var string 作用域
     */
    private static $range = '_sys_';

    /**
     * 检测配置参数是否存在
     * @param        $name
     * @param string $range
     * @return bool
     */
    public static function has($name, $range = '')
    {
        $range = $range ?: self::$range;
        if (!isset(self::$config[$range])) {
            self::$config[$range] = [];
        }

        return !!self::get($name);
    }

    /**
     * 设置配置参数 name 为数组则表示批量设置
     * @param string|array $name  配置参数名 (1.字符串表示单个设置, 2.数组表示批量设置)
     * @param string|null  $value 配置值
     * @param string       $range 作用域
     * @return mixed
     */
    public static function set($name, $value = null, $range = '')
    {
        $range = $range ?: self::$range;
        if (!isset(self::$config[$range])) {
            self::$config[$range] = [];
        }

        // 字符串则表示单个配置
        if (is_string($name)) {
            if (!strpos($name, '.')) {
                self::$config[$range][strtolower($name)] = $value;
            } else {
                // 二维
                $name                                                 = explode('.', $name, 2);
                self::$config[$range][strtolower($name[0])][$name[1]] = $value;
            }
        }

        // 数组则表示批量配置
        if (is_array($name)) {
            if (!empty($value)) {
                self::$config[$range][$value] = isset(self::$config[$range][$value]) ? array_merge(
                    self::$config[$range][$value], $name) : $name;
                return self::$config[$range][$value];
            }
            return self::$config[$range] = array_merge(self::$config[$range], array_change_key_case($name));
        }
        // 为空直接返回已有配置
        return self::$config[$range];
    }

    /**
     * 获取配置参数 字符串表示单个配置
     * @param string|null $name  配置参数名
     * @param string      $range 作用域
     * @return mixed
     */
    public static function get($name = null, $range = '')
    {
        $range = $range ?: self::$range;
        if (!isset(self::$config[$range])) {
            self::$config[$range] = [];
        }

        // 字符串则表示单个配置
        if (is_string($name)) {
            if (!strpos($name, '.')) {
                return self::$config[$range][$name];
            } else {
                $name = explode('.', $name, 2);
                return self::$config[$range][strtolower($name[0])][$name[1]];
            }
        }
        // 为空
        return self::$config[$range];
    }
}

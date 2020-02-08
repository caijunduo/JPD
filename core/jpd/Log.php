<?php

namespace core\jpd;

/**
 * Log 日志类
 * @package core\jpd
 */
class Log
{
    // 实例化
    private static $instance;

    /**
     * 构造函数
     * @access public
     */
    public function __construct()
    {
    }

    /**
     * 初始化
     * @access public
     * @return boolean
     */
    private static function init()
    {
        if (!Config::get('log_on')) {
            return false;
        }

        $type           = Config::get('log.type');
        $class          = '\\' . __NAMESPACE__ . '\log\drive\\' . ucfirst($type ? $type : 'file');
        self::$instance = new $class();

        return true;
    }

    /**
     * 记录日志
     * @access public
     * @param string $message 日志内容
     * @param string $tag     日志标签
     * @return void
     */
    public static function log($message, $tag = 'LOG')
    {
        self::init() && self::$instance->write($message, $tag);
    }
}

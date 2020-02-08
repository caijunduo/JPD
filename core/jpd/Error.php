<?php

namespace core\jpd;

use core\jpd\exception\ErrorException;
use core\jpd\exception\Handle;

/**
 * Error 错误声明类
 * @package core\jpd
 */
class Error
{

    /**
     * 错误和异常处理的注册
     * @access public
     * @return void
     */
    public static function register()
    {
        error_reporting(E_ALL ^ E_NOTICE ^ E_ERROR | E_STRICT);
        set_error_handler([
            __CLASS__,
            'appError'
        ]);
        set_exception_handler([
            __CLASS__,
            'appException'
        ]);
        register_shutdown_function([
            __CLASS__,
            'appShutdown'
        ]);
    }

    /**
     * 错误处理
     * @access public
     * @param integer $errno   错误编号
     * @param integer $errstr  详细错误信息
     * @param string  $errfile 出错的文件
     * @param integer $errline 出错行号
     * @return void
     */
    public static function appError($errno, $errstr, $errfile = '', $errline = 0)
    {
        $exception = new ErrorException($errno, $errstr, $errfile, $errline);
        self::getExceptionHandler()->report($exception);
    }

    /**
     * 异常处理
     * @access public
     * @param \Exception $e 异常
     * @return void
     */
    public static function appException($e)
    {
        $handle = self::getExceptionHandler();
        $handle->report($e);
        $handle->render();
    }

    /**
     * 异常中止处理
     * @access public
     * @return void
     */
    public static function appShutdown()
    {
        if (!is_null($error = error_get_last()) && App::$debug) {
            self::appException(new ErrorException($error['type'], $error['message'], $error['file'], $error['line']));
        }
    }

    /**
     * 获取异常处理的实例
     * @access public
     * @return Handle
     */
    public static function getExceptionHandler()
    {
        static $handle;
        if (!$handle) {
            $handle = new Handle();
        }
        return $handle;
    }
}

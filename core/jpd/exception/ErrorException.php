<?php

namespace core\jpd\exception;

use core\jpd\Exception;

/**
 * 异常处理
 */
class ErrorException extends Exception
{
    /**
     * 构造函数
     * @access public
     * @param integer $errno   错误级别
     * @param string  $errstr  错误信息
     * @param string  $errfile 错误文件
     * @param integer $errline 错误文件行数
     */
    public function __construct($errno, $errstr, $errfile, $errline)
    {
        $this->message = $errstr;
        $this->file    = $errfile;
        $this->line    = $errline;
        $this->code    = $errno;
        (new ResponseException())->send([
            'message' => $this->message,
            'file'    => $this->file,
            'line'    => $this->line,
            'code'    => $this->code,
        ]);
    }
}

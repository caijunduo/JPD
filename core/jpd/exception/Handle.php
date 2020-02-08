<?php

namespace core\jpd\exception;

use core\jpd\App;
use core\jpd\Exception;

class Handle
{
    // 输出错误信息类的实例
    protected static $instance;
    // 储存错误信息
    protected $data = [];

    /**
     * 记录错误信息
     * @access public
     * @param core\jpd\Exception $exception 异常处理类
     * @return void
     */
    public function report($exception)
    {
        if (App::$debug) {
            $data = [
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'message' => $exception->getMessage(),
                'code'    => $exception->getCode(),
            ];
        } else {
            $data = [
                'code'    => $exception->getCode(),
                'message' => $exception->getMessage(),
            ];
        }

        $this->data = $data;
    }

    /**
     * 输出错误到页面
     * @access public
     * @return void
     */
    public function render()
    {
        if (is_null(self::$instance)) {
            self::$instance = new ResponseException();
        }

        self::$instance->send($this->data);
    }
}

<?php

namespace core\jpd\exception\drive;

use core\jpd\App;
use core\jpd\exception\ResponseException;

/**
 * Json格式输出错误信息
 */
class Json extends ResponseException
{
    /**
     * 构造函数
     */
    public function __construct()
    {
    }

    /**
     * 输出到页面
     * @access public
     * @param array $data 错误信息数组
     * @return void
     */
    public function send($data)
    {
        // 清空缓存
        ob_get_length() > 0 && ob_clean();
        header('Content-Type: application/json;charset=utf-8');
        header('HTTP/1.1 500 Internal Server Error');
        if (!App::$debug) {
            exit(json_encode([
                'code' => 500,
                'message'  => 'Internal Server Error'
            ]));
        }
        exit(json_encode($data));
    }
}

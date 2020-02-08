<?php

namespace core\jpd\exception\drive;

use core\jpd\App;
use core\jpd\Config;
use core\jpd\exception\ResponseException;

/**
 * Html格式输出错误信息
 */
class Html extends ResponseException
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
        header('Content-Type: text/html;charset=utf-8');
        $file = Config::get('exception.exception_tpl');

        if (App::$debug) {
            $msg = "<p>File: {$data['file']}</p>\n<p>Line: {$data['line']}</p>\n";
        } else {
            $file = dirname($file) . '/Error.tpl';
        }

        $content = file_get_contents($file);

        $content = str_replace([
            '###@@MESSAGE@@###',
            '###@@@@###'
        ], [
            $data['message'],
            $msg
        ], $content);

        // 清除缓存
        ob_get_length() > 0 && ob_clean();

        // 输出错误页面
        exit($content);
    }
}

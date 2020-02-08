<?php

namespace core\jpd\log\drive;

use core\jpd\Config;

class File
{
    // 文件目录
    private $path;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->path = Config::get('log.path');
    }

    /**
     * 写日志
     * @access public
     * @param string $message 日志内容
     * @param string $flag    日志标签
     * @return mixed
     */
    public function write($message, $flag = 'LOG')
    {
        $path = $this->path . date('Ym') . DS . date('d');

        // 目录不存在则创建
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $file    = $path . DS . date('H') . '.log';
        $content = '[' . date(DATE_ATOM, time()) . ']' . '[' . $flag . ']  ' . json_encode($message) . PHP_EOL;

        return file_put_contents($file, $content, FILE_APPEND);
    }
}

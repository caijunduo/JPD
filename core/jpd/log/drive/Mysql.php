<?php

namespace core\jpd\log\drive;

use core\jpd\Config;

// mysql类型暂时没有编写，需求：需要一个数据表来装，需要内置好一个表，字段规定
class Mysql
{
    private $path;

    public function __construct()
    {
        $this->path = Config::get('log.path');
    }

    public function write()
    {

    }
}

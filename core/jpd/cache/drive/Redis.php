<?php

namespace core\jpd\cache\drive;

use core\jpd\cache\Drive;
use \Redis as RedisExtend;

/**
 * Redis缓存类
 * @package core\jpd\cache\drive
 */
class Redis extends Drive
{
    /**
     * @access protected
     * @var array 配置参数
     */
    protected $options = [
        // 时间有效期(秒)
        'expire'        => 0,
        // 前缀
        'prefix'        => '',
        // 是否数据压缩
        'data_compress' => false,
    ];


    /**
     * 构造函数
     * @access public
     * @param array $options 配置参数
     * @return void
     */
    public function __construct($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $this->init();
    }

    public function init()
    {
        $handler = new RedisExtend;
        $handler->connect('127.0.0.1', 6379);
        $this->handler = $handler;
    }

    public function set($name, $value = '', $expire = 0)
    {
        $res = $this->handler->set($name, $value);

        // 不为0则设置过期时间
        if (0 !== $expire) {
            $this->handler->expire($name, $expire);
        }

        return $res;
    }

    public function get($name, $default = null)
    {
        $res = $this->handler->get($name);
        return $res;
    }

    public function has($name)
    {
        $res = $this->handler->exists($name);
        return $res;
    }

    public function clear($tag = null)
    {

    }

    public function rm($name)
    {
        $res = $this->handler->del($name);
        return $res;
    }

    public function hGet($name, $field = '')
    {
        $res = empty($field) ?
            $this->handler->hGetAll($name) :
            $this->handler->hGet($name, $field);
        return $res;
    }

    public function hSet($name, $field, $value, $expire = 0)
    {
        $res = $this->handler->hSet($name, $field, $value);
        if (0 !== $expire) {
            $this->handler->expire($name, $expire);
        }
        return $res;
    }

    public function expire($name, $expire = 0)
    {
        $res = $this->handler->expire($name, $expire);
        return $res;
    }
}
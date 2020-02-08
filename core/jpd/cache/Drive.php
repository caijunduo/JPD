<?php

namespace core\jpd\cache;

/**
 * 缓存基础类
 */
abstract class Drive
{
    /**
     * @access protected
     * @var object 句柄对象
     */
    protected $handler = null;

    /**
     * @access protected
     * @var array 配置参数
     */
    protected $options = [];

    /**
     * @access protected
     * @var string 缓存标签
     */
    protected $tag;

    /**
     * 判断缓存是否存在
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    abstract public function has($name);

    /**
     * 读取缓存
     * @access public
     * @param string $name    缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    abstract public function get($name, $default = null);

    /**
     * 写入缓存
     * @access public
     * @param string            $name   缓存变量名
     * @param mixed             $value  存储数据
     * @param integer|\DateTime $expire 有效时间(秒)
     * @return bool
     */
    abstract public function set($name, $value, $expire = null);

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    abstract public function rm($name);

    /**
     * 清除缓存
     * @access public
     * @param string $tag 变量名 为空清除全部缓存
     * @return bool
     */
    abstract public function clear($tag = null);

    /**
     * 读取缓存并删除
     * @access public
     * @param string $name 缓存变量名
     * @return mixed
     */
    public function pull($name)
    {
        $result = $this->get($name);
        if ($result) {
            $this->rm($name);
            return $result;
        } else {
            return;
        }
    }

    // 读取缓存，如果不存在则写入缓存
    public function remember($name, $value, $expire = null)
    {
        if (!$this->has($name)) {

        } else {
            $value = $this->get($name);
        }
        return $value;
    }

    /**
     * 缓存标签
     * @access public
     * @param string       $name    标签名
     * @param string|array $keys    缓存标识
     * @param bool         $overlay 是否覆盖
     * @return $this
     */
    public function tag($name, $keys = null, $overlay = false)
    {
        if (is_null($name)) {
        } elseif (is_null($keys)) {
            $this->tag = $name;
        } else {
            $key = 'tag_' . md5($name);
            if (is_string($keys)) {
                $keys = explode(',', $keys);
            }
            $keys = array_map([
                $this,
                'getCacheKey',
            ], $keys);
            if ($overlay) {
                $value = $keys;
            } else {
                $value = array_unique(array_merge($this->getTagItem($name), $keys));
            }
            $this->set($key, implode(',', $value), 0);
        }
        return $this;
    }

    /**
     * 更新标签
     * @access protected
     * @param string $name 缓存标识
     * @return void
     */
    protected function setTagItem($name)
    {
        if ($this->tag) {
            $key       = 'tag_' . md5($this->tag);
            $this->tag = null;
            if ($this->has($key)) {
                $value   = explode(',', $this->get($key));
                $value[] = $name;
                $value   = implode(',', array_unique($value));
            } else {
                $value = $name;
            }
            $this->set($key, $value, 0);
        }
    }

    /**
     * 获取标签包含的缓存标识
     * @access protected
     * @param string $tag 缓存标签
     * @return array
     */
    protected function getTagItem($tag)
    {
        $key   = 'tag_' . md5($tag);
        $value = $this->get($key);
        if ($value) {
            return array_filter(explode(',', $value));
        } else {
            return [];
        }
    }

    /**
     * 返回句柄对象
     * @access public
     * @return object
     */
    public function handler()
    {
        return $this->handler;
    }
}

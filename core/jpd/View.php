<?php

namespace core\jpd;

/**
 * View 视图类
 * @package core\jpd
 */
class View
{

    /**
     * 视图的实例
     * @var
     */
    protected static $instance;

    /**
     * 模板引擎的实例
     * @var
     */
    public $engine;

    /**
     * 模板变量
     * @var array
     */
    protected $data = [];

    /**
     * 视图输出替换
     * @var array
     */
    protected $replace = [];

    /**
     * 构造函数
     * @access public
     * @param array $engine  模板引擎
     * @param array $replace 视图替换字符串
     */
    public function __construct($engine = [], $replace = [])
    {
        // 初始化模板引擎
        $this->engine($engine);
        // 基础视图替换字符串
        $root          = '';
        $baseReplace   = [
            '__ROOT__'   => $root,
            '__STATIC__' => $root . '/static',
            '__CSS__'    => $root . '/static/css',
            '__JS__'     => $root . '/static/js',
            '__IMG__'    => $root . '/static/img'
        ];
        $this->replace = array_merge($baseReplace, (array)$replace);
    }

    /**
     * 视图实例化
     * @access public
     * @param array $engine  模板引擎
     * @param array $replace 视图替换字符串
     * @return \core\jpd\View
     */
    public static function instance($engine = [], $replace = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($engine, $replace);
        }
        return self::$instance;
    }

    /**
     * 设置模板变量
     * @param string $name  变量名
     * @param string $value 变量值
     * @return \core\jpd\View
     */
    public function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }
        return $this;
    }

    /**
     * 渲染内容输出
     * @param string $template
     * @param array  $replace
     * @return bool
     */
    public function display($template = '', $replace = [])
    {
        // 页面缓存
        ob_start();
        ob_implicit_flush(0);
        try {
            $replace = array_merge($this->replace, $replace, $this->engine->config('tpl_replace_string'));
            $this->engine->config('tpl_replace_string', $replace);
            $this->engine->display($template, $this->data, $replace);
        } catch (\Exception $e) {
            ob_end_clean();
            dump($e->getMessage()); // 没处理
        }
        $content = ob_get_clean();
        return $content;
    }

    /**
     * 设置模板解析引擎
     * @param array $options 引擎参数
     * @return $this
     */
    public function engine($options = [])
    {
        if (is_string($options)) {
            $type    = $options;
            $options = [];
        } else {
            $type = !empty($options['type']) ? $options['type'] : 'Php';
        }
        $class = false !== strpos($type, '\\') ? $type : '\\core\\jpd\\view\\drive\\' . ucfirst($type);
        if (isset($options['type'])) {
            unset($options['type']);
        }
        $this->engine = new $class($options);
        return $this;
    }
}

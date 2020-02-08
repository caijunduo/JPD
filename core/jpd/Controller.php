<?php

namespace core\jpd;

/**
 * Controller 控制器基类
 * @package core\jpd
 */
class Controller
{
    // 视图实例化
    protected $view;
    // 请求类实例化
    protected $request;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->view    = Config::get('template_on') ? View::instance(Config::get('template'),
            Config::get('view_replace_str')) : '';
        $this->request = Request::instance();
        // 控制器初始化
        $this->_initialize();
    }

    /**
     * 初始化函数
     */
    protected function _initialize()
    {
    }

    /**
     * 重定向
     * @param string $url   URL地址
     * @param array  $param 参数数组
     * @return void
     */
    public function redirect($url, $param = [])
    {
        Jump::redirect($url, $param);
    }

    /**
     * 渲染模板
     * @param string $content 模板地址
     */
    protected function display($content = '')
    {
        echo $this->view->display($content);
    }

    /**
     * 设置模板变量
     * @param string $name  变量名
     * @param string $value 变量值
     * @return \core\jpd\Controller
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);
        return $this;
    }
}

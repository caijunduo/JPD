<?php

namespace core\jpd;

/**
 * Request 请求类
 * @package core\jpd
 */
class Request
{
    // 对象实例
    protected static $instance;
    // URL地址
    protected $url;
    // 模块
    protected $module;
    // 控制器
    protected $controller;
    // 方法
    protected $action;
    // 根地址
    protected $root;

    /**
     * 构造函数
     * @param array $options
     */
    public function __construct($options = [])
    {
    }

    /**
     * 初始化
     * @param array $options 参数
     * @return \core\jpd\Request
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * 设置或获取当前模块名
     * @param string $module 模块名
     * @return string|Request
     */
    public function module($module = null)
    {
        if (!is_null($module)) {
            $this->module = $module;
            return $this;
        } else {
            return $this->module ?: '';
        }
    }

    /**
     * 设置或获取当前控制器名
     * @param  string $controller 控制器名
     * @return string|Request
     */
    public function controller($controller = null)
    {
        if (!is_null($controller)) {
            $this->controller = $controller;
            return $this;
        } else {
            return $this->controller ?: '';
        }
    }

    /**
     * 设置或获取当前方法名
     * @param  string $action 方法名
     * @return string|Request
     */
    public function action($action = null)
    {
        if (!is_null($action)) {
            $this->action = $action;
            return $this;
        } else {
            return $this->action ?: '';
        }
    }

    /**
     * 获取请求的方法
     * @access public
     * @return string
     */
    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * 获取域名
     * @access public
     * @return mixed
     */
    public function host()
    {
        return $_SERVER['HTTP_HOST'];
    }

    public function ip()
    {
    }

    /**
     * 获取HTTP或HTTPS开头
     * @access public
     * @return string
     */
    public function http()
    {
        if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
            return 'https://';
        }
        return 'http://';
    }

    /**
     * 获取URL地址，不含参数
     * @param null $url
     * @return string
     */
    public function root($url = null)
    {
        $this->root = is_null($url) ? array_filter([
            $this->module,
            $this->controller,
            $this->action
        ]) : $url;
        return $this->http() . $this->host() . '/' . implode('/', $this->root);
    }

    /**
     * 是否为GET请求
     * @access public
     * @return boolean
     */
    public function isGet()
    {
        if ('GET' == $this->method()) {
            return true;
        }
        return false;
    }

    /**
     * 是否为POST请求
     * @access public
     * @return boolean
     */
    public function isPost()
    {
        if ('POST' == $this->method()) {
            return true;
        }
        return false;
    }

    /**
     * 是否为PUT请求
     * @access public
     * @return boolean
     */
    public function isPut()
    {
        if ('PUT' == $this->method()) {
            return true;
        }
        return false;
    }

    /**
     * 是否为DELETE请求
     * @access public
     * @return boolean
     */
    public function isDelete()
    {
        if ('DELETE' == $this->method()) {
            return true;
        }
        return false;
    }

    /**
     * 是否为PATCH请求
     * @access public
     * @return boolean
     */
    public function isPatch()
    {
        if ('PATCH' == $this->method()) {
            return true;
        }
        return false;
    }

    /**
     * 获取GET请求的参数
     * @access public
     * @param string $name    参数名 存在则获取单个，不存在则获取全部
     * @param string $default 默认值
     * @return mixed
     */
    public function get($name = '', $default = '')
    {
        return $this->input('GET', $name, $default);
    }

    /**
     * 获取POST请求的参数
     * @access public
     * @param string $name    参数名 存在则获取单个，不存在则获取全部
     * @param string $default 默认值
     * @return mixed
     */
    public function post($name = '', $default = '')
    {
        return $this->input('POST', $name, $default);
    }

    /**
     * 获取所有请求的参数
     * @access public
     * @param string $name    参数名 存在则获取单个，不存在则获取全部
     * @param string $default 默认值
     * @return mixed
     */
    public function param($name = '', $default = '')
    {
        return $this->input('REQUEST', $name, $default);
    }

    /**
     * 获取对应请求的参数
     * @todo 这里获取参数可能会有问题
     * @var array $_GET
     * @var array $_POST
     * @var array $_REQUEST
     * @param string $method  请求方法
     * @param string $name    参数名
     * @param string $default 默认值
     * @return mixed
     */
    public function input($method, $name = '', $default = '')
    {
        header('Content-type: application/json;charset=utf-8');
        $data = [];
        if (isset($_SERVER['HTTP_CONTENT_TYPE']) && ('application/json' == strtolower($_SERVER['HTTP_CONTENT_TYPE']) || 'application/json;charset=utf-8' == strtolower($_SERVER['HTTP_CONTENT_TYPE']))) {
            $data = json_decode(file_get_contents('php://input'), true);
            if ($this->isPost()) {
                $_POST = array_merge($_POST, $data);
            } else {
                $_REQUEST = array_merge($_REQUEST, $data);
            }
        }

        $methodParam = [
            'GET'     => $_GET,
            'POST'    => $_POST,
            'REQUEST' => $_REQUEST
        ];
        $data        = array_merge($methodParam[$method], $data);

        // 获取单个参数
        if (!empty($name)) {
            if (false === strpos($name, ',')) {
                $data = isset($data[$name]) && !empty($data[$name]) ? [$name => $data[$name]] : [$name => $default];
            } else {
                $default = explode(',', $default);
                $temp = [];
                foreach (explode(',', $name) as $key => $val) {
                    $temp[$val] = isset($data[$val]) ? $data[$val] : (isset($default[$key]) ? $default[$key] : '');
                }
                $data = $temp;
            }
        }
        
        return $data;
    }

    /**
     * 获取或设置请求头信息 Request Headers
     * @access public
     * @param string|array $name 字符串为获取，
     * @param string $value
     * @param string $prefix
     * @return bool|array|string
     */
    public function header($name = '', $value = '', $prefix = 'HTTP_')
    {
        $name = str_replace('-', '_', $name);
        $data = [];
        if (!$name) {
            // 返回所有
            foreach ($_SERVER as $key => $val) {
                if (substr($key, 0, 5) === $prefix) {
                    $data[str_replace([$prefix, '_'], ['', '-'], $key)] = $val;
                }
            }
        } elseif (is_array($name)) {
            // 批量设置
            foreach ($name as $key => $val) {
                $_SERVER[$prefix . strtoupper($key)] = $val;
            }
            return true;
        } elseif ($value) {
            // 单个设置
            return $_SERVER[strtoupper($prefix . $name)] = $value;
        } elseif (false === strpos($name, ',')) {
            // 获取单个
            $data[str_replace('_', '-', $name)] = $_SERVER[$prefix . strtoupper($name)];
        } else {
            // 获取多个
            foreach (explode(',', $name) as $item) {
                $key = strtoupper($prefix . $item);
                $data[str_replace('_', '-', $item)] = isset($_SERVER[$key]) ? $_SERVER[$key] : '';
            }
        }

        return array_change_key_case($data, CASE_LOWER);
    }

    /**
     * 设置响应头信息 Response Headers
     * @access public
     * @param string|array $name 字符串为单个设置，数组为批量设置，数组需为一维关联数组
     * @param string $value 头信息值
     * @return bool
     */
    public function response($name, $value = '')
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                header($key . ': ' . $val);
            }
            return true;
        } elseif ($value) {
            return header($name, $value);
        } else {
            return false;
        }
    }
}

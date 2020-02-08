<?php

namespace core\jpd;

/**
 * Route 路由类
 * @package core\jpd
 */
class Route
{
    // 路由类实例化句柄
    protected static $instance;
    // 请求类实例化句柄
    protected $request;
    // 当前模块路径
    public static $modulePath;
    // 路由储存
    public static $routeStorage;
    // 多模块
    public static $multiModule;

    /**
     * 构造函数
     * @access public
     * @param array $options  配置信息数组
     * @param bool  $notCheck 自动检查路由
     * @throws Exception
     */
    public function __construct($options = null, $notCheck = true)
    {
        $this->request     = Request::instance($options);
        self::$multiModule = Config::get('multi_module');
        if ($this->request->method() == 'OPTIONS') {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization,Authorization-Token, Authorization-Terminal");
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
            exit;
        }
        $notCheck && $this->check($options);
    }

    /**
     * 实例化
     * @access public
     * @param array $options  配置信息数组
     * @param bool  $notCheck 自动检查路由
     * @return \core\jpd\Route
     * @throws Exception
     */
    public static function instance($options = null, $notCheck = true)
    {
        if (is_null(self::$instance)) {
            self::$instance = new static($options, $notCheck);
        }
        return self::$instance;
    }

    /**
     * 路由检测调用
     * @access public
     * @param array $options 配置信息数组
     * @return void
     * @throws Exception
     */
    public function check($options)
    {
        $this->checkRoute($options['pathinfo'], $options['depr']);
    }

    /**
     * 路由检测
     * @access public
     * @param string $url  路由地址
     * @param string $depr 路由分隔符
     * @return void
     * @throws Exception
     */
    public function checkRoute($url, $depr)
    {
        false !== ($end = strpos($url, '?')) && $url = substr($url, 0, $end);

        $module = $this->parseUrl($url, $depr);
        
        // 去掉s参数
        if (isset($_GET['s']) && $_GET['s'] == $url) {
            unset($_GET['s']);
        }
        if (isset($_REQUEST['s']) && $_REQUEST['s'] == $url) {
            unset($_REQUEST['s']);
        }

        if (!empty($module)) {
            $this->load($module);
        }
    }

    /**
     * 切割路由，进行转换
     * @access public
     * @param string $url  路由地址
     * @param string $depr 路由分隔符
     * @return array
     * @throws Exception
     */
    public function parseRoute($url, $depr)
    {
        // 开启路由
        if (Config::get('route_on')) {
            $url = $this->parseVars($url);
        }
        
        $limit = self::$multiModule ? 4 : 3;

        $name = isset($url) && '/' !== $url ? explode($depr, trim($url, '/'), $limit) : [];

        $dModule = Config::get('default_module');
        $dCtrl   = Config::get('default_controller');
        $dAction = Config::get('default_action');

        if (self::$multiModule) {
            $module['module']     = isset($name[0]) ? $name[0] : $dModule;
            $module['controller'] = isset($name[1]) ? $name[1] : $dCtrl;
            $module['action']     = isset($name[2]) ? $name[2] : $dAction;
        } else {
            $module['controller'] = isset($name[0]) ? $name[0] : $dCtrl;
            $module['action']     = isset($name[1]) ? $name[1] : $dAction;
        }

        // 清除内存
        unset($name);

        return $module;
    }

    /**
     * 切换URL地址，并将路由信息记录到请求类
     * @access public
     * @param string $url  URL地址
     * @param string $depr URL分隔符
     * @return array
     * @throws Exception
     */
    public function parseUrl($url, $depr)
    {
        $module = $this->parseRoute($url, $depr);

        // 获取当前模块路径
        self::$modulePath = APP_PATH . (isset($module['module']) ? $module['module'] . DS : '');

        isset($module['module']) && $this->request->module($module['module']);
        isset($module['controller']) && $this->request->controller($module['controller']);
        isset($module['action']) && $this->request->action($module['action']);
        isset($module) && $this->request->root($module);

        return $module;
    }

    /**
     * 路由变量转换
     * *不支持一个位置多个变量
     * @access public
     * @param string $url 访问地址
     * @return string
     */
    public function parseVars($url)
    {
        $method = $this->request->method();
        $name   = isset(self::$routeStorage[$method]) ? self::$routeStorage[$method] : false;

        if (false === $name) {
            throw new Exception('该页面不存在');
        }

        // 检测变量
        $urlArr = array_filter(explode('/', $url));
        $flag   = false; // 路由是否配对成功

        // 静态路由匹配
        if (isset($name[$url])) {
            return $name[$url];
        }

        // 动态路由匹配
        $nameCount = count($urlArr);
        foreach ($name as $key => $val) {
            $match   = array_filter(explode('/', $key));
            $matchCount = count($match);

            // 路由位数不符合
            if ($nameCount !== $matchCount) {
                continue;
            }
            
            $urlTemp = $urlArr;
            foreach ($match as $k => $item) {
                if (false !== strpos($item, '[') && false !== strpos($item, ']')) {
                    // 非必填
                    $item = str_replace(['[', ']'], '', $item);
                    if (false !== strpos($item, ':')) {
                        $item = ltrim($item, ':');

                        // 默认值
                        if (preg_match('#\{(.*?)\}#', $item, $defaultMatch)){
                            $default = $defaultMatch[1];
                            $item = str_replace($defaultMatch[0], '', $item);
                        }

                        // 类型
                        $strpos = strpos($item, '(');
                        if (false !== $strpos && false !== strpos($item, ')')) {
                            $patt = str_replace(['(', ')'], '', substr($item, $strpos));
                            $item = substr($item, 0, $strpos);
                            if (isset($urlTemp[$k]) && !preg_match("/^$patt*$/", $urlTemp[$k])) {
                                throw new Exception('参数类型错误：' . $item);
                            }
                        }
                        
                        (isset($urlTemp[$k]) && $_GET[$item] = $urlTemp[$k]) || (isset($default) && $_GET[$item] = $default);

                    } else {
                        throw new Exception('路由参数定义出错：' . '[' . $item . ']');
                    }

                    unset($match[$k]);
                    unset($urlTemp[$k]);
                } elseif (false !== strpos($item, ':')) {
                    // 必填
                    $item = ltrim($item, ':');
                    if (!isset($urlTemp[$k])) {
                        throw new Exception('缺少参数：' . $item);
                    }

                    // 类型
                    $strpos = strpos($item, '(');
                    if (false !== $strpos && false !== strpos($item, ')')) {
                        $patt = str_replace(['(', ')'], '', substr($item, $strpos));
                        $item = substr($item, 0, $strpos);
                        if (isset($urlTemp[$k]) && !preg_match("/^\d*$/", $urlTemp[$k])) {
                            throw new Exception('参数类型错误：' . $item);
                        }
                    }

                    isset($urlTemp[$k]) && $_GET[$item] = $urlTemp[$k];

                    unset($match[$k]);
                    unset($urlTemp[$k]);
                }

                // 找到对应的路由，并跳出循环
                if (implode('/', $match) == implode('/', $urlTemp)) {
                    $flag = true;
                    break;
                }
            }

            // 找到对应路由
            if ($flag === true) {
                $urlTemp = $val;
                break;
            }
        }
        
        if ($flag === false) {
            throw new Exception('该页面不存在');
        }

        return $urlTemp;
    }

    /**
     * 添加请求方式为GET的路由
     * @access public
     * @param string $route  路由
     * @param string $module 模块/控制器/方法 | 控制器/方法
     * @return void
     */
    public static function get($route, $module)
    {
        self::$routeStorage['GET'][$route] = $module;
    }

    /**
     * 添加请求方式为POST的路由
     * @access public
     * @param string $route  路由
     * @param string $module 模块/控制器/方法 | 控制器/方法
     * @return void
     */
    public static function post($route, $module)
    {
        self::$routeStorage['POST'][$route] = $module;
    }

    /**
     * 添加请求方式为PUT的路由
     * @access public
     * @param string $route  路由
     * @param string $module 模块/控制器/方法 | 控制器/方法
     * @return void
     */
    public static function put($route, $module)
    {
        self::$routeStorage['PUT'][$route] = $module;
    }

    /**
     * 添加请求方式为PATCH的路由
     * @access public
     * @param string $route  路由
     * @param string $module 模块/控制器/方法 | 控制器/方法
     * @return void
     */
    public static function patch($route, $module)
    {
        self::$routeStorage['PATCH'][$route] = $module;
    }

    /**
     * 添加请求方式为DELETE的路由
     * @access public
     * @param string $route  路由
     * @param string $module 模块/控制器/方法 | 控制器/方法
     * @return void
     */
    public static function delete($route, $module)
    {
        self::$routeStorage['DELETE'][$route] = $module;
    }

    /**
     * 添加请求方式为HEAD的路由
     * @access public
     * @param string $route  路由
     * @param string $module 模块/控制器/方法 | 控制器/方法
     * @return void
     */
    public static function head($route, $module)
    {
        self::$routeStorage['HEAD'][$route] = $module;
    }

    /**
     * 加载路由
     * @access public
     * @param array $module 切割后的路由信息数组
     * @return void
     * @throws Exception
     */
    public function load($module)
    {
        Loader::controller($module);
    }
}

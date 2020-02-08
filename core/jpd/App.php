<?php

namespace core\jpd;

/**
 * App 应用类
 * @package core\jpd
 */
class App
{
    // 调试状态
    public static $debug;

    /**
     * 运行
     * @access public
     * @return void
     * @throws Exception
     */
    public static function run()
    {
        // 初始化文件
        self::initCommon();

        // 初始通道
        self::initChannel();

        // 检测URL地址并加载
        Route::instance(
            [
                'pathinfo' => $_SERVER['REQUEST_URI'],
                'depr'     => Config::get('url_route_depr')
            ]);
    }

    /**
     * 初始通道，检测访问域名是否通过
     * @access public
     */
    public static function initChannel()
    {
        $config = Config::get('domain');

        if ($config['check']) {
            $domain = $_SERVER['SERVER_NAME'];
//            var_dump($_SERVER);die;
//            var_dump(str_replace(['http://', 'https://'], '', $_SERVER['HTTP_REFERER']));die;
            $receive = array_filter(explode(',', $config['receive']));
            $rejection = array_filter(explode(',', $config['rejection']));

            if ($receive && !in_array($domain, $receive)) {
                // 通过
                header('HTTP/1.1 403 Forbidden');
                exit;
            } elseif ($rejection && in_array($domain, $rejection)) {
                // 禁止通过
                header('HTTP/1.1 403 Forbidden');
                exit;
            }
        }
    }

    /**
     * 初始化配置
     * @access public
     * @return void
     */
    public static function initCommon()
    {
        // 初始化配置
        self::init();
        // 设置系统时区
        date_default_timezone_set(Config::get('default_timezone'));
        // 是否开启调试模式
        self::$debug = Config::get('app_debug');
        // 设置PHP错误显示
        ini_set('display_errors', self::$debug ? 'On' : 'Off');
        // 设置REQUEST HEADERS
        header('Access-Control-Allow-Origin:*');
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization,Authorization-Token, Authorization-Terminal");
    }

    /**
     * 初始化配置文件
     * @access public
     * @return void
     */
    public static function init()
    {
        // 文件路径
        $config        = APP_PATH . 'config' . EXT;
        $database      = APP_PATH . 'database' . EXT;
        $route         = APP_PATH . 'route' . EXT;
        $commonFunc    = APP_PATH . 'common' . DS . 'function' . EXT;
        $commonSysFunc = JPD_PATH . 'common' . EXT;
        $helper        = JPD_PATH . 'helper' . EXT;

        // 加载配置文件
        is_file($config) && Config::set(include $config);
        // 加载数据库配置文件
        is_file($database) && Config::set(include $database, 'database');
        // 加载路由文件
        is_file($route) && include $route;
        // 加载公共函数文件
        is_file($commonFunc) && include $commonFunc;
        // 加载系统公告函数文件
        is_file($commonSysFunc) && include $commonSysFunc;
        // 加载助手函数
        Config::get('helper_on') && is_file($helper) && include $helper;
    }
}

<?php

namespace core\jpd;

/**
 * Loader 加载类
 * @package core\jpd
 */
class Loader
{
    // 实例数组
    protected static $instance = [];

    private static $namespace = 'app';

    /**
     * 自动加载
     * @static
     * @access public
     * @param string $class 命名空间
     * @return void
     */
    public static function autoload($class)
    {
        if (!isset(self::$instance[$class])) {
            self::$instance[$class] = ROOT_PATH . str_replace('\\', '/', $class) . EXT;
        }
        
        self::require_file(self::$instance[$class]);

        App::$debug && Log::log('FILE: ' . realpath(self::$instance[$class]), 'LOADER');
    }

    /**
     * 自动加载注册
     * @static
     * @access public
     * @return void
     */
    public static function register()
    {
        spl_autoload_register('core\\jpd\\Loader::autoload', true, true);
    }

    /**
     * 包含文件
     * @static
     * @access private
     * @param string $file 文件路径(包含文件名)
     * @return void
     */
    private static function require_file($file)
    {
        if (!is_file($file)) {
            throw new Exception('文件不存在：' . $file);
        }
        require $file;
    }

    /**
     * 加载控制器
     * @static
     * @access public
     * @param string $module 模块名
     * @param string $layer  控制器层的后缀
     * @return void
     * @throws Exception
     */
    public static function controller($module, $layer = 'controller')
    {
        if (!empty($module)) {
            $namespace = self::$namespace . DS . (Route::$multiModule ? $module['module'] . DS : '') . $layer;
            $namespace = str_replace('/', '\\', $namespace);

            $file = '\\' . $namespace . '\\' . ucfirst($module['controller']);

            if (Route::$multiModule) {
                $isModule = ROOT_PATH . self::$namespace . DS . $module['module'] . DS;
                // 模块不存在
                if (!is_file($isModule)) {
                    throw new Exception('该模块不存在: ' . dirname($namespace));
                }
            }

            $isControl = ROOT_PATH . str_replace('\\', '/', $file) . EXT;
            // 控制器不存在
            if (!is_file($isControl)) {
                throw new Exception('该控制器不存在: ' . $file);
            }

            if (!isset(self::$instance[$namespace])) {
                self::$instance[$namespace] = new $file();
            }

            echo self::action($module['action'], $namespace);

            if (Route::$multiModule) {
                App::$debug &&
                Log::log(
                    '[MODULE:' . $module['module'] . '][CONTROLLER:' . $module['controller'] . '][ACTION:' .
                    $module['action'] . ']', 'RUN');
            } else {
                App::$debug &&
                Log::log('[CONTROLLER:' . $module['controller'] . '][ACTION:' . $module['action'] . ']', 'RUN');
            }
        }
    }

    /**
     * 加载方法
     * @static
     * @access public
     * @param string $action    方法名
     * @param string $namespace 命名空间
     * @return mixed
     * @throws Exception
     */
    public static function action($action, $namespace)
    {
        // 方法不存在
        if (!is_callable([self::$instance[$namespace], $action])) {
            throw new Exception(
                '该方法不存在: ' . $namespace . '\\' . $action);
        }
        return self::$instance[$namespace]->$action();
    }
}

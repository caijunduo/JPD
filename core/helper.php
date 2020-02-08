<?php
// --------------
// 助手函数
// --------------
use core\jpd\Cache;
use core\jpd\Config;
use core\jpd\Db;
use core\jpd\Request;
use core\jpd\Response;
use core\jpd\Log;
use core\jpd\Jump;

if (!function_exists('config')) {

    /**
     * 获取和设置配置参数
     * 格式：
     * config('app_debug') => get
     * config('app_debug', false) => set
     * @param string $name  参数名
     * @param null   $value 参数值
     * @param string $range 作用域
     * @return mixed
     */
    function config($name = '', $value = null, $range = '')
    {
        if (is_null($value) && is_string($name)) {
            return Config::get($name, $range);
        } else {
            return Config::set($name, $value, $range);
        }
    }
}
if (!function_exists('request')) {

    /**
     * 请求类的实例化
     * @return Request
     */
    function request()
    {
        return Request::instance();
    }
}
if (!function_exists('input')) {

    /**
     * 获取请求的参数
     * 格式:
     * input('get.name') 获取GET请求下的name参数
     * input('get.') 获取GET请求下的所有参数
     * input('name') 获取REQUEST请求下的name参数
     * @param string $name 参数或请求参数
     * @return mixed
     */
    function input($name)
    {
        if (strpos($name, '.')) {
            list($key, $value) = explode('.', $name, 2);
            return request()->input(strtoupper($key), $value);
        } else {
            $method = request()->method();
            return request()->input($method, $name);
        }
    }
}
if (!function_exists('url')) {

    /**
     * 生成URL
     * 格式：
     * url(['index/index/index', ['id' => 1]]);
     * @param array $url 生成规则数组  第一个为地址，第二个为一个参数关联数组
     * @return string
     */
    function url(array $url = [])
    {
        $param = isset($url[1]) ? $url[1] : '';
        return Jump::getUrl($url[0], $param);
    }
}
if (!function_exists('json')) {

    /**
     * JSON格式输出
     * @param mixed $data 数据
     * @return mixed
     */
    function json($data)
    {
        return Response::instance()->send($data, 'json');
    }
}
if (!function_exists('cache')) {

    /**
     * 获取/设置缓存
     * @param string $name    缓存名
     * @param string $value   缓存数据
     * @param array  $options 配置数组
     * @param string $tag     缓存标签
     * @return bool|\core\jpd\cache\Drive|mixed
     */
    function cache($name, $value = '', $options = null, $tag = null)
    {
        if (is_array($options)) {
            // 缓存操作的同时初始化
            $cache = Cache::connect($options);
        } elseif (is_array($name)) {
            // 缓存初始化
            return Cache::connect($name);
        } else {
            $cache = Cache::init();
        }
        if (is_null($name)) {
            return $cache->clear($value);
        } elseif ('' == $value) {
            return false === strpos($name, '?') ? $cache->get($name) : $cache->has(substr($name, 1));
        } elseif (is_null($value)) {
            return $cache->rm($name);
        } elseif (0 === strpos($name, '?') && '' !== $value) {
            $expire = is_numeric($options) ? $options : null;
            return $cache->remember(substr($name, 1), $value, $expire);
        } else {
            // 缓存数据
            if (is_array($options)) {
                $expire = isset($options['expire']) ? $options['expire'] : null;
            } else {
                $expire = is_numeric($options) ? $options : null;
            }
            if (is_null($tag)) {
                return $cache->set($name, $value, $expire);
            } else {
                return $cache->tag($tag)->set($name, $value, $expire);
            }
        }
    }
}
if (!function_exists('db')) {

    /**
     * 实例化数据库操作类
     * @param string $name   操作的数据表
     * @param array  $config 数据库的配置信息
     * @return \core\jpd\Db
     */
    function db($name = '', $config = [])
    {
        $db = new Db($config);
        return $db->table($name);
    }
}
if (!function_exists('log')) {

    /**
     * 写日志
     * @param string $message 日志内容
     * @param string $tag     日志标签
     * @return mixed
     */
    function log($message, $tag = '')
    {
        return Log::log($message, $tag);
    }
}
if (!function_exists('dump')) {

    /**
     * 浏览器有格式化的变量输出
     * @param mixed $var 输出的变量
     */
    function dump()
    {
        $vars   = func_get_args();
        $output = '';
        foreach ($vars as $var) {
            ob_start();
            var_dump($var);
            $output = preg_replace('/\]\=\>\n(\s+)/', '] => ', ob_get_clean());
            $output = '<pre style="width: 98% !important; background-color: #eee; margin: 10px; padding: 10px; border: solid 1px #ccc; display: inline-block; border-radius: 4px; color: #666;">' .
                htmlspecialchars($output, ENT_SUBSTITUTE) . '</pre><br />';
            echo $output;
        }
    }
}

<?php

namespace core\jpd;

/**
 * Jump 跳转类
 * @package core\jpd
 */
class Jump
{

    /**
     * 重定向
     * @param string $url   URL地址
     * @param array  $param 参数数组
     * @return void
     */
    public static function redirect($url, $param = [])
    {
        header('location: ' . self::getUrl($url, $param));
    }

    /**
     * 生成Url
     * @param string $url   地址
     * @param array  $param 参数数组
     * @return string
     */
    public static function getUrl($url, $param = [])
    {
        $param = self::getParam($param);
        $url   = self::parseUrl($url, $param);
        return $url;
    }

    /**
     * 参数数组转为url参数
     * @param array $param 参数数组
     * @return string
     */
    public static function getParam($param = [])
    {
        $str = '';
        if ($param && is_array($param)) {
            $str .= '?';
            foreach ($param as $key => $val) {
                $str .= $key . '=' . $val . '&';
            }
            $str = trim($str, '&');
        }
        return $str;
    }

    /**
     * 转换URL
     * @param string $url   地址
     * @param string $param URL参数字符串
     * @return string
     */
    public static function parseUrl($url, $param = '')
    {
        $request = Request::instance();

        if ($url) {
            if (!Config::get('route_on')) {
                $multiModule = Config::get('multi_module');
                $limit       = $multiModule ? 4 : 3;
                $url         = explode('/', $url, $limit);
                $count       = count($url);

                $module = $request->module();
                $ctrl   = $request->controller();
                if ($multiModule) {
                    if (1 == $count) {
                        array_unshift($url, $module, $ctrl);
                    } elseif (2 == $count) {
                        array_unshift($url, $module);
                    }
                } else {
                    if (1 == $count) {
                        array_unshift($url, $ctrl);
                    }
                }
            }
        }

        // 组合URL
        $url = $request->http() . $request->host() . '/' . implode('/', $url) . $param;

        return $url;
    }
}

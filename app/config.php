<?php
return [

    // +----------------------------------------------------------------------
    // | APP设置
    // +----------------------------------------------------------------------

    // 调试模式
    'app_debug'          => true,
    // 默认时区
    'default_timezone'   => 'PRC',
    // 助手函数
    'helper_on'          => false,
    // 开启路由强制模式
    'route_on'           => true,
    // 模板引擎开启
    'template_on'        => false,
    // 日志开关
    'log_on'             => false,

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 多模块
    'multi_module'       => false,
    // 默认模块名
    'default_module'     => 'index',
    // 默认控制器名
    'default_controller' => 'index',
    // 默认操作名
    'default_action'     => 'index',

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO分隔符
    'url_route_depr'     => '/',

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template'         => [
        // 模板引擎类型 支持扩展
        'type'        => 'Php',
        // 模板保存目录
        'path'        => TEMP_PATH,
        // 模板引擎普通标签开始标记
        'tpl_begin'   => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'     => '}',
        // 视图模板后缀
        'view_suffix' => 'html',
    ],

    // 模板替换字符串
    'view_replace_str' => [
        '__IMG__' => '/static/images',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache' => [
        // 驱动方式
        'type'         => 'Redis',
        // 缓存保存目录
        'path'         => CACHE_PATH,
        // 缓存前缀
        'prefix'       => '',
        // 缓存后缀
        'suffix'       => 'php',
        // 缓存有效期 0表示为永久缓存
        'expire'       => 0,
        // 是否需要子目录
        'cache_subdir' => false,
    ],

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log' => [
        // 日志记录方式，内置file、mysql支持扩展
        'type' => 'File',
        // 日志保存目录
        'path' => LOG_PATH,
    ],

    // +----------------------------------------------------------------------
    // | 异常处理设置
    // +----------------------------------------------------------------------

    'exception' => [
        // 输出形式 支持json、html 可扩展
        'console'       => 'json',
        // 错误模板
        'exception_tpl' => TPL_PATH . 'Exception.tpl',
    ],

    // +----------------------------------------------------------------------
    // | SESSION
    // +----------------------------------------------------------------------

    'session' => [
        // 客户端的名称
        'name'      => 'JPD_SESSION',
        // 保存目录
        'save_path' => SESSION_PATH,
        // 过期时间
        'expire'    => 1440,
    ],

    // +----------------------------------------------------------------------
    // | COOKIE
    // +----------------------------------------------------------------------

    'cookie' => [
        // 保存目录
        'save_path' => '',
    ],
];

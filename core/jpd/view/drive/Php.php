<?php

namespace core\jpd\view\drive;

use core\jpd\App;
use core\jpd\Config;
use core\jpd\Log;
use core\jpd\Request;
use core\jpd\Route;
use core\jpd\Template;

class Php
{
    protected $config = [
        'view_path'   => '',
        'view_suffix' => 'html',
        'view_depr'   => DS,
        'tpl_cache'   => true,
    ];
    protected $cache  = [];
    protected $template;

    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);
        if (empty($this->config['view_path'])) {
            $this->config['view_path'] = Route::$modulePath . 'view' . DS;
        }
        $this->template = new Template($this->config);
    }

    public function config($name, $value = null)
    {
        if (is_array($name)) {
            $this->template->config($name);
            $this->config = array_merge($this->config, $name);
        } elseif (is_null($value)) {
            return $this->template->config($name);
        } else {
            $this->template->$name = $value;
            $this->config[$name]   = $value;
        }
    }

    public function display($template, $data = [], $config)
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            $template = $this->parseTemplate($template);
        }
        if (!is_file($template)) {
            die('出现错误：没有模板' . $template);
        }
        App::$debug && Log::log('[' . $template . ']', 'view');
        $this->template->display($template, $data, $config);
    }

    public function parseTemplate($template)
    {
        $request = Request::instance();
        $module  = $request->module();
        $path    = !empty($module) ? APP_PATH . $module . DS . 'view' . DS :
            $this->config['view_path'];
        $depr    = $this->config['view_depr'];
        if (false !== strpos($template, '/')) {
            $template = str_replace('/', $this->config['view_depr'], $template);
        } else {
            $controller = $request->controller();
            $template   = $template ? $controller . $depr . $template :
                $controller . $depr . $request->action();
        }
        return $path . $template . '.' . ltrim($this->config['view_suffix'], '.');
    }
}

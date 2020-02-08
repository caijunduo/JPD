<?php

namespace core\jpd;

class Session
{
    // 实例化
    protected static $instance;

    // 配置信息
    protected $options = [
        'name'      => 'JPD_SESSION',
        'save_path' => SESSION_PATH,
        'expire'    => 180
    ];

    /**
     * 构造函数
     * @access public
     * @param array $options 配置信息
     */
    private function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);

        // 初始化
        $this->init();
    }

    /**
     * 实例化对象
     * @access public
     * @return \core\jpd\Session
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self(Config::get('session'));
        }
        return self::$instance;
    }

    /**
     * 初始化
     * @access public
     * @return void
     */
    private function init()
    {
        if (!empty($this->options['name'])) {
            ini_set('session.name', $this->options['name']);
        }
        if (!empty($this->options['save_path'])) {
            if (!is_dir($this->options['save_path'])) {
                mkdir($this->options['save_path'], 0755, true);
            }
            ini_set('session.save_path', $this->options['save_path']);
        }
        if (!empty($this->options['expire'])) {
            ini_set('session.cache_expire', $this->options['expire']);
        }

        // 开启session
        self::start();
    }

    /**
     * 设置SESSION
     * @param  string $name   键，格式为：test[.test1][.test2]
     * @param mixed   $value  值
     * @param integer $expire 保留时间
     * @return bool
     */
    public static function set($name, $value = null, $expire = null)
    {
        if (!is_null($expire)) {
            session_cache_expire($expire);
        }

        if (is_string($name)) {
            if (!strpos($name, '.')) {
                $_SESSION[$name] = $value;
            } else {
                // 多维
                $name = explode('.', $name);
                $fn   = function ($name, $value, &$data) use (&$fn) {
                    $str = array_shift($name);
                    if (!$name) {
                        return $data[$str] = $value;
                    }
                    $data[$str] = isset($data[$str]) ? $data[$str] : [];
                    return $fn($name, $value, $data[$str]);
                };
                $fn($name, $value, $_SESSION);
            }
        }

        return true;
    }

    /**
     * 获取SESSION
     * @param string $name 键，格式为：test[.test1][.test2]
     * @return bool|mixed|string
     */
    public static function get($name = '')
    {
        // 为空返回全部
        if (empty($name)) {
            return $_SESSION;
        }

        // 有值则返回指定值
        if (is_string($name)) {
            if (!strpos($name, '.')) {
                return isset($_SESSION[$name]) ? $_SESSION[$name] : '';
            } else {
                // 多维
                $name = explode('.', $name);
                $list = $_SESSION;
                $fn   = function ($name) use (&$fn, &$list) {
                    $str = array_shift($name);
                    if (!empty($str)) {
                        $list = $list[$str];
                        $fn($name);
                    }
                    return $list;
                };
                return $fn($name);
            }
        }

        return true;
    }

    /**
     * 删除SESSION
     * @param string $name 键，格式为：test[.test1][.test2]
     * @return bool
     */
    public static function clear($name = '')
    {
        // 为空清空全部
        if (empty($name)) {
            session_unset();
        }

        // 有值则删除指定key
        if (is_string($name)) {
            if (!strpos($name, '.')) {
                unset($_SESSION[$name]);
            } else {
                $name = explode('.', $name);
                $fn   = function ($name, &$data) use (&$fn) {
                    foreach ($data as $key => $val) {
                        $str = array_shift($name);
                        if ($str == $key) {
                            if (!$name) unset($data[$key]);
                            else is_array($val) && $fn($name, $data[$key]);
                        }
                    }
                    return true;
                };
                $fn($name, $_SESSION);
            }
        }

        return true;
    }

    /**
     * 开启SESSION
     * @access private
     */
    private static function start()
    {
        session_start();
    }

    /**
     * 关闭SESSION
     * @access private
     */
    private static function close()
    {
        session_write_close();
    }

    /**
     * 析构函数
     * @access public
     */
    public function __destruct()
    {
        self::close();
    }
}

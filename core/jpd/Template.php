<?php

namespace core\jpd;

/**
 * Template 模板类
 * @package core\jpd
 */
class Template
{

    /**
     * 模板变量
     * @access protectedd
     * @var array
     */
    protected $data = [];

    /**
     * 模板内容
     * @access private
     * @var string
     */
    private $content;

    /**
     * 引擎配置
     * @access protected
     * @var array
     */
    protected $config = [
        'view_path'          => '',
        // 模板路径
        'view_suffix'        => 'html',
        // 默认模板文件后缀
        'view_depr'          => DS,
        'cache_suffix'       => 'php',
        // 默认模板缓存后缀
        'tpl_begin'          => '{',
        // 模板引擎普通标签开始标记
        'tpl_end'            => '}',
        // 模板引擎普通标签结束标记
        'tpl_cache'          => true,
        // 是否开启模板编译缓存，设为false则每次都会重新编译
        'compile_type'       => 'file',
        // 模板编译类型
        'cache_prefix'       => '',
        // 模板缓存前缀
        'cache_time'         => 0,
        // 模板缓存有效期 0为永久，（以数字为值，单位为秒）
        'display_cache'      => false,
        // 模板渲染缓存
        'cache_id'           => '',
        // 模板缓存ID
        'taglib_begin'       => '{',
        // 标签库标签开始标记
        'taglib_end'         => '}',
        // 标签库标记结束标记
        'taglib_build_in'    => 'cx',
        // 内置标签库名字
        'tpl_replace_string' => []
    ]// 视图替换字符串
    ;

    /**
     * 记录所有包含的文件路径及更新时间
     * @access protected
     * @var array
     */
    protected $includeFile = [];

    /**
     * 模板引擎
     * @access protected
     * @var object
     */
    protected $storage;

    /**
     * 构造函数
     * @access public
     * @param array $config 引擎配置参数
     * @return void
     */
    public function __construct(array $config = [])
    {
        $this->config['cache_path'] = TEMP_PATH;
        $this->config               = array_merge($this->config, $config);
        // 引擎标记
        $this->config['tpl_begin']    = preg_quote($this->config['tpl_begin'], '/');
        $this->config['tpl_end']      = preg_quote($this->config['tpl_end'], '/');
        $this->config['taglib_begin'] = preg_quote($this->config['taglib_begin'], '/');
        $this->config['taglib_end']   = preg_quote($this->config['taglib_end'], '/');
        // 初始化模板编译
        $type          = $this->config['compile_type'] ? $this->config['compile_type'] : 'File';
        $class         = false !== strpos($type, '\\') ? $type : 'core\\jpd\\template\\drive\\' . ucfirst($type);
        $this->storage = new $class();
    }

    /**
     * 模板变量赋值
     * @access public
     * @param string|array $name  变量名
     * @param string       $value 变量值
     * @return void
     */
    public function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }
    }

    /**
     * 模板引擎参数赋值
     * @access public
     * @param string $name  参数名
     * @param string $value 参数值
     * @return void
     */
    public function __set($name, $value)
    {
        $this->config[$name] = $value;
    }

    /**
     * 设置或获取模板参数
     * @access public
     * @param string $config 模板参数
     * @return string|boolean
     */
    public function config($config)
    {
        if (is_array($config)) {
            $this->config = array_merge($this->config, $config);
        } elseif (isset($this->config[$config])) {
            return $this->config[$config];
        }
        return true;
    }

    /**
     * 渲染模板文件
     * @access public
     * @param string $template 模板文件
     * @param array  $data     模板变量
     * @param array  $config   模板参数
     * @return void
     * @throws Exception
     */
    public function display($template, $data = [], $config = [])
    {
        if ($config) {
            $this->config($config);
        }
        if (!empty($this->config['cache_id']) && $this->config['display_cache']) {
            // 读取页面缓存
            $cacheContent = Cache::get($this->config['cache_id']);
            if (false === $cacheContent) {
                echo $cacheContent;
                return;
            }
        }
        $template = $this->parseTemplateFile($template);
        if ($template) {
            $cacheFile = $this->config['cache_path'] . $this->config['cache_prefix'] . md5($template) . '.' .
                ltrim($this->config['cache_suffix'], '.');
            $content   = file_get_contents($template);
            if (!$this->checkCache($cacheFile)) {
                // 没有缓存，重新编译
                $this->compiler($content, $cacheFile);
            }
            // 开启缓存
            ob_start();
            ob_implicit_flush(0);
            // 读取编译缓存
            $this->storage->read($cacheFile, $data);
            // 获取并清空缓存
            $content = ob_get_clean();
            if (!empty($this->config['cache_id']) && $this->config['display_cache']) {
                // 输出页面输出
                Cache::set($this->config['cache_id'], $content, $this->config['cache_time']);
            }
            echo $content;
        }
    }

    /**
     * 检测编译缓存是否有效
     * 如果无效则重新编译
     * @access public
     * @param  string $cacheFile 缓存文件名
     * @return boolean
     */
    public function checkCache($cacheFile)
    {
        // 未开始缓存功能
        if (!$this->config['tpl_cache']) {
            return false;
        }
        // 缓存文件不存在
        if (!is_file($cacheFile)) {
            return false;
        }
        // 缓存文件不可读
        if (!$handle = @fopen($cacheFile, 'r')) {
            return false;
        }
        // 读取第一行
        preg_match('/\/\*(.*?)\*\//', fgets($handle), $matches);
        if (!isset($matches[1])) {
            return false;
        }
        $includeFile = unserialize($matches[1]);
        if (!is_array($includeFile)) {
            return false;
        }
        // 检查模板文件是否更新
        foreach ($includeFile as $path => $time) {
            if (is_file($path) && filemtime($path) > $time) {
                return false;
            }
        }
        return $this->storage->check($cacheFile, $this->config['cache_time']);
    }

    /**
     * 模板编译
     * @access public
     * @param string $content   模板内容
     * @param string $cacheFile 缓存文件名
     * @return void
     */
    public function compiler($content, $cacheFile)
    {
        // 模板解析
        $this->parse($content);
        // 模板替换字符串输出
        $replace = $this->config['tpl_replace_string'];
        $content = str_replace(array_keys($replace), array_values($replace), $content);
        // 添加安全代码及模板引用记录
        $content = '<?php if (!defined(\'JPD_PATH\')) exit(); /*' . serialize($this->includeFile) . '*/?>' . "\n" .
            $content;
        // 编译储存
        $this->storage->write($cacheFile, $content);
        // 清空模板引用记录
        $this->includeFile = [];
        return;
    }

    /**
     * 模板解析入口
     * @access public
     * @param string $content 要解析的模板内容
     * @return void
     */
    public function parse(&$content)
    {
        // 内容为空
        if (empty($content)) {
            return;
        }
        // 检测include语法
        $this->parseInclude($content);
        // 检测PHP语法
        $this->parsePhp($content);

        // 引入内置标签库
        $tagLibs = explode(',', $this->config['taglib_build_in']);
        foreach ($tagLibs as $tag) {
            $this->parseTagLib($tag, $content, true);
        }

        // 检测普通标签
        $this->parseTag($content);
    }

    /**
     * 解析include标签
     * @access public
     * @param string $content 要解析的内容
     * @return void
     */
    public function parseInclude(&$content)
    {
        $regx = $this->getRegex('include');
        $fun  = function ($content) use (&$fun, &$regx, &$content) {
            if (preg_match_all($regx, $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $array = $this->parseAttr($match[1]);
                    $file  = $array['file'];
                    unset($array['file']);
                    $parseStr = $this->parseTemplateName($file);
                    $content  = str_replace($match[0], $parseStr, $content);
                }
            }
            unset($matches);
        };
        // 替换incluude标签
        $fun($content);
        return;
    }

    /**
     * 检查PHP语法
     * @access public
     * @param string $content 要检查的内容
     * @return void
     */
    public function parsePhp(&$content)
    {
        // 短标签以echo输出
        $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>' . "\n", $content);
        return;
    }

    /**
     * 解析模板中使用函数的变量
     * @access public
     * @param string $varStr 变量数据
     * @return void
     */
    public function parseVarFunction(&$varStr)
    {
        if (false === strpos($varStr, '|')) {
            return;
        }
        $arr       = explode('|', $varStr);
        $temp_str  = array_shift($arr);
        $temp_arg2 = [];
        foreach ($arr as $key) {
            if (false !== strpos($key, '=')) {
                list($name, $args) = explode('=', $key);
                $args = explode(',', $args);
                foreach ($args as $arg) {
                    $temp_arg2[] = ('###' === trim($arg)) ? $temp_str : $arg;
                }
                (1 === count($temp_arg2)) && array_unshift($temp_arg2, $temp_str);
                $temp_str = $name . '(' . implode(',', $temp_arg2) . ')';
            } else {
                $temp_str = $key . '(' . $temp_str . ')';
            }
        }
        $varStr = $temp_str;
        return;
    }

    /**
     * 解析TagLib标签库的标签
     * @access public
     * @param string $tagLib  标签库名
     * @param string $content 要解析的模板内容
     * @param bool   $hide    是否要隐藏标签库前缀
     * @return void
     */
    public function parseTagLib($tagLib, &$content, $hide = false)
    {
        if (false !== strpos($tagLib, '\\')) {
            // 支持指定标签库的命名空间
            $className = $tagLib;
        } else {
            $className = '\\core\\jpd\\template\\taglib\\' . ucwords($tagLib);
        }
        $tLib = new $className($this);
        $tLib->parseTag($content, $hide ? '' : $hide);
        return;
    }

    /**
     * 分析加载的模板文件并读取内容 支持多个模板文件读取
     * @access public
     * @param  string $templateName 模板文件名
     * @return string
     * @throws Exception
     */
    public function parseTemplateName($templateName)
    {
        $array    = explode(',', $templateName);
        $parseStr = '';
        foreach ($array as $templateName) {
            if (empty($templateName)) {
                continue;
            }
            $template = $this->parseTemplateFile($templateName);
            if ($template) {
                $parseStr .= file_get_contents($template);
            }
        }
        return $parseStr;
    }

    /**
     * 解析模板文件名
     * @access public
     * @param string $template 文件名
     * @return string|false
     * @throws Exception
     */
    public function parseTemplateFile($template)
    {
        if ('' == pathinfo($template, PATHINFO_EXTENSION)) {
            $request = Request::instance();
            $module  = $request->module();
            $path    = !empty($module) ? APP_PATH . $module . DS . 'view' . DS : $this->config['view_path'];
            $depr    = $this->config['view_depr'];
            if (false !== strpos($template, '/')) {
                $template = str_replace('/', $this->config['view_depr'], $template);
            } else {
                $controller = $request->controller();
                $template   = $template ? $controller . $depr . $template : $controller . $depr . $request->action();
            }
            $template = $path . $template . '.' . ltrim($this->config['view_suffix'], '.');
        }
        if (is_file($template)) {
            $this->includeFile[$template] = filemtime($template);
            App::$debug && Log::log('FILE: ' . $template, 'TEMPLATE');
            return $template;
        } else {
            App::$debug && Log::log('[ERROR][FILE: ' . $template . ' NOT FOUND]', 'TEMPLATE');
            throw new Exception('template not found : ' . $template);
        }
    }

    /**
     * 分析标签属性
     * @access public
     * @param  string $str  属性字符串
     * @param  string $name 不为空时返回指定的属性名
     * @return array
     */
    public function parseAttr($str, $name = null)
    {
        $regx  = '/\s+(?P<name>[\w-]+)(=[\'"](?P<value>(.*?))[\'"])/';
        $array = [];
        if (preg_match_all($regx, $str, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $array[$match['name']] = $match['value'];
            }
            unset($matches);
        }
        if (!empty($name) && isset($array[$name])) {
            return $array[$name];
        } else {
            return $array;
        }
    }

    /**
     * 模板变量解析，支持使用函数
     * 格式: {$varname|function1|function2=arg1, arg2}
     * @access public
     * @param  string $varStr 变量数据
     * @return void
     */
    public function parseVar(&$varStr)
    {
        $varStr = trim($varStr);
        if (false !== $pos = strpos($varStr, '.')) {
            $temp_obj = explode('.', $varStr);
            $first    = array_shift($temp_obj);
            $temp_str = '';
            foreach ($temp_obj as $key) {
                $temp_str .= '["' . $key . '"]';
            }
            $varStr = $first . $temp_str;
        } elseif (false !==
            $pos = strpos($varStr, '[') &&
                preg_match_all('/\[\d+\]|\[[\'\"]\w+[\'\"]\]/', $varStr, $matches, PREG_SET_ORDER)) {
            $first    = substr($varStr, 0, strpos($varStr, '['));
            $temp_str = '';
            foreach ($matches as $array) {
                $temp_str .= str_replace([
                    '\'',
                    '"'
                ],
                    [
                        '"',
                        '"'
                    ], $array[0]);
            }
            $varStr = $first . $temp_str;
        }
        return;
    }

    /**
     * 模板普通标签解析
     * @access public
     * @param  string $content 要解析的内容
     * @return void
     */
    public function parseTag(&$content)
    {
        $regx = $this->getRegex('tag');
        if (preg_match_all($regx, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $str = stripslashes($match[1]);
                $tag = substr($str, 0, 1);
                switch ($tag) {
                    case '$':
                        $this->parseVar($str);
                        $this->parseVarFunction($str);
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case ':':
                        // 输出函数
                        $str = substr($str, 1);
                        $str = '<?php echo ' . $str . '; ?>';
                        break;
                    case '~':
                        // 执行函数
                        $str = substr($str, 1);
                        $str = '<?php ' . $str . '; ?>';
                        break;
                    case '/':
                        // 标签注释
                        $flag2 = substr($str, 1, 1);
                        if ('/' == $flag2 || ('*' == $flag2 && '*/' == substr(rtrim($str), -2))) {
                            $str = '';
                        }
                        break;
                    default:
                        // 未识别
                        $str = $this->config['tpl_begin'] . $str . $this->config['tpl_end'];
                }
                $content = str_replace($match[0], $str, $content);
            }
            unset($matches);
        }
        return;
    }

    /**
     * 按标签生成正则
     * @access public
     * @param  string $tagName 标签名
     * @return string
     */
    public function getRegex($tagName)
    {
        $regx  = '';
        $begin = ltrim($this->config['tpl_begin'], '\\');
        $end   = ltrim($this->config['tpl_end'], '\\');
        if ('tag' == $tagName) {
            $regx = $begin . '\s*((?:[\$\:\~][a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(.*?)|[\/](.*?)))\s*' . $end;
        } else {
            switch ($tagName) {
                case 'include':
                    $regx = $begin . '(?:include(.*?))\/' . $end;
                    break;
                case 'tagLib':
                    $regx = $begin . '(?:taglib(.*?))\/' . $end;
                    break;
            }
        }
        return '/' . $regx . '/';
    }
}

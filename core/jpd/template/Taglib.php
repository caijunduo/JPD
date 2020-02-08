<?php

namespace core\jpd\template;

use core\jpd\Exception;

/**
 * 标签库基类
 * @package core\jpd\template
 */
class Taglib
{
    /**
     * 当前模板对象
     * @access protected
     * @var object
     */
    protected $tpl;
    /**
     * 标签定义
     * @access protected
     * @var array
     */
    protected $tags = [];

    protected $comparison = [
        ' nheq ' => ' !== ',
        ' heq '  => ' === ',
        ' neq '  => ' != ',
        ' eq '   => ' == ',
        ' egt '  => ' >= ',
        ' gt '   => ' > ',
        ' elt '  => ' <= ',
        ' lt '   => ' < ',
    ];

    /**
     * 构造函数
     * @access public
     * @param \stdClass $emplate 模板引擎对象
     */
    public function __construct($template)
    {
        $this->tpl = $template;
    }

    /**
     * 按标签库替换页面中的标签
     * @access public
     * @param string $content 模板内容
     * @param string $lib     标签库名
     * @return void
     * @throws Exception
     */
    public function parseTag(&$content, $lib = '')
    {
        $tags = [];
        $lib  = $lib ? strtolower($lib) . ':' : '';
        foreach ($this->tags as $name => $val) {
            $close                      = !isset($val['close']) || $val['close'] ?
                1 : 0;
            $tags[$close][$lib . $name] = $name;
            // 存在别名
            if (isset($val['alias'])) {
                $array = (array)$val['alias'];
                foreach (explode(',', $array[0]) as $v) {
                    $tags[$close][$lib . $v] = $name;
                }
            }
        }
        // 闭合标签
        if (!empty($tags[1])) {
            $nodes = [];
            $regx  = $this->getRegx(array_keys($tags[1]), 1);
            if (preg_match_all($regx, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                $right = [];
                foreach ($matches as $match) {
                    if ('' == $match[1][0]) {
                        $name = strtolower($match[2][0]);
                        if (!empty($right[$name])) {
                            $nodes[$match[0][1]] = [
                                'name'  => $name,
                                'begin' => array_pop($right[$name]),
                                'end'   => $match[0],
                            ];
                        }
                    } else {
                        $right[$match[1][0]][] = $match[0];
                    }
                }
                unset($right, $matches);
                krsort($nodes);
            }

            // 内容占位符
            $break = '<!--###break###--!>';

            if ($nodes) {
                $beginArray = [];
                foreach ($nodes as $pos => $node) {
                    // 标签名
                    $name  = $tags[1][$node['name']];
                    $alias = $lib . $name != $node['name'] ?
                        ($lib ? strstr($node['name'], $lib) : $node['name']) :
                        '';
                    // 解析属性
                    $attrs   = $this->parseAttr($node['begin'][0], $name, $alias);
                    $method  = 'tag' . $name;
                    $replace = explode($break, $this->$method($attrs, $break));

                    if (count($replace) > 1) {
                        while ($beginArray) {
                            $begin = end($beginArray);
                            // 判断当前标签尾的位置是否在栈中最后一个标签头的后面，是则为子标签
                            if ($node['end'][1] > $begin['pos']) {
                                break;
                            } else {
                                // 不为子标签时，取出栈中最后一个标签头
                                $begin = array_pop($beginArray);
                                // 替换标签头部
                                $content = substr_replace($content, $begin['str'], $begin['pos'], $begin['len']);
                            }
                        }
                        $content      = substr_replace($content, $replace[1], $node['end'][1], strlen($node['end'][0]));
                        $beginArray[] = [
                            'pos' => $node['begin'][1],
                            'len' => strlen($node['begin'][0]),
                            'str' => $replace[0]
                        ];
                    }
                }
                while ($beginArray) {
                    $begin   = array_pop($beginArray);
                    $content = substr_replace($content, $begin['str'], $begin['pos'], $begin['len']);
                }
            }

        }
        // 自闭合标签
        if (!empty($tags[0])) {
            $regx    = $this->getRegx(array_keys($tags[0]), 0);
            $content = preg_replace_callback($regx, function ($matches) use (&$tags, &$lib) {
                $name   = $tags[0][strtolower($matches[1])];
                $alias  = $lib . $name != $matches[1] ?
                    ($lib ? strstr($matches[1], $lib) : $matches[1]) : '';
                $attrs  = $this->parseAttr($matches[0], $name, $alias);
                $method = 'tag' . $name;
                return $this->$method($attrs, '');
            }, $content);
        }
    }

    /**
     * 按标签获取正则
     * @access public
     * @param array|string $tags  标签名
     * @param boolean      $close 是否为闭合标签
     * @return string
     */
    public function getRegx($tags, $close)
    {
        $begin   = $this->tpl->config('taglib_begin');
        $end     = $this->tpl->config('taglib_end');
        $tagName = is_array($tags) ? implode('|', $tags) : $tags;
        if ($close) {
            $regx = $begin . '(?:(' . $tagName . ')\b(?>[^' . $end . ']*)|\/(' . $tagName . '))' . $end;
        } else {
            $regx = $begin . '(' . $tagName . ')\b(?>[^' . $end . ']*)' . $end;
        }
        return '/' . $regx . '/is';
    }

    /**
     * 以正则的方式分析标签属性
     * @access public
     * @param string $str   标签属性字符串
     * @param string $name  标签名
     * @param string $alias 别名
     * @return array
     * @throws Exception
     */
    public function parseAttr($str, $name, $alias = '')
    {
        $regx   = '/\s+(?>(?P<name>[\w-]+)\s*)=(?>\s*)([\"\'])(?P<value>(?:(?!\\2).)*)\\2/is';
        $result = [];
        if (preg_match_all($regx, $str, $matches)) {
            foreach ($matches['name'] as $key => $val) {
                $result[$val] = $matches['value'][$key];
            }
            if (!$this->tags[$name]) {
                // Not Complete
            } else {
                $tag = $this->tags[$name];
                if (!empty($alias) && isset($tag['alias'])) {
                    $type          = !empty($tag['alias'][1]) ?
                        $tag['alias'][1] : 'type';
                    $result[$type] = $alias;
                }
            }
        } else {
            // 允许直接使用表达式的标签
            if (!empty($this->tags[$name]['expression'])) {
                // Not Complete
            } elseif (empty($this->tags[$name]) || !empty($this->tags[$name]['attr'])) {
                throw new Exception('tag error: ' . $this->tags[$name]);
            }
        }
        return $result;
    }

    /**
     * 解析条件表达式
     * @access public
     * @param string $condition 表达式属性内容
     * @return string
     */
    public function parseCondition($condition)
    {
        $condition = str_ireplace(array_keys($this->comparison), array_values($this->comparison), $condition);
        $this->tpl->parseVar($condition);
        return $condition;
    }

    /**
     * 自动识别并构建变量
     * @access public
     * @param string $name 变量描述
     * @return string
     */
    public function autoBuildVar(&$name)
    {
        $flag = substr($name, 0, 1);
        if (':' == $flag) {
            // 以:开头为函数调用，解析前去掉:
            $name = substr($name, 1);
        } elseif ('$' != $flag && preg_match('/[a-zA-Z_]/', $flag)) {
            // 常量
            if (defined($name)) {
                return $name;
            }
            // 不为常量，不为变量，则添加$
            $name = '$' . $name;
        }
        $this->tpl->parseVar($name);
        $this->tpl->parseVarFunction($name);
        return $name;
    }

    /**
     * 获取标签列表
     * @access public
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }
}

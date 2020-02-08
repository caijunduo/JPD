<?php

namespace core\jpd\template\taglib;

use core\jpd\template\Taglib;

/**
 * Cx内置标签库
 * @package core\jpd\template\taglib
 */
class Cx extends Taglib
{

    /**
     * 标签定义：attr 属性列表 close 是否闭合(0 或 1 默认1) alias 别名[0: 别名, 1:对应的属性] expression
     * 是否能有表达式
     * @access protected
     * @var array
     */
    protected $tags = [
        'php'        => ['attr' => ''],
        'loop'       => ['attr' => 'name,id,key,offset,length,mod'],
        'foreach'    => [
            'attr'       => 'name,id,item,key,index,offset,length,mod',
            'expression' => true
        ],
        'if'         => [
            'attr'       => 'condition',
            'expression' => true
        ],
        'elseif'     => [
            'attr'       => 'condition',
            'expression' => true
        ],
        'else'       => [
            'attr'  => '',
            'close' => 0
        ],
        'switch'     => [
            'attr'       => 'name',
            'expression' => true
        ],
        'case'       => [
            'attr'       => 'value,break',
            'expression' => true
        ],
        'default'    => [
            'attr'  => '',
            'close' => 0
        ],
        'empty'      => ['attr' => 'name'],
        'compare'    => [
            'attr'  => 'name,value,type',
            'alias' => [
                'eq,equal,notequal,neq,gt,lt,egt,elt,heq,nheq',
                'type'
            ]
        ],
        'notempty'   => ['attr' => 'name'],
        'present'    => ['attr' => 'name'],
        'notpresent' => ['attr' => 'name'],
        'defined'    => ['attr' => 'name'],
        'notdefined' => ['attr' => 'name'],
        'for'        => ['attr' => 'start,end,name,comparison,step'],
        'load'       => [
            'attr'  => 'file,href,type,value,basepath',
            'close' => 0,
            'alias' => [
                'import,css,js',
                'type'
            ]
        ],
    ];

    /**
     * php标签解析
     * 格式: {php}echo $name{/php}
     * @access public
     * @param  array  $tag     标签属性
     * @param  string $content 标签内容
     * @return string
     */
    public function tagPhp($tag, $content)
    {
        $parseStr = '<?php ' . $content . ' ?>';
        return $parseStr;
    }

    /**
     * loop标签解析
     * 格式:
     * {loop name="data" id="user" offset="0" length="5" empty=""}
     * {$user.username}
     * {/loop}
     * @access public
     * @param array  $tag     标签属性
     * @param string $content 标签内容
     * @return string|void
     */
    public function tagLoop($tag, $content)
    {
        $name   = $tag['name'];
        $id     = $tag['id'];
        $empty  = isset($tag['empty']) ? $tag['empty'] : '';
        $key    = !empty($tag['key']) ? $tag['key'] : 'i';
        $offset = !empty($tag['offset']) && is_numeric($tag['offset']) ?
            intval($tag['offset']) : 0;
        $length = !empty($tag['length']) && is_numeric($tag['length']) ?
            intval($tag['length']) : 'null';

        $parseStr = '<?php ';
        // 允许使用函数设定数据集 {loop name=":fun(arg)}" id="v"}{$v.name}{/loop}
        if (':' == substr($name, 0, 1)) {
            $var      = '$_' . uniqid();
            $name     = $this->autoBuildVar($name);
            $parseStr .= $var . ' = ' . $name . ';';
            $name     = $var;
        } else {
            $name = $this->autoBuildVar($name);
        }

        $parseStr .= 'if (is_array(' . $name . ')): $' . $key . ' = 0;';
        // 设置数组长度
        if (0 !== $offset || 'null' !== $length) {
            if (!isset($var)) {
                $var = '$_' . uniqid();
            }
            $parseStr .= $var . ' = is_array(' . $name . ') ? array_slice(' . $name . ', ' . $offset . ', ' . $length . ', true) : ' . $name . '->slice(' . $offset . ', ' . $length . ', true);';
        } else {
            $var = &$name;
        }
        $parseStr .= 'if (0 == count(' . $var . ')): echo "' . $empty . '" ;';
        $parseStr .= 'else: ';
        $parseStr .= 'foreach (' . $var . ' as $key => $' . $id . '): ';
        // 设置了奇偶
        if (isset($tag['mod'])) {
            $mod      = $tag['mod'];
            $parseStr .= '$mod = ($' . $key . ' % ' . $mod . '); ';
        }
        $parseStr .= '++$' . $key . ';?>';
        $parseStr .= $content;
        $parseStr .= '<?php endforeach; endif; else: echo "' . $empty . '"; endif; ?>';

        if (!empty($parseStr)) {
            return $parseStr;
        }
        return;
    }

    /**
     * foreach标签解析
     * 格式:
     * {foreach expression="$name as $key => $sad"}
     * {/foreach}
     * @access public
     * @param array  $tag     标签属性
     * @param string $content 标签内容
     * @return string|void
     */
    public function tagForeach($tag, $content)
    {
        if (!empty($tag['expression'])) {
            $expression = ltrim(rtrim($tag['expression'], ')'), '(');
            $expression = $this->autoBuildVar($expression);
            $parseStr   = '<?php foreach (' . $expression . '): ?>';
            $parseStr   .= $content;
            $parseStr   .= '<?php endforeach; ?>';
            return $parseStr;
        }
        $name   = $tag['name'];
        $key    = !empty($tag['key']) ? $tag['key'] : 'key';
        $index  = isset($tag['index']) ? $tag['index'] : 'i';
        $item   = !empty($tag['id']) ? $tag['id'] : $tag['item'];
        $empty  = isset($tag['empty']) ? $tag['empty'] : '';
        $offset = !empty($tag['offset']) && is_numeric($tag['offset']) ?
            intval($tag['offset']) : 0;
        $length = !empty($tag['length']) && is_numeric($tag['length']) ?
            intval($tag['length']) : 'null';

        $parseStr = '<?php ';
        // 允许使用函数设定数据集
        if (':' == substr($name, 0, 1)) {
            $var      = '$_' . uniqid();
            $name     = $this->autoBuildVar($name);
            $parseStr .= $var . ' = ' . $name;
            $name     = $var;
        } else {
            $name = $this->autoBuildVar($name);
        }

        $parseStr .= 'if (is_array(' . $name . ')): $' . $index . ' = 0;';
        // 设置数组长度
        if (0 != $offset || 'null' != $length) {
            if (!isset($var)) {
                $var = '$_' . uniqid();
            }
            $parseStr .= $var . ' = is_array(' . $name . ') ? array_slice(' . $name . ', ' . $offset . ', ' . $length . ', true) : ' . $name . '->slice(' . $offset . ', ' . $length . ', true);';
        } else {
            $var = &$name;
        }

        $parseStr .= 'if (0 == count(' . $var . ')): echo "' . $empty . '"; ';
        $parseStr .= 'else: ';
        $parseStr .= 'foreach (' . $var . ' as $' . $key . ' => $' . $item . '): ';
        // 设置了奇偶
        if (isset($tag['mod'])) {
            $mod      = $tag['mod'];
            $parseStr .= '$mod = ($' . $index . ' % ' . $mod . ');';
        }
        $parseStr .= '++$' . $index;
        $parseStr .= '?>';
        $parseStr .= $content;
        $parseStr .= '<?php endforeach; endif; else: echo "' . $empty . '"; endif; ?>';

        if (!empty($parseStr)) {
            return $parseStr;
        }
        return;
    }

    /**
     * for标签解析
     * 格式:
     * {for start="0" end="100" $step="1" $name="k" $comparison="neq"}
     * {$username[$k]}
     * {/for}
     * @access public
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagFor($tag, $content)
    {
        // 默认属性
        $start      = 0;
        $end        = 0;
        $step       = 1;
        $comparison = 'lt';
        $name       = 'i';
        $rand       = rand();

        // 获取属性
        foreach ($tag as $key => $value) {
            $value = trim($value);
            $flag  = substr($value, 0, 1);
            if ('$' == $flag || ':' == $flag) {
                $value = $this->autoBuildVar($value);
            }

            switch ($key) {
                case 'start':
                    $start = $value;
                    break;
                case 'end':
                    $end = $value;
                    break;
                case 'step':
                    $step = $value;
                    break;
                case 'comparison':
                    $comparison = $value;
                    break;
                case 'name':
                    $name = $value;
                    break;
            }
        }

        $parseStr = '<?php $_FOR_START_' . $rand . ' = ' . $start . '; $_FOR_END_' . $rand . ' = ' . $end . '; ';
        $parseStr .= 'for ($' . $name . '=$_FOR_START_' . $rand . '; ' . $this->parseCondition('$' . $name . ' ' . $comparison . ' $_FOR_END_' . $rand) . '; $' . $name . '+=' . $step . '): ?>';
        $parseStr .= $content;
        $parseStr .= '<?php endfor; ?>';
        return $parseStr;

    }

    /**
     * compare标签解析
     * 格式:
     * {compare name="$name" value="0" type="eq"}content{/compare}
     * @access public
     * @param $tag
     * @param $content
     * @return string
     */
    public function tagCompare($tag, $content)
    {
        $name  = $tag['name'];
        $value = $tag['value'];
        $type  = !empty($tag['type']) ? $tag['type'] : 'eq';
        $name  = $this->autoBuildVar($name);
        $flag  = substr($value, 0, 1);
        if ('$' == $flag || ':' == $flag) {
            $value = $this->autoBuildVar($value);
        } else {
            $value = '\'' . $value . '\'';
        }
        switch ($type) {
            case 'equal':
                $type = 'eq';
                break;
            case 'notequal':
                $type = 'neq';
                break;
        }
        $type     = $this->parseCondition(' ' . $type . ' ');
        $parseStr = '<?php if (' . $name . ' ' . $type . ' ' . $value . '): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * if标签解析
     * 格式:
     * {if condition="$condition1"}
     * {elseif condition="$condition2"}
     * {else/}
     * {/if}
     * @access public
     * @param array  $tag     标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagIf($tag, $content)
    {
        $condition = !empty($tag['expression']) ? $tag['expression'] :
            $tag['condition'];
        $condition = $this->parseCondition($condition);
        $parseStr  = '<?php if (' . $condition . '): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * elseif标签解析
     * 格式: 见if标签
     * @access public
     * @param array  $tag     标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagElseif($tag, $content)
    {
        $condition = !empty($tag['expression']) ? $tag['expression'] :
            $tag['condition'];
        $condition = $this->parseCondition($condition);
        $parseStr  = '<?php elseif (' . $condition . '): ?>';
        return $parseStr;
    }

    /**
     * else标签解析
     * 格式: 见if标签
     * @access public
     * @param array $tag 标签属性
     * @return string
     */
    public function tagElse($tag)
    {
        $parseStr = '<?php else: ?>';
        return $parseStr;
    }

    /**
     * switch标签解析
     * 格式:
     * {switch name="name"}
     * {case value="1" break="false"}1{/case}
     * {case value="2"}2{/case}
     * {default /}other
     * {/switch}
     * @access public
     * @param array  $tag     标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagSwitch($tag, $content)
    {
        $name     = !empty($tag['expression']) ? $tag['expression'] :
            $tag['name'];
        $name     = $this->autoBuildVar($name);
        $parseStr = '<?php switch (' . $name . '): ?>' . $content . '<?php endswitch; ?>';
        return $parseStr;
    }

    /**
     * case标签解析
     * 详细见switch标签
     * @access public
     * @param array  $tag     标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagCase($tag, $content)
    {
        $value = !empty($tag['expression']) ? $tag['expression'] :
            $tag['value'];
        $flag  = substr($value, 0, 1);
        if ('$' == $flag || ':' == $flag) {
            $value = $this->autoBuildVar($value);
        } else {
            $value = '\'' . $value . '\'';
        }
        $parseStr = '<?php case ' . $value . ' : ?>' . $content;
        $isBreak  = isset($tag['break']) ? $tag['break'] : '';
        if ('' === $isBreak || 'true' === $isBreak) {
            $parseStr .= '<?php break; ?>';
        }
        return $parseStr;
    }

    /**
     * default标签解析
     * 详细见switch标签
     * @access public
     * @param array $tag 标签属性
     * @return string
     */
    public function tagDefault($tag)
    {
        $parseStr = '<?php default: ?>';
        return $parseStr;
    }

    /**
     * empty标签解析
     * 如果某个变量为empty 则输出内容
     * 格式:
     * {empty name="$name"}
     * {/empty}
     * @access public
     * @param array  $tag     标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagEmpty($tag, $content)
    {
        $name     = $tag['name'];
        $parseStr = '<?php if (empty(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * notempty标签解析
     * 如果某个变量不为empty 则输出内容
     * 格式:
     * {notempty name="$name"}
     * {/notempty}
     * @access public
     * @param array  $tag     标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagNotempty($tag, $content)
    {
        $name     = $tag['name'];
        $parseStr = '<?php if (!empty(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * present标签解析
     * 如果某个变量已经设置 则输出内容
     * 格式:
     * {present name="$name"}
     * {/present}
     * @access public
     * @param array  $tag     标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagPresent($tag, $content)
    {
        $name     = $tag['name'];
        $parseStr = '<?php if (isset(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * notpresent标签解析
     * 如果某个变量没有设置 则输出内容
     * 格式:
     * {notpresent name="$name"}
     * {/notpresent}
     * @access public
     * @param array  $tag     标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagNotpresent($tag, $content)
    {
        $name     = $tag['name'];
        $parseStr = '<?php if (!isset(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * defined标签解析
     * 如果已经定义该常量 则输出内容
     * 格式:
     * {defined name="$name"}
     * {/defined}
     * @access public
     * @param array  $tag     标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagDefined($tag, $content)
    {
        $name     = $tag['name'];
        $parseStr = '<?php if (defined(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * notdefined标签解析
     * 如果该常量没有被定义 则输出内容
     * 格式:
     * {notdefined name="$name"}
     * {/notdefined}
     * @access public
     * @param array  $tag     标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagNotdefined($tag, $content)
    {
        $name     = $tag['name'];
        $parseStr = '<?php if (!defined(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        return $parseStr;
    }

    /**
     * load标签解析 支持加载多个文件 现支持css、js、php文件，可扩展
     * 格式:
     * {load file="../css/style.css,../css/style2.css" /}
     * @access public
     * @param array  $tag     标签属性
     * @param string $content 标签内容
     * @return string
     */
    public function tagLoad($tag, $content)
    {
        $file     = !empty($tag['file']) ? $tag['file'] : $tag['href'];
        $basepath = !empty($tag['basepath']) ? $tag['basepath'] : '';
        $parseStr = '';
        $endStr   = '';
        // 加载条件, 允许使用函数判断
        if (isset($tag['value'])) {
            $name     = $tag['value'];
            $name     = $this->autoBuildVar($name);
            $parseStr .= '<?php if (' . $name . '): ?>';
            $endStr   .= '<?php endif; ?>';
        }

        $array = explode(',', $file);
        foreach ($array as $val) {
            $type = strtolower(substr(strrchr($val, '.'), 1));
            $val  = $basepath . $val;
            switch ($type) {
                case 'css':
                    $parseStr .= '<link rel="stylesheet" type="text/css" href="' . $val . '" />' . "\n";
                    break;
                case 'js':
                    $parseStr .= '<script type="text/javascript" src="' . $val . '"></script>' . "\n";
                    break;
                case 'php':
                    $parseStr .= '<?php include "' . $val . '"; ?>';
            }
        }
        return $parseStr . $endStr;

    }

}

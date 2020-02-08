<?php

/**
 * 检查数组的类型
 * @param array $array 数组
 * @return number 类型代码：为1索引数组，为2关联数组，为3组合数组
 */
function checkArrayType(array $array)
{
    $count = count($array);
    $in    = array_intersect_key($array, range(0, $count - 1));
    return $count == count($in) ? 1 : (empty($in) ? 2 : 3);
}
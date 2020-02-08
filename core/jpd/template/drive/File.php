<?php

namespace core\jpd\template\drive;

// 文件缓存机制
class File
{
    protected $cacheFile;

    public function write($cacheFile, $content)
    {
        // 检测模板目录
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        // 生成模板文件
        if (false === file_put_contents($cacheFile, $content)) {
            die('File.php 生成模板文件错误');
        }
    }

    public function read($cacheFile, $vars = [])
    {
        $this->cacheFile = $cacheFile;
        if (!empty($vars) && is_array($vars)) {
            extract($vars, EXTR_OVERWRITE);
        }
        include $this->cacheFile;
    }

    public function check($cacheFile, $cacheTime)
    {
        // 缓存文件不存在
        if (!file_exists($cacheFile)) {
            return false;
        }
        if (0 != $cacheTime && $_SERVER['REQUEST_TIME'] > filemtime($cacheFile) + $cacheTime) {
            return false;
        }
        return true;
    }
}

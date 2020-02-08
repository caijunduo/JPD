<?php
header('Content-type: text/html; charset=utf-8');

define('JPD_VERSION', '1.0.0');
define('EXT', '.php');
define('DS', DIRECTORY_SEPARATOR);
defined('JPD_PATH') or define('JPD_PATH', __DIR__ . DS);
define('CORE_PATH', JPD_PATH . 'jpd' . DS);
defined('TPL_PATH') or define('TPL_PATH', JPD_PATH . 'tpl' . DS);
define('CORE_LOG_PATH', CORE_PATH . 'log' . DS);
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . DS);
defined('ROOT_PATH') or define('ROOT_PATH', dirname(realpath(APP_PATH)) . DS);
defined('RUNTIME_PATH') or define('RUNTIME_PATH', ROOT_PATH . 'runtime' . DS);
defined('CACHE_PATH') or define('CACHE_PATH', RUNTIME_PATH . 'cache' . DS);
defined('LOG_PATH') or define('LOG_PATH', RUNTIME_PATH . 'log' . DS);
defined('TEMP_PATH') or define('TEMP_PATH', RUNTIME_PATH . 'temp' . DS);

defined('ERROR_TPL') or define('ERROR_TPL', TPL_PATH . 'Error.tpl');
defined('EXCEPTION_TPL') or define('EXCEPTION_TPL', TPL_PATH . 'Exception.tpl');
defined('SESSION_PATH') or define('SESSION_PATH', RUNTIME_PATH . 'session' . DS);

// 载入Loader类
require CORE_PATH . 'Loader.php';

// 注册自动加载
\core\jpd\Loader::register();

// 注册错误和异常处理机制
\core\jpd\Error::register();

// 加载系统配置
\core\jpd\Config::set(include JPD_PATH . 'convention' . EXT);

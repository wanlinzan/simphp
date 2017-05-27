<?php
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__DIR__) . '/');
defined('__PROTOCOL__') or define('__PROTOCOL__', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://');
defined('__ROOT__') or define('__ROOT__', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));
defined('__REAL_ROOT__') or define('__REAL_ROOT__', __PROTOCOL__ . $_SERVER['HTTP_HOST'] . __ROOT__);
//使用代理情况修正
if (stripos($_SERVER['REQUEST_URI'], 'http') === 0) {
    defined('__CURRENT__') or define('__CURRENT__', $_SERVER['REQUEST_URI']);
} else {
    defined('__CURRENT__') or define('__CURRENT__', __PROTOCOL__ . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
}
defined('DEBUG') or define('DEBUG', true);
defined('LOG_WRITE') or define('LOG_WRITE', true);

include __DIR__ . '/functions.php';

//初始化配置文件
file_exists(ROOT_PATH . 'config.php') and config(include ROOT_PATH . 'config.php');


//自动加载框架simphp目录下的类
spl_autoload_register(function ($name) {
    $includePaths = [
        ROOT_PATH,//网站根目录,只要符合psr-4规范的类都可以自动加载
        dirname(__DIR__) . '/'//框架目录中，只有在框架根目录不在网站根目录情况下起作用
    ];
    foreach ($includePaths as $includePath) {
        $filename = $includePath . str_replace('\\', '/', $name) . '.php';
        if (file_exists($filename)) {
            include $includePath . str_replace('\\', '/', $name) . '.php';
            break;
        }
    }
});

if (DEBUG) {
    error_reporting(E_ALL);
} else if (LOG_WRITE) {
    error_reporting(0);
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        p_log('--------------------');
        p_log(error_get_last());
        p_log($errfile . '[' . $errline . ']:' . $errstr);
    });
    set_exception_handler(function ($e) {
        p_log('--------------------');
        p_log(error_get_last());
        p_log('[' . $e->getLine() . ']:' . $e->getMessage());
    });
} else {
    error_reporting(0);
}


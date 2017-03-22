<?php
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__DIR__) . '/');
defined('__PROTOCOL__') or define('__PROTOCOL__', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://');
defined('__ROOT__') or define('__ROOT__', dirname($_SERVER['SCRIPT_NAME']));
defined('__REAL_ROOT__') or define('__REAL_ROOT__', __PROTOCOL__ . $_SERVER['HTTP_HOST'] . __ROOT__);
defined('__CURRENT__') or define('__CURRENT__', __PROTOCOL__ . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

include __DIR__ . '/functions.php';


//自动加载框架simphp目录下的类
spl_autoload_register(function ($name) {

    $includePaths = [
        ROOT_PATH,//网站根目录,只要符合psr-4规范的类都可以自动加载
        dirname(__DIR__) . '/'//框架目录中，只有在框架根目录不在网站根目录情况下起作用
    ];

    foreach ($includePaths as $includePath) {
        include $includePath . str_replace('\\', '/', $name) . '.php';
        break;
    }
});

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
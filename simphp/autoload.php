<?php
defined('ROOT_PATH') or define('ROOT_PATH', dirname(__DIR__) . '/');
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
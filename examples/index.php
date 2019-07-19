<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
function p($var)
{
    echo '<pre style="background:#eee;padding:10px;margin:10px;">';
    if (is_null($var) || is_bool($var)) {
        var_dump($var);
    } else {
        print_r($var);
    }
    echo '</pre>';
}

include './src/WebApp.php';
//include './src/Providers/Logger.php';

$webApp = new \Simphp\WebApp();

$webApp->get('/login', function (\Monolog\Logger $logger) {
    p($logger);
    return 'login';
});

$webApp->run();
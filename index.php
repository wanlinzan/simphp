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

$webApp = new \Simphp\WebApp();

//$webApp->addMiddleware(new Auth);

$webApp->get('/login', function () {
    return 'login';
});


$webApp->setDependencyInjection(Auth::class, new Auth);

class Auth
{
    public function __invoke()
    {
        if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
            return true;
        } else {
            return [
                'status' => 'not_login',
                'message' => '还没有登录呢'
            ];
        }
    }
}

// 用户相关路由
$webApp->group('/user', function (\Simphp\WebApp $webApp) {

    $webApp->get('/info/(\d+)/(\d+)', function (Auth $auth, $args) {

        var_dump($auth);
        var_dump($args);

        return [
            'status' => 'success',
            'data' => '你好，哈哈哈'
        ];
    });

});

$webApp->get('/class', function () {

});


$webApp->run();
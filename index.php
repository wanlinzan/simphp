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

class A
{

}

class B
{

}


$webApp->setDependencyInjection(A::class, new A);
$webApp->setDependencyInjection(B::class, new B);

class Auth
{
    public function __invoke(B $b, A $a)
    {
        p($a);
        P($b);
        if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
            return true;
        } else {
            return [
                'status' => 'not_login',
                'message' => '还没有登录呢'
            ];
        }
    }

    public function login(A $a, B $b)
    {
        return [
            'a' => get_class($a),
            'b' => get_class($b)
        ];

//        return 'auth login';
    }
}

$webApp->get('/class', Auth::class);
$webApp->get('/object', new Auth());
$webApp->get('/object_controller', [new Auth(), 'login']);
$webApp->get('/class_controller', [Auth::class, 'login']);


$webApp->run();
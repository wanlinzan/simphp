# SimPHP 框架
SimPHP 是一个简单而强大的PHP开发框架，他可以帮助你快速开发web应用程序。

## 安装
```base
composer require wanlinzan/simphp
```

## 使用
```php

<?php

require './vendor/autoload.php';

$app = new simphp\WebApp();
#$app = new simphp\WebApp(__DIR__.'/config.php'); // 自定义配置文件


#中间件
$app->addMiddleware(function($routeParams){
    #routeParams 路由参数
    #可根据 $routeParams 中的参数获取到路由参数等信息，根据路由判断是否需要登录之类的
    #return false;
    return true; // 停止往下执行
});

$app->get('/hello/(\w+).html', function ($args) {
    echo 'hello, ' . $args[1];
});

class HomeController {
    
    // medoo 不需要手动传入，框架通过依赖注入的方式自动注入
    public function home(\Medoo\Medoo $medoo){
        
        #$medoo->get('users',['id' => 1]);
        echo '主页';
    }
}

class UserController {
    public function __invoke() {
        echo '用户主页';
    }
}

// 控制器方法方式使用
$app->get('/home',[HomeController::class,'home']);

// 控制器方式使用
$app->get('/user/home',UserController::class);

$app->run();
```

你可以使用PHP内置的服务器快速测试:
```bash
$ php -S localhost:8000
```

访问 http://localhost:8000/hello/world.html 将会显示 "Hello, world"。

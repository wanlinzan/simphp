# SimPHP 框架
SimPHP 是一个简单而强大的PHP开发框架，他可以帮助你快速开发web应用程序。
## 安装与使用

```php
<?php

require './simphp/autoload.php';

$app = new simphp\App();

$app->get('/hello/(\w+).html', function ($args) {
    echo 'hello, ' . $args[1];
});

$app->run();
```

你可以使用PHP内置的服务器快速测试:
```bash
$ php -S localhost:8000
```

访问 http://localhost:8000/hello/world.html 将会显示 "Hello, world"。

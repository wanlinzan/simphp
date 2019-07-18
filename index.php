<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include './src/WebApp.php';

class Action
{
    public function xxx()
    {
        echo '你好xxxx';
    }

    public function __invoke()
    {
        // TODO: Implement __invoke() method.
        $this->xxx();
    }
}

function aaa()
{
    echo 'aaa';
}

$webApp = new \Simphp\WebApp();

$webApp->get('/login', function () {
    echo 'admin/login';
});
$webApp->group('/admin', function (\Simphp\WebApp $webApp) {
    $webApp->get('/login', function () {
        echo 'admin/login';
    });
    $webApp->group('/level0', function (\Simphp\WebApp $webApp) {
        $webApp->get('/login', Action::class);
        $webApp->get('/logout', function () {
            echo 'admin/login';
        });
    });
    $webApp->get('/logout', function () {
        echo 'admin/login';
    });
    $webApp->group('/level1', function (\Simphp\WebApp $webApp) {
        $webApp->get('/login', function () {
            echo 'admin/login';
        }, [new \Simphp\WebApp()]);
        $webApp->get('/logout', function () {
            echo 'admin/login';
        });
    }, [function () {
        echo 'admin/level1的中间件';
    }]);
    $webApp->group('/level2', function (\Simphp\WebApp $webApp) {
        $webApp->get('/login', function () {
            echo 'admin/login';
        });
        $webApp->get('/logout', function () {
            echo 'admin/login';
        });
    });
}, [function () {
    echo 'admin的中间件';
}]);
$webApp->get('/logout', function () {
    echo 'admin/login';
});

$webApp->get('/erson', function () {
    echo 'admin/login';
}, [function () {
    echo 'ersonn的中间件';
}]);


$webApp->addMiddleware(function () {
    echo '全局中间件';
});

//print_r($webApp);
$webApp->run();
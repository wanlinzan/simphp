<?php
session_start();
include './simphp/autoload.php';
$app = new \simphp\App();

$app->setObject('mysql', new \simphp\Mysql([
    'hostname' => 'localhost',
    'database' => 'zytb',
    'username' => 'root',
    'password' => 'tiantian',
]));


$app->get('/schoolList', function () {

    $result = $this->mysql->select("select area,school from school");

    $return = [];
    foreach ($result as $v) {
        $return[$v['area']][] = $v['school'];
    }
    $this->ajax($return);
});

$app->run();
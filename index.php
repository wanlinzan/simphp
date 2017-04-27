<?php
session_start();
include './simphp/autoload.php';
$app = new \simphp\App();


$app->get('/', function () {
    $code = new \simphp\VerifyCode();
    $code->show();
});

$app->run();
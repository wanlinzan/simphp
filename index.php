<?php
session_start();
include './simphp/autoload.php';
$app = new \simphp\App();

$app->get('/', function () {
    echo phpversion();
    p_const();
});

$app->run();
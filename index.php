<?php
session_start();
include './simphp/autoload.php';
$app = new \simphp\App();

$app->get('/', function () {

    p_const();

});

$app->run();
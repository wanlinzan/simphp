<?php
session_start();
include './simphp/autoload.php';
$app = new \simphp\App();

$app->get('/test', function () {

    p_const();

});

$app->run();
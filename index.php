<?php
session_start();
include './simphp/autoload.php';
$app = new \simphp\App();


$app->get('/', function ($args) {



    $image = \org\Image::createFromFilename('https://www.baidu.com/img/bd_logo1.png');

    p($image->crop(0,0,20,20));

});

$app->run();
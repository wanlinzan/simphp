<?php
session_start();
include './simphp/autoload.php';
$app = new \simphp\App();


$app->get('/', function ($args) {


//    $image = \org\Image::createFromFilename('https://www.baidu.com/img/bd_logo1.png');
    $image = \org\Image::createFromFilename('38.jpg');

//    p($image);

    $image->thumb(400,400)->save(ROOT_PATH.'AAA.PNG');

});

$app->run();
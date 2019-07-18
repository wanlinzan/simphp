<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
include './vendor/autoload.php';

$app = new \Wanlinzan\App();

echo '<pre>';
print_r($app);
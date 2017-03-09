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

$app->get('/', function () {

    $data = [
        'unionid' => 'oiTCLt7sZwBVY3HQ3IU0gdys1e3Q',
        'names' => [
            '2017年广州中考模拟志愿填报',
            '21天口语计划'
        ]
    ];
    $result = simphp\Http::post('http://mp.weixin.gzpeiyou.com/index/api/batchUserTag', json_encode($data,JSON_UNESCAPED_UNICODE));

    p($result);
});

$app->run();
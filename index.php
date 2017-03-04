<?php
define('ROOT_PATH', '../');
session_start();
include './simphp/autoload.php';

$app = new \simphp\App();
$app->setObject('mysql', new \simphp\Mysql([
    'hostname' => 'localhost',
    'database' => 'simbbs',
    'username' => 'root',
    'password' => ''
]));

$app->setObject('view', new \simphp\View([
    'dir' => './view/',
    'cache_dir' => './cache/'
]));

//检查是否登录的中间件
$app->addMiddleware(function ($route_key) {
    $login = ['/register.html', '/user.html'];
    //检查登录
    if (in_array($route_key, $login)) {
        $login_info = session('login_info');
        if (empty($login_info)) {
            $this->redirect('./login.html');
        }
        $user_id = $this->mysql->find("select id from simbbs_user where login_info='$login_info'");
        if (empty($user_id)) {
            $this->redirect('./login.html');
        }
    }
});


$app->get('/', function () {

    echo phpversion();

    $this->view->assign('title', '网站首页');
    $this->view->display('index');
});


//用户个人中心
$app->get('/user.html', function () {

    $user_id = session('user_id');

    $user = $this->mysql->find("select * from simbbs_user where id='{$user_id}'");
    $sex = ['transgender', 'mars', 'venus'];
    $user['sex'] = $sex[$user['sex']];
    $user['register_time'] = human_time($user['register_time']);
    $user['login_time'] = human_time($user['login_time']);

    $status = ['正常', '锁定'];
    $user['status'] = $status[$user['status']];
    $user['photo'] = './user_icon/' . $user['photo'];
    if (empty($user)) {
        $this->error('用户不存在');
    }
    $this->view->assign('title', '用户个人中心');
    $this->view->assign('user', $user);
    $this->view->display('user');
});

//修改资料
$app->get('/user-modify.html', function () {

    $user_id = session('user_id');
    $user = $this->mysql->find("select * from simbbs_user where id='{$user_id}'");
    $user['photo'] = './user_icon/' . $user['photo'];

    $this->view->assign('title', '修改资料');
    $this->view->assign('user', $user);
    $this->view->display('user-modify');
});


//修改资料处理
$app->post('/user-modify.html', function () {
    $user_id = session('user_id');
    $data = [
        'nickname' => input('nickname'),
        'email' => input('email'),
        'qq' => input('qq'),
        'phone' => input('phone'),
        'sex' => input('sex')
    ];

    if (empty($data['nickname'])) {
        $this->error('昵称不能为空');
    }

    //可以为空的
    if (!preg_match('#^(\d{5,}|)$#', $data['qq'])) {
        $this->error('QQ格式不对');
    }

    //可以为空的
    if (!preg_match('#^(1[2345678]\d{9}|)$#', $data['phone'])) {
        $this->error('手机号格式不对');
    }

    if (empty($data['email'])) {
        $this->error('邮箱不能为空');
    }

    if (!preg_match('#^\w+(\.\w+)*?@\w+?(\.\w+)+?$#', $data['email'])) {
        $this->error('邮箱格式不对');
    }

    if (!in_array($data['sex'], [0, 1, 2])) {
        unset($data['sex']);
    }

    if ($this->mysql->find("select id from simbbs_user where email='{$data['email']}' and id != '{$user_id}'")) {
        $this->error('邮箱已经绑定其他账号');
    }

    //手机号存在，并且在别的账号中找到了，也就是被占用了。
    if ($data['phone'] && $this->mysql->find("select id from simbbs_user where phone='{$data['phone']}' and id != '{$user_id}'")) {
        $this->error('手机号已经绑定其他账号');
    }

    $this->mysql->update('simbbs_user', $data, "id='{$user_id}'");

    $this->success('修改资料成功', './user.html');
});


//上传头像处理
$app->post('/user-photo.html', function () {

    $user_id = session('user_id');
    try {
        $up = new \simphp\Upload('user_icon');
        $file = $up->upload($_FILES['file']);
        //压缩图片
        $img = new \simphp\Image('./user_icon');
        $new_name = $img->thumb($file['new_name'], 300, 300);
        //删除原图
        unlink('./user_icon/' . $file['new_name']);

        $user = $this->mysql->find("select id,photo from simbbs_user where id='{$user_id}'");
        $old_photo = './user_icon/' . $user['photo'];
        $this->mysql->update('simbbs_user', ['photo' => $new_name], "id='{$user_id}'");
        file_exists($old_photo) and unlink($old_photo);

        $this->ajax([
            'code' => 1,
            'msg' => '上传头像成功',
            'src' => './user_icon/' . $new_name
        ]);

    } catch (\Exception $e) {
        $this->error($e->getMessage());
    }

});


//验证码
$app->get('/verificationCode.html', function () {
    $code = new \simphp\VerificationCode();
    $code->height(46)->fontSize(23)->show();
});


//用户注册
$app->get('/register.html', function () {

    $this->view->assign('title', '用户注册');
    $this->view->display('register');

});

//用户注册处理
$app->post('/register.html', function () {

    if (strtoupper(input('verify')) != session('code')) {
        $this->error('验证码错误');
    }
    $username = input('username');
    $password = input('password');
    $password1 = input('password1');
    $email = input('email');
    if (empty($username)) {
        $this->error('用户名不能为空!');
    }

    if (empty($email)) {
        $this->error('邮箱不能为空');
    }

    if (!preg_match('#^\w+(\.\w+)*?@\w+?(\.\w+)+?$#', $email)) {
        $this->error('邮箱格式不对');
    }

    if (empty($password)) {
        $this->error('密码不能为空');
    }

    if (strlen($password) < 6) {
        $this->error('密码长度不能小于6位');
    }

    if (empty($password1)) {
        $this->error('确认密码不能为空');
    }

    if ($password != $password1) {
        $this->error('两次输入的密码不一致');
    }

    $data = [
        'username' => $username,
        'password' => md5($password),
        'nickname' => $username,
        'email' => $email,
        'register_time' => time(),
        'autograph' => '这个人太懒了，什么都没有留下！'
    ];
    try {
        //生成头像
        $identicon = new \simphp\Identicon();
        $data['photo'] = unique_id() . '.png';
        $resource = $identicon->getImageBinaryData($username);
        if (!file_exists('./user_icon/')) {
            if (false === mkdir('./user_icon/', 0700, true)) {
                throw new \Exception('目录创建失败');
            }
        }
        file_put_contents('./user_icon/' . $data['photo'], $resource);

        if (!empty($this->mysql->find("select id from simbbs_user where username='{$username}'"))) {
            throw new \Exception('用户名已经被他人注册!');
        }
        if (!empty($this->mysql->find("select id from simbbs_user where email='{$email}'"))) {
            throw new \Exception('邮箱已经被他人绑定');
        }
        $this->mysql->insert('simbbs_user', $data);
        //销毁验证码
        session('code', rand());
        $this->success('注册成功', './login.html');

    } catch (\Exception $e) {
        $this->error($e->getMessage());
    }
});

//用户登录
$app->get('/login.html', function () {

    $this->view->assign('title', '用户登录');
    $this->view->display('login');

});

//用户登录处理
$app->post('/login.html', function () {
    $username = input_escape('username');
    $password = input('password');

    try {
        if (empty($username)) {
            throw new \Exception('请输入用户名');
        }
        if (empty($password)) {
            throw new \Exception('请输入密码');
        }

        $user = $this->mysql->find("select id,password from simbbs_user where username='$username'");

        if (empty($user)) {
            throw new \Exception('用户不存在');
        }

        if ($user['password'] != md5($password)) {
            throw new \Exception('密码错误!');
        }

        $data = [
            'login_ip' => client_ip(),
            'login_time' => time(),
        ];
        $data['login_info'] = md5($user['id'] . $data['login_ip'] . $data['login_time']);
        $this->mysql->update('simbbs_user', $data, "id='{$user['id']}'");

        session('login_info', $data['login_info']);
        session('user_id', $user['id']);

        $this->success('登录成功', './user.html');
    } catch (\Exception $e) {
        $this->error($e->getMessage());
    }

});

//用户退出
$app->get('/logout.html', function () {
    session('user_id', null);
    session('login_info', null);
    $this->success('退出成功', './');
});

//找回密码
$app->get('/password-forget.html', function () {
    $this->view->assign('title', '找回密码');
    $this->view->display('password-forget');
});


//找回密码处理
$app->post('/password-forget.html', function () {

    $verify = input('verify');
    $email = input_escape('email');
    if (strtoupper($verify) != session('code')) {
        $this->error('验证码错误');
    }

    if (empty($email)) {
        $this->error('邮箱不能为空');
    }

    if (!preg_match('#^\w+(\.\w+)*?@\w+?(\.\w+)+?$#', $email)) {
        $this->error('邮箱格式不对!');
    }

    $user = $this->mysql->find("select id,email,login_info from simbbs_user where email='{$email}'");
    if (empty($user)) {
        $this->error('邮箱不存在');
    }

    $verify = md5($user['id'] . $user['login_info']);

    try {
        $result = send_email($user['email'], '找回密码', "<a href='http://" . $_SERVER['HTTP_HOST'] . "/reset-password.html?email={$user['email']}&verify={$verify}'>点击找回密码</a> ");
        if ($result) {
            session('code', rand());
            $this->success('邮件发送成功,请进入邮件进行操作');
        } else {
            $this->error('发送邮件失败');
        }

    } catch (\Exception $e) {
        $this->error($e->getMessage());
    }
});

//密码重置
$app->get('/password-reset.html', function () {

    $email = input_escape('email');
    $verify = input('verify');
    $user = $this->mysql->find("select id,login_info,username,email from simbbs_user where email='{$email}'");
    if (empty($user) || $verify != md5($user['id'] . $user['login_info'])) {
        $this->error('链接已经失效了!');
    }

    $this->view->assign('title', '重置密码');
    $this->view->assign('user', $user);
    $this->view->display('password-reset');
});

//密码重置处理
$app->post('/password-reset.html', function () {

    $email = input_escape('email');
    $verify = input('verify');
    $user = $this->mysql->find("select id,login_info,username,email from simbbs_user where email='{$email}'");
    if (empty($user) || $verify != md5($user['id'] . $user['login_info'])) {
        $this->error('链接已经失效了!');
    }
    $password = input('password');
    $password1 = input('password1');
    if (empty($password)) {
        $this->error('密码不能为空');
    }

    if (strlen($password) < 6) {
        $this->error('密码不能小于6位');
    }

    if ($password != $password1) {
        $this->error('两次输入的密码不一致!');
    }

    $this->mysql->update('simbbs_user', [
        'password' => md5($password),
        'login_info' => md5(client_ip() . time())
    ], "id='{$user['id']}'");

    $this->success('设置新密码成功!', './login.html');
});


//修改密码
$app->get('/password-modify.html', function () {
    $this->view->assign('title', '修改密码');
    $this->view->display('password-modify');

});


//修改密码处理
$app->post('/password-modify.html', function () {

    $user_id = session('user_id');
    $user = $this->mysql->find("select * from simbbs_user where id='{$user_id}'");
    $old_password = input('old_password');
    $password = input('password');
    $password1 = input('password1');
    if (empty($old_password)) {
        $this->error('请填写原密码');
    }
    if (empty($password)) {
        $this->error('请输入新密码');
    }

    if (strlen($password) < 6) {
        $this->error('密码长度不能小于6位');
    }

    if ($password != $password1) {
        $this->error('两次输入的密码不一致');
    }
    if (md5($old_password) != $user['password']) {
        $this->error('原密码不对');
    }

    $this->mysql->update('simbbs_user', [
        'password' => md5($password)
    ], "id='{$user_id}'");

    $this->success('修改密码成功', './user.html');
});

//上传图片
$app->post('/upload-image.html', function () {

    try {
        //所需要上传的目录
        $up = new \simphp\Upload('./image');

        $file = $up->upload($_FILES['file']);

        $image = new \simphp\Image('./image');
        $thumb_name = $image->thumb($file['new_name'], 200, 200);
        $this->ajax([
            'code' => 1,
            'msg' => '上传成功',
            'url' => './image/' . $file['new_name'],
            'thumb_url' => './image/th_' . $thumb_name
        ]);

    } catch (\Exception $e) {
        $this->error($e->getMessage());
    }
});


$app->run();



<?php
/**
 * @param $data
 * 打印信息到控制台
 */
function console_log($data)
{
    echo '<script>';
    echo 'console.log(' . json_encode($data) . ')';
    echo '</script>';
}

/**
 * @param $str
 * 调试打印信息到文件中
 */
function p_log($str)
{
    $str = print_r($str, true);
    $filename = dirname(__DIR__) . '/' . date("Ymd") . '.log';
    $fp = fopen($filename, 'a+');
    fwrite($fp, $str . "\r\n");
    fclose($fp);
}

function send_email($to, $title, $content)
{
    $mail = new \simphp\Mail();
    $mail->isSMTP();
    $mail->SMTPAuth = true;
    $mail->Host = 'smtp.qq.com';
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';
    $mail->FromName = 'fsdfsdf';
    $mail->Username = '799034851';
    $mail->Password = 'ikkoiafxeecnbefg';//'tbyduuggtxrybedj';
    //发件人地址
    $mail->From = '799034851@qq.com';
    $mail->isHTML(true);
    //设置收件人邮箱地址
    $mail->addAddress($to);
    //邮件的标题
    $mail->Subject = $title;
    //邮件正文
    $mail->Body = $content;
    return $mail->send();
}

/**
 * @return string
 * 生成唯一id,理论唯一
 */
function unique_id()
{
    return str_replace('.', '', uniqid('', true));
}

/**
 * @param null $name
 * @param null $value
 * @return array|mixed
 * 全局配置函数
 */
function config($name = null, $value = null)
{
    static $config = array();
//合并配置文件
    if (is_array($name)) {
        $config = array_merge($config, $name);
    } //获取所有的配置
    else if (is_null($name)) {
        return $config;
    }//获取配置
    else if (is_null($value)) {
        return $config[$name];
    }//修改配置
    else {
        $config[$name] = $value;
    }
}

/**
 * 获取客户端ip
 **/
function client_ip()
{
    static $ip = NULL;
    if ($ip !== NULL) return $ip;
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos) unset($arr[$pos]);
        $ip = trim($arr[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
// IP地址合法验证
    $ip = sprintf("%u", ip2long($ip)) ? $ip : '0.0.0.0';
    return $ip;
}

//接收外部数据
function input($key, $default = '')
{
    if (isset($_REQUEST[$key])) {
        return _trim($_REQUEST[$key]);
    } else {
        return $default;
    }
}

function _trim($var, $string = "\t\n\r\0\x0B")
{
    if (is_string($var)) {
        return trim($var, $string);
    }
    foreach ($var as $k => $v) {
        $var[trim($k, $string)] = _trim($v, $string);
    }
    return $var;
}

//接收外部数据 并转义
function input_escape($key, $default = '')
{
    if (isset($_REQUEST[$key])) {
        return _addslashes($_REQUEST[$key]);
    } else {
        return $default;
    }
}

function _addslashes($var)
{
    if (!is_array($var)) {
        return addslashes($var);
    }
    foreach ($var as $k => $v) {
        $var[addslashes($k)] = _addslashes($v);
    }
    return $var;
}

function p($var)
{
    echo '<pre style="background:#eee;padding:10px;margin:10px;">';
    if (is_null($var) || is_bool($var)) {
        var_dump($var);
    } else {
        print_r($var);
    }
    echo '</pre>';
}

function p_const()
{
    p(get_defined_constants(true)['user']);
}

/**
 * @param int $size
 * @param int $decimals
 * @return string
 * 转化为人能看懂的大小单位
 */
function human_size($size, $decimals = 2)
{
    switch (true) {
        case $size >= pow(1024, 3):
            return round($size / pow(1024, 3), $decimals) . " GB";
        case $size >= pow(1024, 2):
            return round($size / pow(1024, 2), $decimals) . " MB";
        case $size >= pow(1024, 1):
            return round($size / pow(1024, 1), $decimals) . " KB";
        default:
            return $size . 'B';
    }
}

function human_time($time)
{
    $use_time = time() - $time;
    if ($use_time < 60) {
        return $use_time . '秒前';
    } else if ($use_time < 3600) {
        return floor($use_time / 60) . '分钟前';
    } else if ($use_time < 86400) {
        return floor($use_time / 3600) . '小时前';
    } else if ($use_time < 5184000) {//60天
        return floor($use_time / 86400) . '天前';
    } else {
        return date('Y-m-d H:i:s', $time);
    }
}

/**
 * @param bool $name
 * @param bool $value
 * @return array|string
 * session操作函数
 */
function session($name = false, $value = false)
{
    if (is_null($name)) {
        $_SESSION = array();
        //清除内存中的
        session_unset();
        session_destroy();
    } else if (is_null($value)) {
        //删除session值
        unset($_SESSION[$name]);
    } else if (false === $name) {
        //设置session
        return $_SESSION;
    } else if (false === $value) {
        //获取session值
        return isset($_SESSION[$name]) ? $_SESSION[$name] : '';
    } else {
        //获取所有session
        $_SESSION[$name] = $value;
    }
}

/**
 * @param bool $name
 * @param bool $value
 * @param int $lifetime
 * @return null
 * cookie 操作函数
 */
function cookie($name = false, $value = false, $lifetime = 3600)
{
//删除所有的cookie
    if (is_null($name)) {
        foreach ($_COOKIE as $k => $v) {
            unset($_COOKIE[$k]);
            setcookie($k, '', time() - 3600);
        }
    } //删除指定cookie
    else if (is_null($value)) {
        unset($_COOKIE[$name]);
        setcookie($name, '', time() - 3600);
    } //获取所有cookie
    else if (false === $name) {
        return $_COOKIE;
    } //获取cookie
    else if (false === $value) {
        return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
    }//设置cookie
    else {
        $_COOKIE[$name] = $value;
        setcookie($name, $value, time() + $lifetime);
    }
}
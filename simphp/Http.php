<?php
namespace simphp;
class Http
{

    //需要结果的get请求
    public static function get($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        if (stripos($content_type, 'application/json') !== false) {
            $result = preg_replace('/[\x00-\x1F\x80-\x9F]/u', '', trim($result));
            return json_decode($result, true);
        } else if (stripos($content_type, 'text/xml') !== false) {
            return simplexml_load_string($content_type);
        } else {
            return $result;
        }
    }


    //需要结果的post请求
    public static function post($url, $data = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);//自动设置referer
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);//自动根据referer跳转
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if (phpversion() > '5.5') {
            if (is_array($data) == true) {
                // Check each post field
                foreach ($data as $key => $value) {
                    // Convert values for keys starting with '@' prefix
                    if (strpos($value, '@') === 0) {
                        // Get the file name
                        $filename = ltrim($value, '@');
                        // Convert the value to the new class
                        $data[$key] = new \CURLFile($filename);
                    }
                }
            }
            curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);// PHP 5.6.0 后必须开启
        }

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);

        if (stripos($content_type, 'application/json') !== false) {
            $result = preg_replace('/[\x00-\x1F\x80-\x9F]/u', '', trim($result));
            return json_decode($result, true);
        } else if (stripos($content_type, 'text/xml') !== false) {
            return simplexml_load_string($content_type);
        } else {
            return $result;
        }
    }


    //无需等待结果的get请求
    public static function s_get($url)
    {
        $url_data = parse_url($url);
        $new_url = isset($url_data['path']) ? $url_data['path'] : '/';
        if (isset($url_data['query'])) {
            $new_url .= '?' . $url_data['query'];
        }
        $fp = fsockopen($url_data['host'], isset($url_data['port']) ? $url_data['port'] : 80, $error, $errstr, 1);
        $http = "GET {$new_url} HTTP/1.1\r\nHost: {$url_data['host']}\r\n\r\n";
        fwrite($fp, $http);
        fclose($fp);
    }

    //无需等待结果的post请求
    public static function s_post($url, $data = [])
    {
        $url_data = parse_url($url);
        $new_url = isset($url_data['path']) ? $url_data['path'] : '/';
        if (isset($url_data['query'])) {
            $new_url .= '?' . $url_data['query'];
        }
        $fp = fsockopen($url_data['host'], isset($url_data['port']) ? $url_data['port'] : 80, $error, $errstr, 1);
        $data = http_build_query($data);
        $http = "POST {$new_url} HTTP/1.1\r\nHost: {$url_data['host']}\r\nContent-type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($data) . "\r\nConnection:close\r\n\r\n{$data}\r\n\r\n";
        fwrite($fp, $http);
        fclose($fp);
    }

}
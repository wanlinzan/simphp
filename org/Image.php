<?php
namespace org;

class Image
{
    private $_resource = null;

    private $_width = 0;

    private $_height = 0;


    //构造函数
    public function __construct($resource = null)
    {
        if (!is_resource($resource)) {
            throw new \Exception('不是一个有效的图片资源');
        }
        $this->_resource = $resource;
        $this->_width = imagesx($resource);
        $this->_height = imagesy($resource);
    }

    //通过字符串创建图像资源
    public static function createFromString($string = null)
    {
        if (is_null($string) || !is_string($string)) {
            throw new \Exception('请传入合法的字符串作为参数');
        }
        $resource = imagecreatefromstring($string);
        if (false === $resource) {
            throw new \Exception('创建图片失败');
        }
        return new self($resource);
    }

    //通过文件名创建图片,支持原创图片
    public static function createFromFilename($filename = null)
    {
        if (is_null($filename)) {
            throw new \Exception('请传入合法的路径作为参数');
        }
        if (stripos($filename, 'http') !== 0 && !is_file($filename)) {
            throw new \Exception('文件不存在');
        }
        $type = exif_imagetype($filename);
        if (false === $type) {
            throw new \Exception('非法图片');
        }
        $func = 'imagecreatefrom' . image_type_to_extension($type, false);
        $resource = $func($filename);
        if (false === $resource) {
            throw new \Exception('创建图片失败');
        }
        return new self($resource);
    }

    //压缩图片
    public function thumb($width = 1, $height = 1)
    {

    }

    public function crop($x, $y, $width, $height)
    {
        $resource = imagecreatetruecolor($width, $height);
        imagecopyresampled($resource, $this->_resource, 0, 0, $x, $y, $width, $height, $width, $height);
        return new self($resource);
    }

    public function __destruct()
    {
        imagedestroy($this->_resource);
    }
}
<?php
namespace org;

class Image
{
    private $_resource = null;

    private $_width = 0;

    private $_height = 0;

    public function __construct($resource = null)
    {
        if (!is_resource($resource)) {
            throw new \Exception('不是一个有效的图片资源');
        }
        $this->_resource = $resource;
        $this->_width = imagesx($resource);
        $this->_height = imagesy($resource);
    }

    /**
     * 通过字符串创建图像资源
     * @param $string
     * @return Image
     * @throws \Exception
     */
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

    /**
     * 通过文件名创建图片,支持远程图片
     * @param null $filename
     * @return Image
     * @throws \Exception
     */
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
    public function thumb($width = null, $height = null)
    {
        if (is_null($width) && is_null($height)) {
            return $this;
        }

        if (is_null($width)) {
            if ($height < 1) {
                throw new \Exception('高度不能小于1');
            }
            $proportion = $height / $this->_height;
            $width = max(1, intval($this->_width * $proportion));
        }

        if (is_null($height)) {
            if ($width < 1) {
                throw new \Exception('宽度不能小于1');
            }
            $proportion = $width / $this->_width;
            $height = max(1, intval($this->_height * $proportion));
        }

        $resource = imagecreatetruecolor($width, $height);
        imagecopyresized($resource, $this->_resource, 0, 0, 0, 0, $width, $height, $this->_width, $this->_height);
        return new self($resource);
    }

    /**
     * 裁剪
     * @param $x int x坐标
     * @param $y int y坐标
     * @param $width int 宽度
     * @param $height int 高度
     * @return Image
     * @throws \Exception
     */
    public function crop($x, $y, $width, $height)
    {
        if ($x < 0 || $y < 0 || $x + $width > $this->_width || $y + $height > $this->_height) {
            throw new \Exception('裁剪超过原图大小');
        }
        $resource = imagecreatetruecolor($width, $height);
        imagecopyresampled($resource, $this->_resource, 0, 0, $x, $y, $width, $height, $width, $height);
        return new self($resource);
    }

    public function show()
    {
        header('Content-type:image/png');
        imagepng($this->_resource);
    }

    public function save($filename){
        imagepng($this->_resource,$filename);
    }

    public function __destruct()
    {
        imagedestroy($this->_resource);
    }
}
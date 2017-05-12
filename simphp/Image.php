<?php
namespace simphp;

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

    public function width()
    {
        return $this->_width;
    }

    public function height()
    {
        return $this->_height;
    }

    public function resource()
    {
        return $this->_resource;
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

    /**
     * 压缩图片
     * @param null $width int 宽度
     * @param null $height int 高度
     * @return $this|Image
     * @throws \Exception
     */
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

    /**
     * 合并图片  用于水印图
     * @param Image $image
     * @param int $x x坐标
     * @param int $y y坐标
     * @param int $alpha 透明度
     * @return $this
     * @throws \Exception
     */
    public function merge(Image $image, $x = 0, $y = 0, $alpha = 50)
    {
        if (!($image instanceof self)) {
            throw new \Exception('不是一个合法的对象');
        }

        if ($x < 0 || $y < 0 || $x > $this->_width || $y > $this->_height) {
            return $this;
        }
        //防止透明颜色丢失
        $src = imagecreatetruecolor($this->_width, $this->_height);
        $color = imagecolorallocate($src, 255, 255, 255);
        imagecolortransparent($src, $color);
        imagefill($src, 0, 0, $color);
        imagecopy($src, $this->_resource, 0, 0, 0, 0, $this->_width, $this->_height);
        imagecopy($src, $image->resource(), $x, $y, 0, 0, $image->width(), $image->height());
        imagecopymerge($this->_resource, $src, 0, 0, 0, 0, $this->_width, $this->_height, $alpha);
        imagedestroy($src);
        return $this;
    }


    /**
     * 写文本
     * @param $text string 文本
     * @param $font string 字体文件
     * @param int $x 坐标
     * @param int $y 坐标
     * @param int $size 字体大小
     * @param string|array $color 颜色
     * @param int $angle 旋转角度
     * @return $this
     * @throws \Exception
     */
    public function text($text, $font, $x = 0, $y = 0, $size = 16, $color = '#00000000', $angle = 0)
    {
        if (!is_file($font)) {
            throw new \Exception('字符文件不存在' . $font);
        }
        //计算初始位置
        $info = imagettfbbox($size, $angle, $font, $text);
        $x += abs(min($info[0], $info[2], $info[4], $info[6])) + 2;
        $y += abs(min($info[1], $info[3], $info[5], $info[7]));

        //计算颜色
        if (is_string($color) && 0 === strpos($color, '#')) {
            $color = str_split(substr($color, 1), 2);
            $color = array_map('hexdec', $color);
            if (empty($color[3]) || $color[3] > 127) {
                $color[3] = 0;
            }
        } else if (!is_array($color)) {
            throw new \Exception('错误的颜色值');
        }
        $col = imagecolorallocatealpha($this->_resource, $color[0], $color[1], $color[2], $color[3]);
        imagettftext($this->_resource, $size, $angle, $x, $y, $col, $font, $text);
        return $this;
    }


    /**
     * 显示图片
     * @param string $type
     * @param int $quality
     */
    public function show($type = 'png', $quality = 80)
    {
        header('Content-type:image/' . $type);
        $func = 'image' . $type;
        if ($type == 'png') {
            imagesavealpha($this->_resource, true);
            imagepng($this->_resource, null, $quality / 10);
        } else {

            $func($this->_resource, null, $quality);
        }
    }

    /**
     * 保存图片
     * @param null $filename
     * @param $type
     * @param int $quality
     */
    public function save($filename = null, $type = 'png', $quality = 80)
    {
        if ($type == 'png') {
            imagesavealpha($this->_resource, true);
            imagepng($this->_resource, $filename, $quality / 10);
        } else {
            $func = 'image' . $type;
            $func($this->_resource, $filename, $quality);
        }
    }

    public function __destruct()
    {
        imagedestroy($this->_resource);
    }
}
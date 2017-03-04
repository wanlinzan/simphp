<?php
namespace simphp;
/**
 * Class Code
 * @package System
 * 验证码类
 */
class VerificationCode
{
    private $width = 80;
    private $height = 30;
    private $background = array(255, 255, 255);
    private $len = 4;
    private $fontSize = 20;
    private $fontFile = null;
    //画布
    private $canvas = null;
    //字符串
    private $code = null;

    private $_config = [
        'fontFile' => ''
    ];

    public function __construct($config = [])
    {
        if ($this->_config['fontFile'] == '') {
            $this->_config['fontFile'] = __DIR__ . '/CodeFont.ttf';
        }
        if (!file_exists($this->_config['fontFile'])) {
            throw new \Exception('字体文件不存在');
        }
    }

    public function width($width)
    {
        $this->width = $width;
        return $this;
    }

    public function height($height)
    {
        if ($this->height < 22) {
            throw new \Exception('高度不能小于22px');
        }
        $this->height = $height;
        return $this;
    }

    public function background($background)
    {
        $this->background = $background;
        return $this;
    }

    public function len($len)
    {
        $this->len = $len;
        return $this;
    }

    public function fontSize($fontSize)
    {
        $this->fontSize = $fontSize;
        return $this;
    }

    //创建画布
    private function createCanvas()
    {
        $this->canvas = imagecreatetruecolor($this->width, $this->height);
        $color = imagecolorallocate($this->canvas,
            $this->background[0], $this->background[1], $this->background[2]);
        imagefill($this->canvas, 0, 0, $color);
        return $this;
    }

    //画线
    private function drawLine()
    {
        if (is_null($this->canvas)) {
            throw new \Exception('请先创建画布');
        }
        //画线
        $color = imagecolorallocate($this->canvas, 220, 220, 220);
        for ($i = 1, $l = $this->height / 5; $i < $l; $i++) {
            $step = $i * 5;
            imageline($this->canvas, 0, $step, $this->width, $step, $color);
        }
        for ($i = 1, $l = $this->width / 10; $i < $l; $i++) {
            $step = $i * 10;
            imageline($this->canvas, $step, 0, $step, $this->height, $color);
        }
        return $this;
    }

    //画点
    private function drawPixel()
    {
        $color = imagecolorallocate($this->canvas, mt_rand(50, 155), mt_rand(50, 155), mt_rand(50, 155));
        for ($i = 0; $i < 50; $i++) {
            imagesetpixel($this->canvas, mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }
        return $this;
    }

    //画随机线
    private function drawRandomLine()
    {
        $color = imagecolorallocate($this->canvas, mt_rand(50, 155), mt_rand(50, 155), mt_rand(50, 155));
        for ($i = 0; $i < 10; $i++) {
            imageline($this->canvas, mt_rand(0, $this->width),
                mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }
        return $this;
    }

    //画圆弧
    private function drawArc()
    {
        $color = imagecolorallocate($this->canvas, mt_rand(50, 155), mt_rand(50, 155), mt_rand(50, 155));
        for ($i = 0; $i < 5; $i++) {
            imagearc($this->canvas,
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                mt_rand(0, $this->width),
                mt_rand(0, $this->height),
                mt_rand(0, 160),
                mt_rand(0, 200),
                $color);
        }
        return $this;
    }

    //生成验证码
    private function generateCode()
    {
        //生成字符串
        $codeStr = '23456789abcdefghjkmnpqrstuvwsyz';
        $codeLen = strlen($codeStr);
        $code = '';
        for ($i = 0; $i < $this->len; $i++) {
            $code .= $codeStr[mt_rand(0, $codeLen - 1)];
        }
        $this->code = $code;
        $_SESSION['code'] = strtoupper($code);
        return $this;
    }

    //写入字符串
    private function writeCode()
    {
        if (is_null($this->code)) {
            throw new \Exception('请先生成字符串');
        }
        $x = ($this->width - 10) / $this->len;
        for ($i = 0; $i < $this->len; $i++) {
            $color = imagecolorallocate($this->canvas, mt_rand(50, 155), mt_rand(50, 155), mt_rand(50, 155));
            imagettftext(
                $this->canvas,
                $this->fontSize,
                mt_rand(-30, 30),
//            0,
                $x * $i + mt_rand(6, 10),
//                mt_rand($this->height / 1.3, $this->height - 5),
                mt_rand(20, $this->height - ($this->fontSize / 10.0)),
                $color,  $this->_config['fontFile'], $this->code[$i]
            );
        }
        return $this;
    }

    //显示验证码
    public function show()
    {
        $this->createCanvas()->drawLine()->generateCode()->writeCode()
            ->drawRandomLine()->drawPixel()->drawArc();
        imagesetthickness($this->canvas, 1);//// 设置画线宽度
        header('Content-type:image/png');
        imagepng($this->canvas);
        imagedestroy($this->canvas);
    }

    public function code()
    {
        return $this->code;
    }
}
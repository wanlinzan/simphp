<?php
namespace simphp;
/**
 * 验证码类
 * Class VerifyCode
 * @package simphp
 */
class VerifyCode
{
    //画布
    private $canvas = null;

    //字符串
    private $code = null;

    //配置信息
    private $_config = [
        'fontFile' => '',
        'fontSize' => 20,
        'width' => 80,
        'height' => 30,
        'background' => [255, 255, 255],
        'len' => 4
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
        $this->_config['width'] = $width;
        return $this;
    }

    public function height($height)
    {
        if ($this->_config['height'] < 22) {
            throw new \Exception('高度不能小于22px');
        }
        $this->_config['height'] = $height;
        return $this;
    }

    public function background($background)
    {
        $this->_config['background'] = $background;
        return $this;
    }

    public function len($len)
    {
        $this->_config['len'] = $len;
        return $this;
    }

    public function fontSize($fontSize)
    {
        $this->_config['fontSize'] = $fontSize;
        return $this;
    }

    //创建画布
    private function createCanvas()
    {
        $this->canvas = imagecreatetruecolor($this->_config['width'], $this->_config['height']);
        $color = imagecolorallocate($this->canvas,
            $this->_config['background'][0], $this->_config['background'][1], $this->_config['background'][2]);
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
        for ($i = 1, $l = $this->_config['height'] / 5; $i < $l; $i++) {
            $step = $i * 5;
            imageline($this->canvas, 0, $step, $this->_config['width'], $step, $color);
        }
        for ($i = 1, $l = $this->_config['width'] / 10; $i < $l; $i++) {
            $step = $i * 10;
            imageline($this->canvas, $step, 0, $step, $this->_config['height'], $color);
        }
        return $this;
    }

    //画点
    private function drawPixel()
    {
        $color = imagecolorallocate($this->canvas, mt_rand(50, 155), mt_rand(50, 155), mt_rand(50, 155));
        for ($i = 0; $i < 50; $i++) {
            imagesetpixel($this->canvas, mt_rand(0, $this->_config['width']), mt_rand(0, $this->_config['height']), $color);
        }
        return $this;
    }

    //画随机线
    private function drawRandomLine()
    {
        $color = imagecolorallocate($this->canvas, mt_rand(50, 155), mt_rand(50, 155), mt_rand(50, 155));
        for ($i = 0; $i < 10; $i++) {
            imageline($this->canvas, mt_rand(0, $this->_config['width']), mt_rand(0, $this->_config['height']), mt_rand(0, $this->_config['width']), mt_rand(0, $this->_config['height']), $color);
        }
        return $this;
    }

    //画圆弧
    private function drawArc()
    {
        $color = imagecolorallocate($this->canvas, mt_rand(50, 155), mt_rand(50, 155), mt_rand(50, 155));
        for ($i = 0; $i < 5; $i++) {
            imagearc($this->canvas,
                mt_rand(0, $this->_config['width']),
                mt_rand(0, $this->_config['height']),
                mt_rand(0, $this->_config['width']),
                mt_rand(0, $this->_config['height']),
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
        for ($i = 0; $i < $this->_config['len']; $i++) {
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
        $x = ($this->_config['width'] - 10) / $this->_config['len'];
        for ($i = 0; $i < $this->_config['len']; $i++) {
            $color = imagecolorallocate($this->canvas, mt_rand(50, 155), mt_rand(50, 155), mt_rand(50, 155));
            imagettftext(
                $this->canvas,
                $this->_config['fontSize'],
                mt_rand(-30, 30),
                $x * $i + mt_rand(6, 10),
                mt_rand(20, $this->_config['height'] - ($this->_config['fontSize'] / 10.0)),
                $color,
                $this->_config['fontFile'],
                $this->code[$i]
            );
        }
        return $this;
    }

    //显示验证码
    public function show()
    {
        $this->createCanvas()->drawLine()->generateCode()->writeCode()->drawRandomLine()->drawPixel()->drawArc();
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
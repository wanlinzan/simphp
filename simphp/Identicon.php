<?php
namespace simphp;
/**
 * @author Benjamin Laugueux <benjamin@yzalis.com>
 */
class Identicon
{
    /**
     * @var mixed
     */
    protected $generatedImage;

    /**
     * @var array
     */
    protected $color;

    /**
     * @var array
     */
    protected $backgroundColor;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var int
     */
    protected $pixelRatio;

    /**
     * @var string
     */
    private $hash;

    /**
     * @var array
     */
    private $arrayOfSquare = [];


    /**
     * GdGenerator constructor.
     */
    public function __construct()
    {
        if (!extension_loaded('gd')) {
            throw new \Exception('GD库没有安装！');
        }
    }


    /**
     * Set the image color.
     *
     * @param string|array $color The color in hexa (3 or 6 chars) or rgb array
     *
     * @return $this
     */
    public function setColor($color)
    {
        if (null === $color) {
            return $this;
        }

        $this->color = $this->convertColor($color);

        return $this;
    }

    /**
     * Set the image background color.
     *
     * @param string|array $backgroundColor The color in hexa (3 or 6 chars) or rgb array
     *
     * @return $this
     */
    public function setBackgroundColor($backgroundColor)
    {
        if (null === $backgroundColor) {
            return $this;
        }

        $this->backgroundColor = $this->convertColor($backgroundColor);

        return $this;
    }

    /**
     * @param array|string $color
     *
     * @return array
     */
    private function convertColor($color)
    {
        if (is_array($color)) {
            return $color;
        }

        if (preg_match('/^#?([a-z\d])([a-z\d])([a-z\d])$/i', $color, $matches)) {
            $color = $matches[1].$matches[1];
            $color .= $matches[2].$matches[2];
            $color .= $matches[3].$matches[3];
        }

        preg_match('/#?([a-z\d]{2})([a-z\d]{2})([a-z\d]{2})$/i', $color, $matches);

        return array_map(function ($value) {
            return hexdec($value);
        }, array_slice($matches, 1, 3));
    }

    /**
     * Get the color.
     *
     * @return array
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Get the background color.
     *
     * @return array
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Convert the hash into an multidimensional array of boolean.
     *
     * @return $this
     */
    private function convertHashToArrayOfBoolean()
    {
        preg_match_all('/(\w)(\w)/', $this->hash, $chars);

        foreach ($chars[1] as $i => $char) {
            $index = (int) ($i / 3);
            $data = $this->convertHexaToBoolean($char);

            $items = [
                0 => [0, 4],
                1 => [1, 3],
                2 => [2],
            ];

            foreach ($items[$i % 3] as $item) {
                $this->arrayOfSquare[$index][$item] = $data;
            }

            ksort($this->arrayOfSquare[$index]);
        }

        $this->color = array_map(function ($data) {
            return hexdec($data) * 16;
        }, array_reverse($chars[1]));

        return $this;
    }

    /**
     * Convert an hexadecimal number into a boolean.
     *
     * @param string $hexa
     *
     * @return bool
     */
    private function convertHexaToBoolean($hexa)
    {
        return (bool) intval(round(hexdec($hexa) / 10));
    }

    /**
     * @return array
     */
    public function getArrayOfSquare()
    {
        return $this->arrayOfSquare;
    }

    /**
     * Get the identicon string hash.
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Generate a hash from the original string.
     *
     * @param string $string
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setString($string)
    {
        if (is_null($string)) {
            throw new \Exception('字符串不能为null');
        }

        $this->hash = md5($string);

        $this->convertHashToArrayOfBoolean();

        return $this;
    }

    /**
     * Set the image size.
     *
     * @param int $size
     *
     * @return $this
     */
    public function setSize($size)
    {
        if (null === $size) {
            return $this;
        }

        $this->size = $size;
        $this->pixelRatio = (int) round($size / 5);

        return $this;
    }

    /**
     * Get the image size.
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Get the pixel ratio.
     *
     * @return int
     */
    public function getPixelRatio()
    {
        return $this->pixelRatio;
    }

    /**
     * @return $this
     */
    private function generateImage()
    {
        // prepare image
        $this->generatedImage = imagecreatetruecolor($this->getPixelRatio() * 5, $this->getPixelRatio() * 5);

        $rgbBackgroundColor = $this->getBackgroundColor();
        if (null === $rgbBackgroundColor) {
            $background = imagecolorallocate($this->generatedImage, 0, 0, 0);
            imagecolortransparent($this->generatedImage, $background);
        } else {
            $background = imagecolorallocate($this->generatedImage, $rgbBackgroundColor[0], $rgbBackgroundColor[1], $rgbBackgroundColor[2]);
            imagefill($this->generatedImage, 0, 0, $background);
        }

        // prepare color
        $rgbColor = $this->getColor();
        $gdColor = imagecolorallocate($this->generatedImage, $rgbColor[0], $rgbColor[1], $rgbColor[2]);

        // draw content
        foreach ($this->getArrayOfSquare() as $lineKey => $lineValue) {
            foreach ($lineValue as $colKey => $colValue) {
                if (true === $colValue) {
                    imagefilledrectangle($this->generatedImage, $colKey * $this->getPixelRatio(), $lineKey * $this->getPixelRatio(), ($colKey + 1) * $this->getPixelRatio(), ($lineKey + 1) * $this->getPixelRatio(), $gdColor);
                }
            }
        }

        return $this;
    }
    /**
     * {@inheritdoc}
     */
    private function getImageResource($string, $size = null, $color = null, $backgroundColor = null)
    {
        $this->setString($string)
            ->setSize($size)
            ->setColor($color)
            ->setBackgroundColor($backgroundColor)
            ->generateImage();

        return $this->generatedImage;
    }

    /**
     * {@inheritdoc}
     */
    public function getImageBinaryData($string, $size = 64, $color = null, $backgroundColor = null)
    {
        ob_start();
        imagepng($this->getImageResource($string, $size, $color, $backgroundColor));
        $imageData = ob_get_contents();
        ob_end_clean();
        return $imageData;
    }

    public function displayImage($string, $size = 64, $color = null, $backgroundColor = null)
    {
        header('Content-Type: image/png');
        echo $this->getImageBinaryData($string, $size, $color, $backgroundColor);
    }
}
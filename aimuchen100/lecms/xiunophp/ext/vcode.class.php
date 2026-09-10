<?php
/**
 * 验证码类
 */
class vcode
{

    private $vcodename = 'vcode';

    // 随机因子
    public $charset = 'ABCDEFGHKMNPRTUVWXY23456789';

    // 指定字体大小
    public $fontsize = 18;

    // 验证码长度
    public $codelen = 4;

    // 宽度
    public $width = 130;

    // 高度
    public $height = 45;

    // 验证码
    private $code;

    // 图形资源句柄
    private $img;

    // 指定的字体
    private $font;

    // 指定字体颜色
    private $fontcolor;


    public function __construct() {
        $this->font = ROOT_PATH.'static/vcode_font/arvo_regular.ttf';
    }

    //生成验证码
    public function get_vcode($vcodename = '',$width = 130, $height = 45) {
        @ob_clean(); // 清理图片输出前内容，避免生成错误！
        $vcodename && $this->vcodename = $vcodename;
        $this->createCode();

        $width > 0 AND $this->width = min(200, $width ? $width : 120);
        $height > 0 AND $this->height = min(100, ($height ? $height : 32) - 2);

        $this->createBg();
        $this->createLine();
        $this->createFont();
        $this->outPut();

        return $this->code;
    }

    //生成随机验证码
    private function createCode() {
        $code = '';
        $charset_len = strlen($this->charset) - 1;
        for ($i = 0; $i < $this->codelen; $i++) {
            $code .= $this->charset[rand(1, $charset_len)];
        }
        $this->code = trim($code);

        $_SESSION[$this->vcodename] = $code;
    }

    //生成背景
    private function createBg() {
        $this->img = imagecreatetruecolor($this->width, $this->height);
        $color = imagecolorallocate($this->img, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
        imagefilledrectangle($this->img, 0, $this->height, $this->width, 0, $color);
    }

    //生成文字
    private function createFont() {
        $_x = ($this->width - 10) / $this->codelen;
        for ($i = 0; $i < $this->codelen; $i ++) {
            $this->fontcolor = imagecolorallocate($this->img, mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 100));
            imagettftext($this->img, $this->fontsize, mt_rand(- 20, 20), $_x * $i + $_x / 3, $this->height / 1.4, $this->fontcolor, $this->font, $this->code[$i]);
        }
    }

    //生成干扰线条
    private function createLine() {
        for ($i = 0; $i < 6; $i ++) {
            $color = imagecolorallocate($this->img, mt_rand(100, 200), mt_rand(100, 200), mt_rand(100, 200));
            imageline($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }
        for ($i = 0; $i < 30; $i ++) {
            $color = imagecolorallocate($this->img, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
            imagestring($this->img, mt_rand(1, 5), mt_rand(0, $this->width), mt_rand(0, $this->height), '*', $color);
        }

    }

    //显示
    private function outPut() {
        @ob_start();
        @ob_clean(); //关键代码，防止出现'图像因其本身有错无法显示'的问题。
        header('Content-type:image/png');
        imagepng($this->img);
        imagedestroy($this->img);
    }
}
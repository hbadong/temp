<?php
/**
 * 缩略图裁切代理（原创实现）
 * 用法：/thumb.php?src=<图片URL或站内相对路径>&w=260&h=180
 * 结果按 4:3 等比例裁切并缓存到 upload/thumb/，避免每次请求重算。
 */
declare(strict_types=1);

$src = isset($_GET['src']) ? trim($_GET['src']) : '';
$w = isset($_GET['w']) ? (int)$_GET['w'] : 260;
$h = isset($_GET['h']) ? (int)$_GET['h'] : 180;

$w = max(16, min(1200, $w));
$h = max(16, min(1200, $h));

if ($src === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'missing src';
    exit;
}

// 只允许站内图片，避免被当作任意 URL 代理
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$allowed = ['www.lecms.local', $host];
$abs = $src;
if (strpos($src, 'http') !== 0) {
    $abs = 'http://' . $host . '/' . ltrim($src, '/');
}
// 只比对主机名（去除端口），避免 127.0.0.1:8090 这类带端口的 HOST 误判
$hostName = parse_url('http://' . $host, PHP_URL_HOST);
$parts = parse_url($abs);
$srcHost = isset($parts['host']) ? $parts['host'] : '';
if ($srcHost === '' || $srcHost !== $hostName) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'forbidden host';
    exit;
}

$cacheDir = __DIR__ . '/upload/thumb/';
if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0777, true); }
$key = md5($src . '_' . $w . '_' . $h);
$cacheFile = $cacheDir . $key . '.jpg';

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 86400 * 30) {
    header('Content-Type: image/jpeg');
    header('Cache-Control: public, max-age=86400');
    readfile($cacheFile);
    exit;
}

$bin = false;
// 站内相对路径：直接读本地文件（内置服务器是单线程的，回环请求会造成自锁）
if (strpos($src, 'http') !== 0) {
    $local = __DIR__ . '/' . ltrim(str_replace('..', '', $src), '/');
    if (is_file($local)) {
        $bin = @file_get_contents($local);
    }
} else {
    $ctx = stream_context_create(['http' => ['timeout' => 3, 'follow_location' => 1, 'max_redirects' => 2]]);
    if ($srcHost === $hostName) {
        // 同主机也尽量走本地：把 URL 路径映射到站点根目录
        $path = isset($parts['path']) ? $parts['path'] : '';
        $local = __DIR__ . '/' . ltrim(str_replace('..', '', $path), '/');
        if ($path !== '' && is_file($local)) {
            $bin = @file_get_contents($local);
        } else {
            $bin = @file_get_contents($abs, false, $ctx);
        }
    }
}
if ($bin === false || strlen($bin) < 32) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'source not found';
    exit;
}

$tmp = $cacheDir . 'tmp_' . $key;
file_put_contents($tmp, $bin);
$srcImg = @imagecreatefromstring($bin);
if (!$srcImg) {
    @unlink($tmp);
    http_response_code(415);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'unsupported image';
    exit;
}

$sw = imagesx($srcImg);
$sh = imagesy($srcImg);
// 等比例居中裁切
$srcRatio = $sw / $sh;
$dstRatio = $w / $h;
if ($srcRatio > $dstRatio) {
    $cropW = (int)round($sh * $dstRatio);
    $cropH = $sh;
    $cropX = (int)round(($sw - $cropW) / 2);
    $cropY = 0;
} else {
    $cropW = $sw;
    $cropH = (int)round($sw / $dstRatio);
    $cropX = 0;
    $cropY = (int)round(($sh - $cropH) / 2);
}

$dst = imagecreatetruecolor($w, $h);
$white = imagecolorallocate($dst, 255, 255, 255);
imagefilledrectangle($dst, 0, 0, $w, $h, $white);
imagecopyresampled($dst, $srcImg, 0, 0, $cropX, $cropY, $w, $h, $cropW, $cropH);

header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=86400');
imagejpeg($dst, null, 82);
imagejpeg($dst, $cacheFile, 82);

imagedestroy($dst);
imagedestroy($srcImg);
@unlink($tmp);

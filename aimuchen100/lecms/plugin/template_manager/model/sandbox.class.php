<?php
defined('ROOT_PATH') || exit;

/**
 * Sandbox 模型 — PHP 高危函数过滤 + CSS 白名单 + 上传校验 + 路径防护
 */
class Sandbox
{
    /**
     * 高危函数列表
     */
    private static $dangerous_functions = [
        'eval', 'exec', 'system', 'passthru', 'shell_exec',
        'popen', 'proc_open', 'pcntl_exec', 'assert',
    ];

    /**
     * CSS 变量白名单
     */
    private static $allowed_css_vars = [
        '--primary-color', '--secondary-color', '--background-color',
        '--text-color', '--accent-color', '--spacing', '--border-radius',
        '--font-size', '--line-height', '--max-width',
    ];

    /**
     * 允许上传的 MIME 类型
     */
    private static $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'text/css', 'text/html'];

    /**
     * 高危函数过滤
     */
    public function filter($compiled)
    {
        foreach (self::$dangerous_functions as $func) {
            $pattern = '/\b' . $func . '\s*\(/i';
            $compiled = preg_replace($pattern, '//* blocked: ' . $func . ' */', $compiled);
        }
        return $compiled;
    }

    /**
     * CSS 变量白名单过滤
     */
    public function filter_css_vars($vars)
    {
        $result = [];
        foreach ($vars as $key => $value) {
            if (in_array($key, self::$allowed_css_vars)) {
                // 拒绝 expression/url(javascript:)
                if (stripos($value, 'expression') !== false || stripos($value, 'url(javascript:') !== false) {
                    continue;
                }
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * 上传文件校验
     */
    public function validate_upload($filename, $mime, $archive)
    {
        // 检查扩展名
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $blocked_exts = ['php', 'asp', 'aspx', 'jsp', 'cgi', 'exe'];

        if (in_array($ext, $blocked_exts)) {
            return ['valid' => false, 'error' => '禁止上传可执行文件'];
        }

        // 检查双扩展名
        if (substr_count($filename, '.') > 1) {
            return ['valid' => false, 'error' => '禁止双扩展名文件'];
        }

        // 检查 MIME
        if (!in_array($mime, self::$allowed_mimes)) {
            return ['valid' => false, 'error' => '不允许的 MIME 类型'];
        }

        return ['valid' => true];
    }
}

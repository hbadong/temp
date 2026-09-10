<?php
/**
 * SpiderDetect - 蜘蛛检测引擎
 *
 * 识别 7 种主流搜索引擎爬虫的 User-Agent，返回引擎名称标识。
 * 支持大小写不敏感匹配，支持变体 UA 片段识别。
 *
 * @author     沐尘100
 * @version    1.0.0
 * @cms_version 3.0.0
 */

class SpiderDetect
{
    /**
     * 搜索引擎 UA 正则模式映射
     * key   => 引擎标识（返回值）
     * value => PCRE 正则模式（已开启 i 修饰符，大小写不敏感）
     */
    private static $patterns = [
        'baidu'      => '/Baiduspider/i',
        'google'     => '/Googlebot/i',
        'sogou'      => '/Sogou/i',
        '360'        => '/360Spider/i',
        'bing'       => '/bingbot/i',
        'bytedance'  => '/Bytespider/i',
        'yisou'      => '/YisouSpider/i',
    ];

    /**
     * 引擎标识映射表（确保纯数字键 '360' 以字符串形式返回）
     * 因为 PHP 会将纯数字字符串数组键自动转为整数
     */
    private static $engineLabels = [
        'baidu'      => 'baidu',
        'google'     => 'google',
        'sogou'      => 'sogou',
        '360'        => '360',
        'bing'       => 'bing',
        'bytedance'  => 'bytedance',
        'yisou'      => 'yisou',
    ];

    /**
     * 检测 User-Agent 是否为蜘蛛，返回引擎名称
     *
     * @param   string  $user_agent  HTTP User-Agent 字符串
     * @return  string|false         引擎标识（baidu/google/sogou/360/bing/bytedance/yisou）
     *                                或 false（非蜘蛛 / 空字符串）
     */
    public static function detect($user_agent)
    {
        if (empty($user_agent) || !is_string($user_agent)) {
            return false;
        }

        foreach (self::$engineLabels as $engine_key => $engine_label) {
            if (preg_match(self::$patterns[$engine_key], $user_agent)) {
                return $engine_label;
            }
        }

        return false;
    }
}

<?php
/**
 * SEO URL 提交（BaiduSubmit）模型。
 *
 * 设计要点：
 * - 依赖注入：构造接受 $db 与 $http_client，便于测试 mock
 *   （http_client 必须实现 post($url, $body, $headers, $timeout) 接口）。
 * - 默认 http_client 为 baidu_submit_curl_client，封装 cURL POST 调用，
 *   CURLOPT_TIMEOUT=10s，失败不阻断发布流程。
 * - 支持 7 个搜索引擎端点（baidu/sogou/360/bing/toutiao/shenma/google），
 *   真实 token / site 信息由调用方通过 get_endpoint() 注入；占位符
 *   YOUR_SITE/YOUR_TOKEN/YOUR_KEY 仅作为默认模板。
 * - should_push 实现日/周/月三级频率控制，全部基于 pre_seo_push_log 查询。
 * - log() 写 INSERT 到 pre_seo_push_log；submit() 循环调用 submit_to_engine
 *   并捕获 Throwable，异常时返回 0，不抛出。
 */

class baidu_submit
{
    /** @var int 当前站点 ID */
    private $site_id = 0;

    /** @var object|null LECMS 数据库对象 */
    private $db;

    /** @var object HTTP 客户端（实现 post 方法） */
    private $http_client;

    /** @var int cURL 超时秒数（Baidu API 限制） */
    private $curl_timeout = 10;

    /**
     * 搜索引擎端点模板。真实部署时由 admin 控制器读取站点配置后，
     * 通过 set_endpoint() 注入实际 token/site 后再调用。
     *
     * @var array<string,string>
     */
    private $engine_endpoints = array(
        'baidu'   => 'http://data.zz.baidu.com/urls?site=YOUR_SITE&token=YOUR_TOKEN',
        'sogou'   => 'http://fav.travelsogou.com/urls?from=YOUR_SITE',
        '360'     => 'http://info.so.360.cn/urls?from=YOUR_SITE',
        'bing'    => 'https://www.bing.com/indexnow?key=YOUR_KEY',
        'toutiao' => 'https://search.cdn-toutiao.com/urls?from=YOUR_SITE',
        'shenma'  => 'http://so.sm.cn/urls?from=YOUR_SITE',
        'google'  => 'https://www.google.com/ping?sitemap=YOUR_SITEMAP',
    );

    /**
     * @param int $site_id 当前站点 ID
     * @param object|null $db 数据库对象；未注入时尝试 $GLOBALS['run']->db
     * @param object|null $http_client 实现 post($url,$body,$headers,$timeout) 的 HTTP 客户端
     */
    public function __construct($site_id = 0, $db = null, $http_client = null)
    {
        $this->site_id = (int)$site_id;
        $this->db = $db;
        $this->http_client = $http_client ?: new baidu_submit_curl_client($this->curl_timeout);
    }

    /**
     * 获取搜索引擎端点列表（用于 admin 控制器列出配置项）。
     *
     * @return array<string,string>
     */
    public function get_endpoints()
    {
        return $this->engine_endpoints;
    }

    /**
     * 覆盖指定引擎端点（admin 保存站点 token/site 时调用）。
     *
     * @param string $engine 搜索引擎标识
     * @param string $url 完整 API URL（含 token/site）
     */
    public function set_endpoint($engine, $url)
    {
        $engine = (string)$engine;
        if (!isset($this->engine_endpoints[$engine])) {
            return false;
        }
        $this->engine_endpoints[$engine] = (string)$url;
        return true;
    }

    /**
     * 提交 URL 到全部 7 个搜索引擎。
     *
     * 任一 engine 抛异常不影响后续 engine；返回成功提交的 engine 数量。
     * 调用方可据此判断是否完全成功；count=0 表示全部失败。
     *
     * @param int $site_id 站点 ID
     * @param array $urls URL 列表
     * @return int
     */
    public function submit($site_id, $urls)
    {
        $site_id = (int)$site_id;
        $urls = is_array($urls) ? array_values($urls) : array();
        $urls = array_filter($urls, function ($u) {
            return is_string($u) && $u !== '';
        });
        if (empty($urls)) {
            return 0;
        }

        $success = 0;
        foreach ($this->engine_endpoints as $engine => $endpoint) {
            try {
                $resp = $this->submit_to_engine($engine, $urls);
                $http_code = isset($resp['http_code']) ? (int)$resp['http_code'] : 0;
                $error = isset($resp['error']) ? (string)$resp['error'] : '';
                $body = isset($resp['response']) ? (string)$resp['response'] : '';
                $status = ($http_code >= 200 && $http_code < 300 && $error === '') ? 1 : 0;
                $response_text = $body !== '' ? $body : $error;
                $this->log($site_id, $engine, $urls[0], $status, $response_text);
                if ($status === 1) {
                    $success++;
                }
            } catch (Throwable $e) {
                try {
                    $this->log($site_id, $engine, isset($urls[0]) ? $urls[0] : '', 0, $e->getMessage());
                } catch (Throwable $ignored) {
                    // 写日志失败也不抛出
                }
            }
        }
        return $success;
    }

    /**
     * 提交 URL 列表到单个搜索引擎。
     *
     * bing 用 JSON POST（host + urlList），baidu/sogou/360/toutiao/shenma 用换行分隔
     * 的 URL 列表 POST，google 用 GET（sitemap=YOUR_SITEMAP）。
     *
     * @param string $engine 搜索引擎标识
     * @param array $urls URL 列表
     * @return array{response:string, http_code:int, error:string}
     */
    public function submit_to_engine($engine, $urls)
    {
        $engine = (string)$engine;
        $urls = is_array($urls) ? array_values($urls) : array();
        if (!isset($this->engine_endpoints[$engine])) {
            return array('response' => '', 'http_code' => 0, 'error' => 'unknown engine');
        }
        $endpoint = $this->engine_endpoints[$engine];
        if (empty($urls)) {
            return array('response' => '', 'http_code' => 0, 'error' => 'empty urls');
        }

        if ($engine === 'google') {
            // Google sitemap ping：GET ?sitemap=<url>
            $sitemap = $urls[0];
            $sep = strpos($endpoint, '?') === false ? '?' : '&';
            $url = $endpoint . $sep . 'sitemap=' . urlencode($sitemap);
            return $this->http_client->post($url, '', array(), $this->curl_timeout);
        }

        if ($engine === 'bing') {
            // Bing IndexNow：POST JSON {host, urlList}
            $parsed = parse_url($endpoint);
            $host = isset($parsed['host']) ? $parsed['host'] : '';
            $body = json_encode(array('host' => $host, 'urlList' => $urls));
            $headers = array('Content-Type: application/json');
            return $this->http_client->post($endpoint, $body, $headers, $this->curl_timeout);
        }

        // baidu / sogou / 360 / toutiao / shenma：POST 一行一个 URL
        $body = implode("\n", $urls);
        $headers = array('Content-Type: text/plain');
        return $this->http_client->post($endpoint, $body, $headers, $this->curl_timeout);
    }

    /**
     * 频率控制：判断当前是否应该推送。
     *
     * - day:  今日 00:00:00 之后没有成功日志 → true
     * - week: 本周一 00:00:00 之后没有成功日志 → true
     * - month:本月 1 日 00:00:00 之后没有成功日志 → true
     *
     * @param int $site_id 站点 ID
     * @param string $engine 搜索引擎标识
     * @param string $frequency day|week|month
     * @return bool
     */
    public function should_push($site_id, $engine, $frequency = 'day')
    {
        $site_id = (int)$site_id;
        $engine = (string)$engine;
        $frequency = (string)$frequency;
        $threshold = $this->get_threshold($frequency);
        if ($threshold === 0) {
            // 未知 frequency：保守放行
            return true;
        }

        try {
            $db = $this->get_db();
        } catch (Throwable $e) {
            // 没有 db 时保守放行（避免误阻断发布）
            return true;
        }

        $sql = "SELECT `id` FROM `{$db->tablepre}seo_push_log` "
            . "WHERE `site_id` = " . intval($site_id)
            . " AND `engine` = '" . addslashes($engine) . "'"
            . " AND `status` = 1"
            . " AND `dateline` >= " . $threshold
            . " ORDER BY `dateline` DESC LIMIT 1";
        $row = $db->fetch_first($sql);
        return empty($row);
    }

    /**
     * 记录推送结果到 pre_seo_push_log。
     *
     * @return bool
     */
    public function log($site_id, $engine, $url, $status, $response)
    {
        $site_id = (int)$site_id;
        $engine = (string)$engine;
        $url = (string)$url;
        $status = $status ? 1 : 0;
        $response = (string)$response;
        $dateline = isset($_ENV['_time']) ? (int)$_ENV['_time'] : time();

        $db = $this->get_db();
        $sql = "INSERT INTO `{$db->tablepre}seo_push_log` "
            . "(`site_id`, `engine`, `url`, `status`, `response`, `dateline`) VALUES ("
            . $site_id . ", '" . addslashes($engine) . "', '" . addslashes($url) . "', "
            . $status . ", '" . addslashes($response) . "', " . $dateline . ")";
        return (bool)$db->query($sql);
    }

    /**
     * 获取数据库连接，兼容前台 hook 通过 $GLOBALS['run'] 注入连接。
     *
     * @return object
     * @throws RuntimeException
     */
    private function get_db()
    {
        if ($this->db === null && isset($GLOBALS['run']) && is_object($GLOBALS['run'])) {
            try {
                $controller_db = $GLOBALS['run']->db;
                if (is_object($controller_db)) {
                    $this->db = $controller_db;
                }
            } catch (Throwable $e) {
                // 统一转为下方的数据库缺失异常
            }
        }
        if ($this->db === null) {
            throw new RuntimeException('baidu_submit 需要注入 db 对象');
        }
        return $this->db;
    }

    /**
     * 根据频率计算时间阈值（Unix 时间戳）。
     *
     * @param string $frequency day|week|month
     * @return int 0 表示未知频率
     */
    private function get_threshold($frequency)
    {
        $now = isset($_ENV['_time']) ? (int)$_ENV['_time'] : time();
        switch ($frequency) {
            case 'day':
                return strtotime(date('Y-m-d', $now));
            case 'week':
                $weekday = (int)date('N', $now); // 1=Mon..7=Sun
                $monday = strtotime('-' . ($weekday - 1) . ' day', strtotime(date('Y-m-d', $now)));
                return $monday;
            case 'month':
                return strtotime(date('Y-m-01', $now));
            default:
                return 0;
        }
    }
}

/**
 * 默认 HTTP 客户端：基于 cURL，POST 调用。
 *
 * 独立成类以便子类化或被依赖注入替换；测试时通过 mock 实现相同 post 接口。
 */
class baidu_submit_curl_client
{
    /** @var int 超时秒数 */
    private $timeout = 10;

    public function __construct($timeout = 10)
    {
        $this->timeout = (int)$timeout;
    }

    /**
     * @param string $url 完整 URL
     * @param string $body 请求 body（GET 模式可为空）
     * @param array $headers HTTP 头数组
     * @param int $timeout 超时秒数
     * @return array{response:string, http_code:int, error:string}
     */
    public function post($url, $body, $headers = array(), $timeout = 10)
    {
        if (!function_exists('curl_init')) {
            return array('response' => '', 'http_code' => 0, 'error' => 'curl not available');
        }

        $ch = curl_init();
        $opts = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int)$timeout,
            CURLOPT_CONNECTTIMEOUT => (int)$timeout,
        );
        if ($body !== '') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $body;
        } else {
            $opts[CURLOPT_HTTPGET] = true;
        }
        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }
        @curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return array(
            'response' => $response === false ? '' : (string)$response,
            'http_code' => (int)$http_code,
            'error' => (string)$error,
        );
    }
}

?>
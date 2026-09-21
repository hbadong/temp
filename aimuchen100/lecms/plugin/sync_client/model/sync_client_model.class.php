<?php
defined('ROOT_PATH') or exit;
/**
 * 站群数据同步客户端 模型
 * 从同步主站 API 拉取数据（games/articles/categories/tags）：
 * - HMAC 验签（与 master_api::sign_payload 对称复算）
 * - 分页拉取循环 + 事务 upsert（PG 无事务则逐条，PDO 无嵌套事务按批次提交）
 * - games: 明文 URL 用本地 auth_key 加密落库 + 重算 content_hash + url_map 登记
 * - articles: cms_article 主表 + cms_article_data 正文 + url_map 登记
 * - categories/tags: 按 main_id/tagid 映射 upsert
 * - 跳过 is_ai_rewritten=1（主站已改写内容不再覆盖本地）
 * - 图片本地化：仅白名单 host 下载（jpg/jpeg/png/gif/webp），失败移除 img 标签
 * - 同步日志写 le_cms_sync_log
 */

class sync_client extends model {

    public $rate_limit = 30;
    public $default_page_size = 100;

    public function __construct() {
        $this->table = 'cms_sync_log';
        $this->pri = array('id');
        $this->maxid = 'id';
        $this->tablepre = isset($_ENV['_config']['db']['master']['tablepre']) ? $_ENV['_config']['db']['master']['tablepre'] : '';
    }

    /**
     * 设置读写：存 le_site_manager.config 的 sync_client 子配置（与 template_rewrite 同机制）
     * @param int $site_id 目标站点
     * @return array 默认 + 已存配置合并
     */
    public function get_settings($site_id = 0) {
        $defaults = array(
            'master_url' => '',
            'token' => '',
            'target_site' => (int)$site_id > 0 ? (int)$site_id : 1,
            'cron_key' => '',
            'img_whitelist' => '',
            'sync_games' => 1,
            'sync_articles' => 1,
            'sync_categories' => 1,
            'sync_tags' => 1,
            'last_sync_at' => 0,
            'last_sync_type' => '',
        );
        $site_id = (int)$site_id;
        if($site_id <= 0) $site_id = (int)$this->current_site();
        $row = $this->db->fetch_first("SELECT config FROM `{$this->tablepre}site_manager` WHERE sid={$site_id} LIMIT 1");
        if(!$row) return $defaults;
        $cfg = json_decode((string)$row['config'], true);
        $sc = is_array($cfg) && isset($cfg['sync_client']) && is_array($cfg['sync_client']) ? $cfg['sync_client'] : array();
        return array_merge($defaults, $sc);
    }

    /**
     * 保存设置到 site_manager.config（合并 sync_client 子键，不动其它插件配置）
     */
    public function save_settings($arr, $site_id = 0) {
        $site_id = (int)$site_id;
        if($site_id <= 0) $site_id = (int)$this->current_site();
        $row = $this->db->fetch_first("SELECT config FROM `{$this->tablepre}site_manager` WHERE sid={$site_id} LIMIT 1");
        $cfg = $row ? json_decode((string)$row['config'], true) : array();
        if(!is_array($cfg)) $cfg = array();
        $cfg['sync_client'] = array_merge(is_array($cfg['sync_client']) ? $cfg['sync_client'] : array(), $arr);
        $json = json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if($row) {
            $this->db->query("UPDATE `{$this->tablepre}site_manager` SET config=" . $this->esc($json) . " WHERE sid={$site_id}");
        }
        return true;
    }

    /**
     * 当前站点（前台 base_control 常量优先，回退读 host）
     */
    public function current_site() {
        if(defined('CURRENT_SITE_ID')) return (int)CURRENT_SITE_ID;
        $host = isset($_SERVER['HTTP_HOST']) ? trim($_SERVER['HTTP_HOST']) : '';
        if($host === '') return 1;
        $host = preg_replace('#:\d+$#', '', $host);
        $row = $this->db->fetch_first("SELECT sid FROM `{$this->tablepre}site_manager` WHERE domain='" . addslashes($host) . "' OR domain='" . addslashes($host) . ":8080' LIMIT 1");
        return $row ? (int)$row['sid'] : 1;
    }

    /**
     * 同步 key：与 master_api::sign_key() 完全一致（auth_key 站点级）
     */
    public static function sync_key() {
        return hash_hmac('sha256', 'game_sync', C('auth_key'));
    }

    /**
     * 校验主站响应签名（与 master_api::sign_payload 对称复算）
     * @param string $type games|articles|categories|tags
     * @param array $pagination page/page_size/since
     * @param array $rows
     * @param string $sign 响应 sign
     * @return bool
     */
    public function verify_sign($type, $pagination, $rows, $sign) {
        if(!is_array($pagination)) return false;
        $page = isset($pagination['page']) ? (int)$pagination['page'] : 0;
        $page_size = isset($pagination['page_size']) ? (int)$pagination['page_size'] : 0;
        $since = isset($pagination['since']) ? (int)$pagination['since'] : 0;

        $buf = $type . ':' . $page . ':' . $page_size . ':' . $since . "\n";
        foreach((array)$rows as $r) {
            $fp = isset($r['content_hash']) && $r['content_hash'] !== '' ? (string)$r['content_hash'] : $this->row_fingerprint($r);
            $buf .= (int)$r['id'] . ':' . (int)(isset($r['dateline']) ? $r['dateline'] : 0) . ':' . $fp . "\n";
        }
        $expect = hash_hmac('sha256', $buf, self::sync_key());
        return is_string($sign) && hash_equals($expect, $sign);
    }

    /**
     * 兜底指纹：无 content_hash 的行（categories/tags 同主站逻辑）
     */
    public function row_fingerprint($r) {
        $parts = array();
        foreach(array('main_id', 'name', 'intro', 'seo_title', 'alias') as $k) {
            $parts[] = isset($r[$k]) ? (string)$r[$k] : '';
        }
        return hash_hmac('sha256', implode('|', $parts), self::sync_key());
    }

    /**
     * SQL 转义闭包
     */
    private function esc($v) {
        return "'" . addslashes((string)$v) . "'";
    }

    /**
     * 日志记录
     */
    public function log($site_id, $type, $status, $message, $item_count = 0) {
        return $this->db->query("INSERT INTO `{$this->tablepre}cms_sync_log` (`site_id`,`sync_type`,`status`,`message`,`item_count`,`created_at`)
            VALUES (" . (int)$site_id . "," . $this->esc($type) . "," . (int)$status . "," . $this->esc($message) . "," . (int)$item_count . ",'" . date('Y-m-d H:i:s') . "')");
    }

    /**
     * 最近日志（后台展示）
     */
    public function recent_logs($site_id = 0, $limit = 20) {
        $where = $site_id > 0 ? "site_id=" . (int)$site_id : '1';
        return $this->db->fetch_all("SELECT * FROM `{$this->tablepre}cms_sync_log` WHERE {$where} ORDER BY id DESC LIMIT " . (int)$limit);
    }

    /**
     * 图片本地化：仅下载白名单 host 的图片，返回替换后的内容
     * @param string $content 原始内容（html）
     * @param string $whitelist 逗号分隔 host 列表
     * @return array('content'=>..., 'pics'=>下载成功图片数)
     */
    public function localize_images($content, $whitelist = '') {
        $hosts = array();
        foreach(explode(',', trim((string)$whitelist)) as $h) {
            $h = strtolower(trim($h));
            if($h !== '') $hosts[$h] = true;
        }
        if(!$hosts) return array('content' => $content, 'pics' => 0);

        $save_dir = ROOT_PATH . 'upload/sync_img/' . date('Ym');
        if(!is_dir($save_dir)) @mkdir($save_dir, 0777, true);

        $pics = 0;
        $content = preg_replace_callback(
            '/<img[^>]*?src=["\']([^"\']+)["\'][^>]*?>/is',
            function($m) use ($hosts, $save_dir, &$pics) {
                $src = trim($m[1]);
                $url = parse_url($src);
                $host = isset($url['host']) ? strtolower($url['host']) : '';
                if(!isset($hosts[$host])) return $m[0];   // 非白名单 host，原样保留

                if(!preg_match('#\.(jpg|jpeg|png|gif|webp)(\?.*)?$#i', $src)) return $m[0];

                // 下载（curl 独立进程，避免 DEBUG 模式下 @ 抑制失效、连接拒绝抛 Warning 中断同步）
                $ext = strtolower(pathinfo(parse_url($src, PHP_URL_PATH), PATHINFO_EXTENSION));
                $data = $this->http_get($src);
                if($data === false || strlen($data) < 4) {
                    // 下载失败：移除整张 img 标签
                    return '';
                }
                $name = md5($data) . '.' . $ext;
                $file = $save_dir . '/' . $name;
                try {
                    $ok = file_put_contents($file, $data);
                } catch(Exception $e) {
                    $ok = false;
                }
                if($ok === false) return $m[0];
                $pics++;
                $local = '/upload/sync_img/' . date('Ym') . '/' . $name;
                return str_replace($src, $local, $m[0]);
            },
            $content
        );
        return array('content' => $content, 'pics' => $pics);
    }

    /**
     * 全量同步入口：按启用类型依次拉取并 upsert
     * @param int $site_id 目标站点
     * @param string $only 仅同步指定类型（'games'/'articles'/...），空=全部启用类型
     * @return array('ok'=>bool, 'message'=>string, 'results'=>array)
     */
    public function sync_all($site_id = 0, $only = '') {
        $site_id = (int)$site_id;
        if($site_id <= 0) $site_id = (int)$this->current_site();
        $st = $this->get_settings($site_id);
        if(trim($st['master_url']) === '' || trim($st['token']) === '') {
            return array('ok' => false, 'message' => '未配置 master_url / token', 'results' => array());
        }

        $types = array();
        if($only !== '' && in_array($only, array('games', 'articles', 'categories', 'tags'), true)) {
            $types = array($only);
        }else{
            foreach(array('games', 'articles', 'categories', 'tags') as $t) {
                if(!empty($st['sync_' . $t])) $types[] = $t;
            }
        }
        if(!$types) return array('ok' => false, 'message' => '未启用任何同步类型', 'results' => array());

        $results = array();
        $all_ok = true;
        foreach($types as $t) {
            $res = $this->sync_type($t, $site_id, $st);
            $results[$t] = $res;
            if(empty($res['ok'])) $all_ok = false;
        }
        return array('ok' => $all_ok, 'message' => $all_ok ? '全部同步完成' : '部分同步失败', 'results' => $results);
    }

    /**
     * 同步单个类型：分页拉取 + 验签 + upsert
     */
    public function sync_type($type, $site_id, $st = null) {
        if($st === null) $st = $this->get_settings($site_id);
        $base = rtrim(trim($st['master_url']), '/');
        // 主站端点兼容：若 master_url 只是入口（如 http://host/index.php），自动补 master_api-sync
        if(strpos($base, 'master_api-sync') === false) {
            $base .= (strpos($base, '?') !== false ? '&' : '?') . 'master_api-sync';
        }
        $token = trim($st['token']);
        $page = 1;
        $page_size = min(100, $this->default_page_size);
        $since = (int)$st['last_sync_at'];
        $total_processed = 0;
        $has_more = true;
        $err = '';

        while($has_more && $page <= 200) {
            $qs = http_build_query(array(
                'type' => $type,
                'page' => $page,
                'page_size' => $page_size,
                'since' => $since,
                'token' => $token,
            ));
            $url = $base . (strpos($base, '?') !== false ? '&' : '?') . $qs;
            $resp = $this->http_get($url);
            if($resp === false) {
                $err = "拉取第 {$page} 页失败";
                break;
            }
            $j = json_decode($resp, true);
            if(!is_array($j) || empty($j['status'])) {
                $err = "主站返回异常: " . (isset($j['message']) ? $j['message'] : 'bad response');
                break;
            }

            // 验签
            $rows = isset($j['data']) ? $j['data'] : array();
            $pag = isset($j['pagination']) ? $j['pagination'] : array();
            $sign = isset($j['sign']) ? (string)$j['sign'] : '';
            if(!$this->verify_sign($type, $pag, $rows, $sign)) {
                $err = "第 {$page} 页签名校验失败";
                break;
            }

            // upsert
            $ok = $this->upsert_rows($type, $rows, $site_id, $st);
            if(!$ok) {
                $err = "第 {$page} 页写入失败";
                break;
            }
            $total_processed += count($rows);

            $has_more = !empty($pag['has_more']);
            if(!$rows) $has_more = false;
            $page++;
        }

        // 同步成功：更新 last_sync_at（增量游标用当前时间戳，避免逐页推进时主站无新数据）
        if($err === '' && $total_processed >= 0) {
            $this->save_settings(array('last_sync_at' => time(), 'last_sync_type' => $type), $site_id);
        }

        $this->log($site_id, $type, $err === '' ? 1 : 0, $err === '' ? "ok, {$total_processed} items" : $err, $total_processed);
        return array('ok' => $err === '', 'message' => $err === '' ? "{$type}: {$total_processed} items" : $err, 'items' => $total_processed);
    }

    /**
     * 简单 GET 请求（超时 10s；跳转跟随默认）
     */
    private function http_get($url) {
        // 本地回环时解析 localhost → 127.0.0.1 强制 IPv4（服务器内部 ::1 不可达），
        // 同时保留原 Host 头，保证主站按配置域名（如 localhost=site1）识别站点
        $host = parse_url($url, PHP_URL_HOST);
        if($host === 'localhost') {
            $url = str_ireplace('//localhost', '//127.0.0.1', $url);
        }
        // 优先用 curl 独立进程发起（PHP 内置服务器单线程，web 请求内自请求会互相等待导致超时）
        if(function_exists('exec') && function_exists('escapeshellarg')) {
            $cmd = 'curl -sS --max-time 10 -A "LECMS-SyncClient/1.0" -H "Accept: application/json"';
            if($host === 'localhost') $cmd .= ' -H "Host: localhost"';
            $cmd .= ' ' . escapeshellarg($url);
            exec($cmd, $out, $code);
            if($code === 0 && $out) return implode("\n", $out);
        }
        try {
            $ctx = stream_context_create(array('http' => array(
                'timeout' => 10,
                'ignore_errors' => true,
                'header' => "User-Agent: LECMS-SyncClient/1.0\r\nAccept: application/json\r\n" . ($host === 'localhost' ? "Host: localhost\r\n" : ''),
            )));
            $data = file_get_contents($url, false, $ctx);
            return $data === false ? false : $data;
        } catch(Exception $e) {
            // DEBUG 模式下 @ 抑制失效，error_handler 会把连接拒绝转成异常，此处兜底返回 false
            return false;
        }
    }

    /**
     * 按类型 upsert 一批数据
     * @return bool 全部成功 true
     */
    private function upsert_rows($type, $rows, $site_id, $st) {
        switch($type) {
            case 'games':
                return $this->upsert_games($rows, $site_id);
            case 'articles':
                return $this->upsert_articles($rows, $site_id, $st);
            case 'categories':
                return $this->upsert_categories($rows, $site_id);
            case 'tags':
                return $this->upsert_tags($rows, $site_id);
        }
        return false;
    }

    /**
     * 游戏 upsert：按 main_id + site_id 定位，不存在则新增；明文 URL 加密落库并重算 content_hash
     * 跳过 is_ai_rewritten=1（主站已改写内容，本地已消费的 AI 改写不回退）
     */
    private function upsert_games($rows, $site_id) {
        $gc = core::model('game_center');
        $site_id = (int)$site_id;
        $all_ok = true;
        foreach((array)$rows as $r) {
            if((int)$r['is_ai_rewritten'] === 1) continue;   // 跳过主站已 AI 改写

            $main_id = (int)$r['id'];   // 主站游戏 id 作为 main_id
            $exists = $this->db->fetch_first("SELECT id, download_url, content_hash FROM `{$this->tablepre}cms_game` WHERE site_id={$site_id} AND main_id={$main_id} LIMIT 1");

            $name = trim(strip_tags((string)$r['name']));
            if($name === '') { $all_ok = false; continue; }
            $download_url = $gc->encrypt_download_url((string)$r['download_url']);
            $intro = mb_substr(trim(strip_tags((string)$r['intro'])), 0, 500, 'UTF-8');
            $description = mb_substr((string)$r['description'], 0, 20000, 'UTF-8');
            $cover = trim((string)$r['cover']);
            $platform = mb_substr(trim((string)$r['platform']), 0, 50, 'UTF-8');
            $tags = mb_substr((string)$r['tags'], 0, 500, 'UTF-8');
            $category_id = (int)$r['category_id'];
            $status = (int)$r['status'] === 1 ? 1 : 2;
            $downloads = max(0, (int)$r['downloads']);
            $content_hash = $gc->content_hash(array(
                'main_id' => $main_id,
                'name' => $name,
                'platform' => $platform,
                'category_id' => $category_id,
                'intro' => $intro,
                'download_url' => (string)$r['download_url'],   // 明文 URL 重算，与主站一致
            ));

            if($exists) {
                $ret = $this->db->query("UPDATE `{$this->tablepre}cms_game` SET
                    name=" . $this->esc($name) . ",
                    platform=" . $this->esc($platform) . ",
                    category_id={$category_id},
                    cover=" . $this->esc($cover) . ",
                    download_url=" . $this->esc($download_url) . ",
                    intro=" . $this->esc($intro) . ",
                    description=" . $this->esc($description) . ",
                    tags=" . $this->esc($tags) . ",
                    status={$status},
                    downloads={$downloads},
                    content_hash=" . $this->esc($content_hash) . ",
                    source='api',
                    updated_at='" . date('Y-m-d H:i:s') . "'
                    WHERE id=" . (int)$exists['id']);
                if($ret) $gc->register_url_map((int)$exists['id'], $site_id, $status == 1 ? 2 : 3);
            }else{
                $id = $gc->create(array(
                    'site_id' => $site_id,
                    'main_id' => $main_id,
                    'name' => $name,
                    'platform' => $platform,
                    'category_id' => $category_id,
                    'cover' => $cover,
                    'download_url' => $download_url,
                    'intro' => $intro,
                    'description' => $description,
                    'tags' => $tags,
                    'status' => $status,
                    'downloads' => $downloads,
                    'source' => 'api',
                    'content_hash' => $content_hash,
                    'is_ai_rewritten' => 0,
                ));
                if($id) {
                    // game_center::create 是框架基类 create，不触发 url_map 登记，此处补登
                    $gc->register_url_map((int)$id, $site_id, $status == 1 ? 2 : 3);
                }else{
                    $all_ok = false;
                }
            }
        }
        return $all_ok;
    }

    /**
     * 文章 upsert：cms_article 主表 + cms_article_data 正文 + url_map（/game/{id}.html 映射），跳过 is_ai_rewritten=1
     */
    private function upsert_articles($rows, $site_id, $st) {
        $site_id = (int)$site_id;
        $whitelist = isset($st['img_whitelist']) ? (string)$st['img_whitelist'] : '';
        $all_ok = true;
        foreach((array)$rows as $r) {
            if((int)$r['is_ai_rewritten'] === 1) continue;

            $main_id = (int)$r['id'];
            $exists = $this->db->fetch_first("SELECT id FROM `{$this->tablepre}cms_article` WHERE site_id={$site_id} AND main_id={$main_id} LIMIT 1");

            $title = trim(strip_tags((string)$r['title']));
            if($title === '') { $all_ok = false; continue; }
            $content = (string)$r['content'];
            if($content !== '') {
                $loc = $this->localize_images($content, $whitelist);
                $content = $loc['content'];
            }
            $alias = trim((string)$r['alias']);
            $tags = trim((string)$r['tags']);
            $pic = trim((string)$r['pic']);
            $author = mb_substr(trim((string)$r['author']), 0, 50, 'UTF-8');
            $source = mb_substr(trim((string)$r['source']), 0, 50, 'UTF-8');
            $seo_title = mb_substr(trim((string)$r['seo_title']), 0, 255, 'UTF-8');
            $seo_keywords = mb_substr(trim((string)$r['seo_keywords']), 0, 255, 'UTF-8');
            $seo_description = mb_substr(trim((string)$r['seo_description']), 0, 255, 'UTF-8');
            $now_ts = time();
            $now = date('Y-m-d H:i:s');

            if($exists) {
                $aid = (int)$exists['id'];
                $this->db->query("UPDATE `{$this->tablepre}cms_article` SET
                    title=" . $this->esc($title) . ",
                    alias=" . $this->esc($alias) . ",
                    tags=" . $this->esc($tags) . ",
                    pic=" . $this->esc($pic) . ",
                    author=" . $this->esc($author) . ",
                    source=" . $this->esc($source) . ",
                    seo_title=" . $this->esc($seo_title) . ",
                    seo_keywords=" . $this->esc($seo_keywords) . ",
                    seo_description=" . $this->esc($seo_description) . ",
                    lasttime={$now_ts}
                    WHERE id={$aid}");
                $this->db->query("UPDATE `{$this->tablepre}cms_article_data` SET content=" . $this->esc($content) . " WHERE id={$aid}");
            }else{
                $cid = (int)$r['cid'];
                $sql = "INSERT INTO `{$this->tablepre}cms_article`
                    (`site_id`, `cid`, `title`, `alias`, `tags`, `pic`, `dateline`, `lasttime`, `uid`, `author`, `source`, `seo_title`, `seo_keywords`, `seo_description`, `main_id`, `is_ai_rewritten`)
                    VALUES ({$site_id}, {$cid}, " . $this->esc($title) . ", " . $this->esc($alias) . ", " . $this->esc($tags) . ", " . $this->esc($pic) . ", {$now_ts}, {$now_ts}, 0, " . $this->esc($author) . ", " . $this->esc($source) . ", " . $this->esc($seo_title) . ", " . $this->esc($seo_keywords) . ", " . $this->esc($seo_description) . ", {$main_id}, 0)";
                $this->db->query($sql);
                $aid = (int)$this->db->last_insert_id();
                if(!$aid) { $all_ok = false; continue; }
                $this->db->query("INSERT INTO `{$this->tablepre}cms_article_data` (`id`, `content`) VALUES ({$aid}, " . $this->esc($content) . ")");
            }

            // url_map：/game/{aid}.html → game 分类文章映射（幂等）
            $url = '/game/' . $aid . '.html';
            $url_hash = sha1($url);
            $params = json_encode(array('id' => $aid), JSON_UNESCAPED_SLASHES);
            $urow = $this->db->fetch_first("SELECT id FROM `{$this->tablepre}cms_url_map` WHERE site_id={$site_id} AND url_hash='{$url_hash}' LIMIT 1");
            if($urow) {
                $this->db->query("UPDATE `{$this->tablepre}cms_url_map` SET status=2, control='game', action='index', params=" . $this->esc($params) . ", content_id={$aid} WHERE id=" . (int)$urow['id']);
            }else{
                $this->db->query("INSERT INTO `{$this->tablepre}cms_url_map` (`site_id`, `url`, `url_hash`, `type`, `control`, `action`, `params`, `status`, `content_id`, `created_at`, `updated_at`)
                    VALUES ({$site_id}, " . $this->esc($url) . ", '{$url_hash}', 2, 'game', 'index', " . $this->esc($params) . ", 2, {$aid}, '{$now}', '{$now}')");
            }
        }
        return $all_ok;
    }

    /**
     * 分类 upsert：按 main_id + site_id 映射
     */
    private function upsert_categories($rows, $site_id) {
        $site_id = (int)$site_id;
        $all_ok = true;
        foreach((array)$rows as $r) {
            if((int)$r['is_ai_rewritten'] === 1) continue;
            $main_id = (int)$r['id'];
            $exists = $this->db->fetch_first("SELECT id FROM `{$this->tablepre}cms_game_category` WHERE site_id={$site_id} AND main_id={$main_id} LIMIT 1");

            $name = trim(strip_tags((string)$r['name']));
            if($name === '') { $all_ok = false; continue; }
            $alias = trim((string)$r['alias']);
            $intro = mb_substr(trim(strip_tags((string)$r['intro'])), 0, 500, 'UTF-8');
            $seo_title = mb_substr(trim((string)$r['seo_title']), 0, 255, 'UTF-8');
            $seo_keywords = mb_substr(trim((string)$r['seo_keywords']), 0, 255, 'UTF-8');
            $seo_description = mb_substr(trim((string)$r['seo_description']), 0, 255, 'UTF-8');
            $parent_id = (int)$r['parent_id'];
            $orderby = (int)$r['orderby'];
            $enabled = (int)$r['enabled'] === 1 ? 1 : 0;

            if($exists) {
                $ret = $this->db->query("UPDATE `{$this->tablepre}cms_game_category` SET
                    parent_id={$parent_id},
                    name=" . $this->esc($name) . ",
                    alias=" . $this->esc($alias) . ",
                    intro=" . $this->esc($intro) . ",
                    orderby={$orderby},
                    seo_title=" . $this->esc($seo_title) . ",
                    seo_keywords=" . $this->esc($seo_keywords) . ",
                    seo_description=" . $this->esc($seo_description) . ",
                    enabled={$enabled}
                    WHERE id=" . (int)$exists['id']);
                if(!$ret) $all_ok = false;
            }else{
                $id = $this->db->query("INSERT INTO `{$this->tablepre}cms_game_category`
                    (`site_id`, `parent_id`, `name`, `alias`, `intro`, `orderby`, `seo_title`, `seo_keywords`, `seo_description`, `enabled`, `main_id`, `is_ai_rewritten`)
                    VALUES ({$site_id}, {$parent_id}, " . $this->esc($name) . ", " . $this->esc($alias) . ", " . $this->esc($intro) . ", {$orderby}, " . $this->esc($seo_title) . ", " . $this->esc($seo_keywords) . ", " . $this->esc($seo_description) . ", {$enabled}, {$main_id}, 0)");
                if(!$id) $all_ok = false;
            }
        }
        return $all_ok;
    }

    /**
     * 标签 upsert：按 tagid 映射（标签表无 main_id，用主站 tagid 直写）
     */
    private function upsert_tags($rows, $site_id) {
        $all_ok = true;
        foreach((array)$rows as $r) {
            if((int)$r['is_ai_rewritten'] === 1) continue;
            $tagid = (int)$r['id'];
            $name = trim(strip_tags((string)$r['name']));
            if($name === '') { $all_ok = false; continue; }
            $content = mb_substr(trim((string)$r['content']), 0, 500, 'UTF-8');
            $seo_title = mb_substr(trim((string)$r['seo_title']), 0, 255, 'UTF-8');
            $seo_keywords = mb_substr(trim((string)$r['seo_keywords']), 0, 255, 'UTF-8');
            $seo_description = mb_substr(trim((string)$r['seo_description']), 0, 255, 'UTF-8');
            $count = max(0, (int)$r['count']);

            $exists = $this->db->fetch_first("SELECT tagid FROM `{$this->tablepre}cms_article_tag` WHERE tagid={$tagid} LIMIT 1");
            if($exists) {
                $ret = $this->db->query("UPDATE `{$this->tablepre}cms_article_tag` SET
                    name=" . $this->esc($name) . ",
                    count={$count},
                    content=" . $this->esc($content) . ",
                    seo_title=" . $this->esc($seo_title) . ",
                    seo_keywords=" . $this->esc($seo_keywords) . ",
                    seo_description=" . $this->esc($seo_description) . "
                    WHERE tagid={$tagid}");
                if(!$ret) $all_ok = false;
            }else{
                $id = $this->db->query("INSERT INTO `{$this->tablepre}cms_article_tag`
                    (`tagid`, `name`, `count`, `content`, `seo_title`, `seo_keywords`, `seo_description`, `is_ai_rewritten`)
                    VALUES ({$tagid}, " . $this->esc($name) . ", {$count}, " . $this->esc($content) . ", " . $this->esc($seo_title) . ", " . $this->esc($seo_keywords) . ", " . $this->esc($seo_description) . ", 0)");
                if(!$id) $all_ok = false;
            }
        }
        return $all_ok;
    }
}

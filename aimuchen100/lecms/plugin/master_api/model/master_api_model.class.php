<?php
defined('ROOT_PATH') or exit;
/**
 * 同步主站 API 模型
 * Token 鉴权、限频、HMAC 出口签名、四类数据（games/articles/categories/tags）增量导出。
 * 签名与幂等去重统一采用内容指纹：key = hash_hmac('sha256','game_sync',auth_key)
 * （与 game_center 的 content_hash 同参数，sync_client 端可复算校验）。
 */

class master_api extends model {

    public $rate_limit = 30;      // 每 Token 每分钟最大请求数
    public $rate_window = 60;     // 限频窗口（秒）

    public function __construct() {
        $this->table = 'cms_api_token';
        $this->pri = array('id');
        $this->maxid = 'id';
        // 预置表前缀：避免 $this->tablepre 触发 model::__get('tablepre') 去加载 tablepre 模型
        $this->tablepre = isset($_ENV['_config']['db']['master']['tablepre']) ? $_ENV['_config']['db']['master']['tablepre'] : '';
    }

    /**
     * 导出签名 key：与 game_center::content_hash 完全一致
     */
    public static function sign_key() {
        return hash_hmac('sha256', 'game_sync', C('auth_key'));
    }

    /**
     * 数据导出签名：HMAC-SHA256(规范化载荷,key)
     * 规范化载荷 = 固定拼接字符串（避免 JSON 序列化时键序导致的验签不稳定）
     * $type:$page:$page_size:$since|<id>:<dateline>:<content_fingerprint>|<...>
     */
    public static function sign_payload($type, $page, $page_size, $since, $rows) {
        $buf = $type . ':' . (int)$page . ':' . (int)$page_size . ':' . (int)$since . "\n";
        foreach($rows as $r) {
            $fp = isset($r['content_hash']) && $r['content_hash'] !== '' ? $r['content_hash'] : self::row_fingerprint($r);
            $buf .= (int)$r['id'] . ':' . (int)(isset($r['dateline']) ? $r['dateline'] : 0) . ':' . $fp . "\n";
        }
        return hash_hmac('sha256', $buf, self::sign_key());
    }

    /**
     * 兜底指纹：无 content_hash 的导出行（如 categories / tags）用行主字段摘要
     */
    public static function row_fingerprint($r) {
        $parts = array();
        foreach(array('main_id', 'name', 'intro', 'seo_title', 'alias') as $k) {
            $parts[] = isset($r[$k]) ? (string)$r[$k] : '';
        }
        return hash_hmac('sha256', implode('|', $parts), self::sign_key());
    }

    /**
     * Token 鉴权：有效则返回 Token 行，无效/停用返回 null
     */
    public function auth($token) {
        $token = trim((string)$token);
        if($token === '') return null;
        $row = $this->db->fetch_first("SELECT * FROM `{$this->tablepre}{$this->table}` WHERE token='" . addslashes($token) . "' AND enabled=1 LIMIT 1");
        return $row ? $row : null;
    }

    /**
     * 限频检查：每分钟窗口固定计数（以窗口起始秒为桶），零窗口穿越
     * @param array $token_row 命中 token 行（引用，更新请求计数）
     * @return bool true=放行 false=超限
     */
    public function rate_limit(&$token_row) {
        $now = time();
        $window_start = $now - ($now % $this->rate_window);
        $last_win = isset($token_row['last_request_at']) ? (int)$token_row['last_request_at'] : 0;
        $count = isset($token_row['request_count']) ? (int)$token_row['request_count'] : 0;

        if($last_win < $window_start) {
            // 新窗口：计数重置
            $count = 1;
        }elseif($count >= $this->rate_limit) {
            return false;
        }else{
            $count++;
        }

        $token_row['request_count'] = $count;
        $token_row['last_request_at'] = $window_start;
        $this->db->query("UPDATE `{$this->tablepre}{$this->table}` SET request_count={$count}, last_request_at={$window_start} WHERE id=" . (int)$token_row['id']);
        return true;
    }

    /**
     * 生成唯一 Token
     */
    public function generate($remark = '') {
        $token = '';
        do {
            $token = bin2hex(random_bytes(16));
        } while($this->db->fetch_first("SELECT id FROM `{$this->tablepre}{$this->table}` WHERE token='{$token}' LIMIT 1"));
        $this->create(array(
            'token' => $token,
            'remark' => trim($remark),
            'enabled' => 1,
            'last_request_at' => 0,
            'request_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ));
        return $token;
    }

    /**
     * 后台 Token 列表
     */
    public function admin_list($page = 1, $pagenum = 20) {
        return $this->find_fetch(array(), array('id' => 'DESC'), ($page - 1) * $pagenum, $pagenum);
    }

    public function admin_count() {
        $row = $this->db->fetch_first("SELECT COUNT(*) AS cnt FROM `{$this->tablepre}{$this->table}`");
        return $row ? (int)$row['cnt'] : 0;
    }

    /**
     * ============ 四类数据导出 ============
     */

    /**
     * 导出游戏列表（增量+分页）
     * 导出明文 download_url（库中密文先解密，decrypt 兼容历史明文），
     * sync_client 以其本地 auth_key 加密落库并重算 content_hash。
     */
    public function export_games($page, $page_size, $since) {
        $site = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 1;
        $where = "site_id={$site} AND status=1";
        if((int)$since > 0) {
            $where .= " AND (UNIX_TIMESTAMP(updated_at) > " . (int)$since . " OR updated_at IS NULL)";
        }
        $total = $this->db->fetch_first("SELECT COUNT(*) AS cnt FROM `{$this->tablepre}cms_game` WHERE {$where}");
        $rows = array();
        $page = max(1, (int)$page);
        $page_size = min(100, max(1, (int)$page_size));
        $off = ($page - 1) * $page_size;
        $data = $this->db->fetch_all("SELECT * FROM `{$this->tablepre}cms_game` WHERE {$where} ORDER BY id ASC LIMIT {$page_size} OFFSET {$off}");
        $gc = core::model('game_center');
        foreach((array)$data as $r) {
            $rows[] = array(
                'id' => (int)$r['id'],
                'site_id' => (int)$r['site_id'],
                'main_id' => (int)$r['main_id'],
                'name' => (string)$r['name'],
                'category_id' => (int)$r['category_id'],
                'platform' => (string)$r['platform'],
                'cover' => (string)$r['cover'],
                // 库中为密文，导出解密为明文（decrypt 兼容历史明文原样返回），sync_client 端以其本地 auth_key 重新加密落库
                'download_url' => $gc->decrypt_download_url((string)$r['download_url']),
                'intro' => (string)$r['intro'],
                'description' => (string)$r['description'],
                'tags' => (string)$r['tags'],
                'status' => (int)$r['status'],
                'downloads' => (int)$r['downloads'],
                'content_hash' => (string)$r['content_hash'],
                'is_ai_rewritten' => (int)$r['is_ai_rewritten'],
                'dateline' => $r['created_at'] ? strtotime($r['created_at']) : 0,
            );
        }
        return array($total ? (int)$total['cnt'] : 0, $rows);
    }

    /**
     * 导出文章（增量+分页）
     */
    public function export_articles($page, $page_size, $since) {
        $site = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 1;
        $where = "site_id={$site}";
        if((int)$since > 0) {
            $where .= " AND lasttime > " . (int)$since;
        }
        $total = $this->db->fetch_first("SELECT COUNT(*) AS cnt FROM `{$this->tablepre}cms_article` WHERE {$where}");
        $page = max(1, (int)$page);
        $page_size = min(100, max(1, (int)$page_size));
        $off = ($page - 1) * $page_size;
        $rows = array();
        $data = $this->db->fetch_all("SELECT a.*, d.content FROM `{$this->tablepre}cms_article` a LEFT JOIN `{$this->tablepre}cms_article_data` d ON d.id=a.id WHERE {$where} ORDER BY a.id ASC LIMIT {$page_size} OFFSET {$off}");
        foreach((array)$data as $r) {
            $rows[] = array(
                'id' => (int)$r['id'],
                'site_id' => (int)$r['site_id'],
                'main_id' => (int)$r['main_id'],
                'cid' => (int)$r['cid'],
                'title' => (string)$r['title'],
                'alias' => (string)$r['alias'],
                'tags' => (string)$r['tags'],
                'intro' => (string)$r['intro'],
                'pic' => (string)$r['pic'],
                'author' => (string)$r['author'],
                'source' => (string)$r['source'],
                'dateline' => (int)$r['dateline'],
                'lasttime' => (int)$r['lasttime'],
                'seo_title' => (string)$r['seo_title'],
                'seo_keywords' => (string)$r['seo_keywords'],
                'seo_description' => (string)$r['seo_description'],
                'content' => isset($r['content']) ? (string)$r['content'] : '',
                'is_ai_rewritten' => (int)$r['is_ai_rewritten'],
                'content_hash' => isset($r['is_ai_rewritten']) ? self::article_fps($r) : '',
            );
        }
        return array($total ? (int)$total['cnt'] : 0, $rows);
    }

    /**
     * 文章指纹：标题+正文+seo 摘要（AI 改写判定与增量去重用）
     */
    public static function article_fps($r) {
        $payload = implode('|', array(
            (int)$r['main_id'],
            (string)$r['title'],
            isset($r['content']) ? (string)$r['content'] : '',
            (string)$r['intro'],
        ));
        return hash_hmac('sha256', $payload, self::sign_key());
    }

    /**
     * 导出游戏分类（全量；无更新字段，靠指纹去重）
     */
    public function export_categories($page, $page_size) {
        $site = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 1;
        $where = "site_id={$site} AND enabled=1";
        $total = $this->db->fetch_first("SELECT COUNT(*) AS cnt FROM `{$this->tablepre}cms_game_category` WHERE {$where}");
        $page = max(1, (int)$page);
        $page_size = min(100, max(1, (int)$page_size));
        $off = ($page - 1) * $page_size;
        $rows = array();
        $data = $this->db->fetch_all("SELECT * FROM `{$this->tablepre}cms_game_category` WHERE {$where} ORDER BY id ASC LIMIT {$page_size} OFFSET {$off}");
        foreach((array)$data as $r) {
            $rows[] = array(
                'id' => (int)$r['id'],
                'site_id' => (int)$r['site_id'],
                'main_id' => (int)$r['main_id'],
                'parent_id' => (int)$r['parent_id'],
                'name' => (string)$r['name'],
                'alias' => (string)$r['alias'],
                'intro' => (string)$r['intro'],
                'orderby' => (int)$r['orderby'],
                'seo_title' => (string)$r['seo_title'],
                'seo_keywords' => (string)$r['seo_keywords'],
                'seo_description' => (string)$r['seo_description'],
                'enabled' => (int)$r['enabled'],
                'is_ai_rewritten' => (int)$r['is_ai_rewritten'],
                'content_hash' => self::row_fingerprint($r),
                'dateline' => 0,
            );
        }
        return array($total ? (int)$total['cnt'] : 0, $rows);
    }

    /**
     * 导出标签（全量；无更新字段，靠指纹去重）
     */
    public function export_tags($page, $page_size) {
        $total = $this->db->fetch_first("SELECT COUNT(*) AS cnt FROM `{$this->tablepre}cms_article_tag`");
        $page = max(1, (int)$page);
        $page_size = min(100, max(1, (int)$page_size));
        $off = ($page - 1) * $page_size;
        $rows = array();
        $data = $this->db->fetch_all("SELECT * FROM `{$this->tablepre}cms_article_tag` ORDER BY tagid ASC LIMIT {$page_size} OFFSET {$off}");
        foreach((array)$data as $r) {
            $rows[] = array(
                'id' => (int)$r['tagid'],
                'name' => (string)$r['name'],
                'count' => (int)$r['count'],
                'content' => (string)$r['content'],
                'seo_title' => (string)$r['seo_title'],
                'seo_keywords' => (string)$r['seo_keywords'],
                'seo_description' => (string)$r['seo_description'],
                'is_ai_rewritten' => (int)$r['is_ai_rewritten'],
                'content_hash' => self::row_fingerprint(array('id' => $r['tagid'], 'name' => $r['name'], 'intro' => $r['content'])),
                'dateline' => 0,
            );
        }
        return array($total ? (int)$total['cnt'] : 0, $rows);
    }
}
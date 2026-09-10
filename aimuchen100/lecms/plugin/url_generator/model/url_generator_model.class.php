<?php
defined('ROOT_PATH') or exit;
/**
 * URL生成引擎模型
 * 支持8种SEO URL类型的批量生成、查重、生命周期管理
 */

class url_generator extends model {
    public function __construct() {
        $this->table = 'cms_url_map';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    // URL类型常量
    const TYPE_LIST = 1;       // 列表型: /list-{page}.html
    const TYPE_DETAIL = 2;     // 数字型: /{id}.html
    const TYPE_CATEGORY = 3;   // 分类型: /category/{slug}.html
    const TYPE_TAG = 4;        // 标签型: /tag/{slug}.html
    const TYPE_DATE = 5;       // 日期型: /{year}/{month}/{slug}.html
    const TYPE_ALIAS = 6;      // 别名型: /{game-name}.html
    const TYPE_FLEXIBLE = 7;   // 灵活型: /cid/id/date/game.html
    const TYPE_HASHID = 8;     // HashId型: /{short-hash}.html

    // TYPE_DETAIL 等详情类型基于 cms_game 已存在 ID 生成（方案A），跨类型共享查询结果
    private $_game_ids = array();
    private $_game_aliases = array();

    /**
     * 批量生成URL（主入口）
     * @param int $site_id 站点ID
     * @param int $count 生成数量
     * @param array $type_ratio 各类型占比 [type => ratio, ...]
     * @return int 实际生成数量
     */
    public function generate_urls($site_id, $count, $type_ratio = array()) {
        if(empty($type_ratio)) {
            // 默认比例
            $type_ratio = array(
                self::TYPE_LIST => 0.15,
                self::TYPE_DETAIL => 0.25,
                self::TYPE_CATEGORY => 0.15,
                self::TYPE_TAG => 0.15,
                self::TYPE_DATE => 0.10,
                self::TYPE_ALIAS => 0.10,
                self::TYPE_FLEXIBLE => 0.05,
                self::TYPE_HASHID => 0.05,
            );
        }

        $inserted = 0;
        $batch_size = 1000; // 每批1000条，避免内存溢出

        // 按比例分配，向下取整后把余数补给占比最大的类型，使总数尽量贴近 count
        $type_counts = array();
        $allocated = 0;
        foreach($type_ratio as $type => $ratio) {
            $type_counts[$type] = (int)($count * $ratio);
            $allocated += $type_counts[$type];
        }
        $remainder = $count - $allocated;
        if($remainder > 0 && $type_counts) {
            $max_type = null;
            $max_ratio = -1;
            foreach($type_ratio as $type => $ratio) {
                if($ratio > $max_ratio) {
                    $max_ratio = $ratio;
                    $max_type = $type;
                }
            }
            if($max_type !== null) $type_counts[$max_type] += $remainder;
        }

        foreach($type_counts as $type => $type_count) {
            if($type_count <= 0) continue;

            // 分批生成（index 跨批次递增，避免重复URL）
            $remaining = $type_count;
            $offset = 0;
            while($remaining > 0) {
                $batch = min($remaining, $batch_size);
                $batch_inserted = $this->generate_batch($site_id, $type, $batch, $offset);
                $inserted += $batch_inserted;
                $offset += $batch;
                $remaining -= $batch;
            }
        }

        return $inserted;
    }

    /**
     * 批量生成指定类型的URL
     * @param int $start_index 本批起始 index（跨批次递增，避免重复）
     */
    private function generate_batch($site_id, $type, $count, $start_index = 0) {
        $urls = array();
        for($i = $start_index; $i < $start_index + $count; $i++) {
            $r = $this->generate_single_url($site_id, $type, $i);
            if($r && !empty($r['url'])) {
                $url = $r['url'];
                $params = isset($r['params']) && is_array($r['params']) ? $r['params'] : array();
                $url_hash = $this->make_url_hash($url);
                $score = $this->calculate_score($url);
                $control = $this->get_control_by_type($type);
                $action = $this->get_action_by_type($type);

                $urls[] = array(
                    'site_id' => $site_id,
                    'url' => $url,
                    'url_hash' => $url_hash,
                    'type' => $type,
                    'control' => $control,
                    'action' => $action,
                    'params' => json_encode($params),
                    'score' => $score,
                    'status' => 1,
                );
            }
        }

        if(empty($urls)) return 0;

        // 批量去重插入：单条 INSERT IGNORE（uk_site_hash 唯一索引静默跳过重复）
        // 修复（REQ-02-AC1/AC2/AC4）：原实现用 model::create() 逐条普通 INSERT，
        // 分类型/标签型 slug 每 8 条循环产生的同批重复 URL 以及跨批重复 URL 会命中
        // 唯一索引导致 db->query() 抛异常、整批生成崩溃。INSERT IGNORE 等价于静默去重，
        // 且多行一次性插入，符合「百万级」批量场景。
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $values = array();
        foreach($urls as $u) {
            $values[] = "("
                . (int)$site_id . ", '"
                . addslashes($u['url']) . "', '"
                . addslashes($u['url_hash']) . "', "
                . (int)$type . ", '"
                . addslashes($u['control']) . "', '"
                . addslashes($u['action']) . "', '"
                . addslashes($u['params']) . "', "
                . (int)$u['score'] . ", 1)";
        }
        $sql = "INSERT IGNORE INTO `{$tablepre}cms_url_map`
            (`site_id`, `url`, `url_hash`, `type`, `control`, `action`, `params`, `score`, `status`)
            VALUES " . implode(',', $values);
        $this->db->exec($sql);

        // 注意：db_pdo_mysql::exec() 对 INSERT 开头的 SQL 返回 last_insert_id() 而非受影响行数，
        // 不能把返回值当插入条数累加（会产生 4501 之类荒谬总数）。此处按本批生成的 URL 条目数返回；
        // INSERT IGNORE 会按 uk_site_hash 静默去重，实际落库唯一 URL 可能少于本批条数。
        return count($urls);
    }

    /**
     * 计算 URL 的 SHA256 哈希（小写），供生成/查重/路由共用
     */
    public function make_url_hash($url) {
        // le_cms_url_map.url_hash 列为 char(40)，返回截断 40 位的 SHA256，
        // 与 hook 查询端（parseurl_control_index_rewrite_before）保持一致
        return substr(strtolower(hash('sha256', $url)), 0, 40);
    }

    /**
     * 生成单个URL（返回 url + 路由参数）
     */
    private function generate_single_url($site_id, $type, $index) {
        switch($type) {
            case self::TYPE_LIST:
                $page = $index + 1;
                return array(
                    'url' => '/list-' . $page . '.html',
                    'params' => array('page' => $page),
                );

            case self::TYPE_DETAIL:
                // 基于已存在游戏生成（方案A：不再用 MAX(id)+1 预占不存在的 id）
                $games = $this->get_game_ids($site_id);
                if(!isset($games[$index])) return array('url' => '', 'params' => array());
                $game_id = $games[$index];
                return array(
                    'url' => '/' . $game_id . '.html',
                    'params' => array('id' => $game_id),
                );

            case self::TYPE_CATEGORY:
                $categories = array('rpg', 'action', 'strategy', 'sports', 'puzzle', 'arcade', 'simulation', 'adventure');
                $slug = $categories[$index % count($categories)];
                return array(
                    'url' => '/category/' . $slug . '.html',
                    'params' => array('slug' => $slug),
                );

            case self::TYPE_TAG:
                $tags = array('action', 'multiplayer', 'single-player', 'co-op', 'open-world', 'story-rich', 'casual', 'competitive');
                $slug = $tags[$index % count($tags)];
                return array(
                    'url' => '/tag/' . $slug . '.html',
                    'params' => array('name' => $slug),
                );

            case self::TYPE_DATE:
                $year = date('Y', $_ENV['_time']);
                $month = date('m', $_ENV['_time']);
                $slug = 'game-' . ($index + 1);
                return array(
                    'url' => '/' . $year . '/' . $month . '/' . $slug . '.html',
                    'params' => array('year' => $year, 'month' => $month, 'slug' => $slug, 'page' => $index + 1),
                );

            case self::TYPE_ALIAS:
                // 基于 only_alias 中真实存在的别名生成（INNER JOIN 限定已入库游戏）
                $aliases = $this->get_game_aliases($site_id);
                if(!isset($aliases[$index])) return array('url' => '', 'params' => array());
                $alias = $aliases[$index];
                return array(
                    'url' => '/' . $alias . '.html',
                    'params' => array('alias' => $alias),
                );

            case self::TYPE_FLEXIBLE:
                $games = $this->get_game_ids($site_id);
                if(!isset($games[$index])) return array('url' => '', 'params' => array());
                $game_id = $games[$index];
                $date = date('Ymd', $_ENV['_time']);
                return array(
                    'url' => '/1/' . $game_id . '/' . $date . '/game.html',
                    'params' => array('cid' => 1, 'id' => $game_id, 'date' => $date),
                );

            case self::TYPE_HASHID:
                // 前缀 'g' + base36(id)：与 type2 数字 URL 区分，避免 id 小时 hash 为纯数字
                // 与 /{id}.html 冲突；解码端 game_control::decode_hashid 剥前缀后对称解码
                $games = $this->get_game_ids($site_id);
                if(!isset($games[$index])) return array('url' => '', 'params' => array());
                $game_id = $games[$index];
                $hash = 'g' . base_convert((string)$game_id, 10, 36);
                return array(
                    'url' => '/' . $hash . '.html',
                    'params' => array('hash' => $hash),
                );

            default:
                return array('url' => '', 'params' => array());
        }
    }

    /**
     * 获取站点下已存在的游戏ID列表（升序），跨类型共享，只查询一次
     */
    private function get_game_ids($site_id) {
        if(!isset($this->_game_ids[$site_id])) {
            $tablepre = $_ENV['_config']['db']['master']['tablepre'];
            $rows = $this->db->fetch_all("SELECT id FROM `{$tablepre}cms_game`
                WHERE site_id = " . (int)$site_id . " ORDER BY id ASC");
            $this->_game_ids[$site_id] = array_map('intval', array_column($rows, 'id'));
        }
        return $this->_game_ids[$site_id];
    }

    /**
     * 获取站点下已入库游戏的真实别名列表（INNER JOIN 过滤掉指向不存在游戏的别名）
     */
    private function get_game_aliases($site_id) {
        if(!isset($this->_game_aliases[$site_id])) {
            $tablepre = $_ENV['_config']['db']['master']['tablepre'];
            $rows = $this->db->fetch_all("SELECT a.alias FROM `{$tablepre}only_alias` a
                INNER JOIN `{$tablepre}cms_game` g ON g.id = a.id
                WHERE g.site_id = " . (int)$site_id . " AND a.alias != ''
                ORDER BY a.alias ASC");
            $this->_game_aliases[$site_id] = array_values(array_map('strval', array_column($rows, 'alias')));
        }
        return $this->_game_aliases[$site_id];
    }

    /**
     * SEO评分计算（70-90分）
     */
    public function calculate_score($url) {
        $score = 70;

        // URL长度 < 40 字符
        if(strlen($url) < 40) $score += 5;

        // 含语义关键词
        if(preg_match('#/(category|game|tag)/#', $url)) $score += 5;

        // 无重复路径段
        $path = parse_url($url, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        if(count($segments) === count(array_unique($segments))) $score += 3;

        // 含数字层级
        if(preg_match('#/\d{4}/\d{2}/#', $url)) $score += 2;

        return min(90, $score);
    }

    /**
     * 根据URL类型获取控制器名
     */
    private function get_control_by_type($type) {
        $map = array(
            self::TYPE_LIST => 'game_list',
            self::TYPE_DETAIL => 'game',
            self::TYPE_CATEGORY => 'game',
            self::TYPE_TAG => 'game',
            self::TYPE_DATE => 'game',
            self::TYPE_ALIAS => 'game',
            self::TYPE_FLEXIBLE => 'game',
            self::TYPE_HASHID => 'game',
        );
        return isset($map[$type]) ? $map[$type] : 'game';
    }

    /**
     * 根据URL类型获取操作方法
     */
    private function get_action_by_type($type) {
        return 'index';
    }

    /**
     * 根据URL类型获取路由参数模板（对外接口，供调用方了解各类型参数结构）
     */
    public function get_params_by_type($type) {
        switch($type) {
            case self::TYPE_LIST:   return array('page');
            case self::TYPE_DETAIL: return array('id');
            case self::TYPE_CATEGORY: return array('slug');
            case self::TYPE_TAG:    return array('name');
            case self::TYPE_DATE:   return array('year', 'month', 'slug', 'page');
            case self::TYPE_ALIAS:  return array('alias');
            case self::TYPE_FLEXIBLE: return array('cid', 'id', 'date');
            case self::TYPE_HASHID: return array('hash');
            default:                return array();
        }
    }

    /**
     * 检查URL是否重复（基于 SHA256 哈希）
     */
    public function check_duplicate($site_id, $url) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $url_hash = $this->make_url_hash($url);
        $row = $this->db->fetch_first("
            SELECT id FROM `{$tablepre}cms_url_map`
            WHERE site_id = " . (int)$site_id . " AND url_hash = '" . addslashes($url_hash) . "'
            LIMIT 1
        ");
        return !empty($row);
    }

    /**
     * 清理未使用的URL（30天未使用）
     * @return int 受影响行数
     */
    public function clean_unused_urls($days = 30) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $days = (int)$days;
        $sql = "UPDATE `{$tablepre}cms_url_map` SET status = 3
            WHERE status = 1 AND updated_at < DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        return $this->db->exec($sql);
    }

    /**
     * 更新URL状态为已使用
     */
    public function mark_as_used($url_id, $content_id = 0) {
        return $this->update(array(
            'id' => (int)$url_id,
            'status' => 2,
            'content_id' => (int)$content_id,
        ));
    }

    /**
     * 获取待使用的URL
     */
    public function get_pending_urls($site_id, $limit = 50) {
        $site_id = (int)$site_id;
        $limit = (int)$limit;
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $sql = "SELECT id, url, type, control, action, params FROM `{$tablepre}cms_url_map`
            WHERE site_id = {$site_id} AND status = 1
            ORDER BY id ASC
            LIMIT {$limit}";
        return $this->db->fetch_all($sql);
    }
}

<?php
/**
 * 站点管理模型
 */

defined('ROOT_PATH') or exit;

class site_manager extends model {

    public function __construct() {
        $this->table = 'site_manager';
        $this->pri = array('sid');
        $this->maxid = 'sid';
    }

    /**
     * 获取单个站点
     */
    public function get($sid) {
        return parent::get($sid);
    }

    /**
     * 获取站点列表
     * @param int $status 状态过滤: 1启用 0禁用 -1删除 空=全部
     * @param bool $sorted 是否按 sort_order 排序（数值大的在前）
     * @return array
     */
    public function get_list($status = null, $sorted = true) {
        $where = $status !== null ? array('status' => $status) : array();
        if($sorted) {
            return $this->find_fetch($where, array('sort_order' => 'DESC', 'sid' => 'DESC'));
        }
        return $this->find_fetch($where);
    }

    /**
     * 服务端分页查询站点列表（layui table 数据源）
     * @param int|null $status 状态过滤: 1/0/-1，null 或 '' 表示全部
     * @param string $keyword 站点名称/域名模糊关键词
     * @param int $page 页码（从 1 起）
     * @param int $pagenum 每页条数
     * @return array [list, total]
     */
    public function get_list_page($status, $keyword, $page, $pagenum) {
        $conds = array();
        if($status === 1 || $status === 0 || $status === -1) {
            $conds[] = 'status = ' . (int)$status;
        }
        if($keyword !== '') {
            $kw = addslashes($keyword);
            $conds[] = "(site_name LIKE '%{$kw}%' OR domain LIKE '%{$kw}%')";
        }
        $where = $conds ? ' WHERE ' . implode(' AND ', $conds) : '';
        $tablepre = isset($this->db_conf['master']['tablepre']) ? $this->db_conf['master']['tablepre'] : '';
        $table = $tablepre . $this->table;
        $offset = ((int)$page - 1) * (int)$pagenum;

        $list = $this->db->fetch_all("SELECT * FROM `{$table}`{$where} ORDER BY sort_order DESC, sid DESC LIMIT " . (int)$pagenum . " OFFSET {$offset}");
        $row = $this->db->fetch_first("SELECT COUNT(*) AS cnt FROM `{$table}`{$where}");
        return array($list, $row ? (int)$row['cnt'] : 0);
    }

    /**
     * 按状态统计站点数（概览卡片）
     * @return array ['total' => 总数, 'enabled' => 启用, 'disabled' => 禁用, 'deleted' => 删除]
     */
    public function count_status() {
        $tablepre = isset($this->db_conf['master']['tablepre']) ? $this->db_conf['master']['tablepre'] : '';
        $table = $tablepre . $this->table;
        $rows = $this->db->fetch_all("SELECT status, COUNT(*) AS cnt FROM `{$table}` GROUP BY status");
        $ret = array('total' => 0, 'enabled' => 0, 'disabled' => 0, 'deleted' => 0);
        foreach($rows as $r) {
            $ret['total'] += (int)$r['cnt'];
            $s = (int)$r['status'];
            if($s == 1) {
                $ret['enabled'] += (int)$r['cnt'];
            } elseif($s == 0) {
                $ret['disabled'] += (int)$r['cnt'];
            } elseif($s == -1) {
                $ret['deleted'] += (int)$r['cnt'];
            }
        }
        return $ret;
    }

    /**
     * 复制站点：克隆源站点的主题、config（品牌/CSS 变量/SEO/维护提示）、排序值
     * 注意走 parent::create 而非 create() 包装——config 已为 JSON 字符串，
     * 包装方法会二次 json_encode 导致 config 被双重编码。
     */
    public function copy_site($sid, $site_name, $domain) {
        $site = $this->get($sid);
        if(empty($site)) return false;
        $arr = array(
            'site_name' => $site_name,
            'domain' => $domain,
            'theme' => isset($site['theme']) ? $site['theme'] : 'default',
            'sort_order' => (int)(isset($site['sort_order']) ? $site['sort_order'] : 0),
            'status' => 1,
            'config' => isset($site['config']) ? $site['config'] : '',
            'created_at' => $_ENV['_time'],
            'updated_at' => $_ENV['_time'],
        );
        return parent::create($arr);
    }

    /**
     * 通过域名获取站点（去重校验用，单条记录）
     *
     * 注意：不能使用 safe_str() 处理域名 —— safe_str 只保留「空格/字母/数字/下划线/中日韩文」，
     * 会把域名中的点号(.)与连字符(-)剥掉（example.com → examplecom），导致域名唯一性校验永远匹配不到。
     * db 层（db_pdo_mysql）对 where 值统一 addslashes 转义，直接传原始域名即可防注入。
     *
     * find_fetch 返回的是按 KV 键索引的记录表（如 [cache_key => row]），不是数字数组，
     * 本方法取第一条记录的列数组返回，没有命中则返回空数组。
     */
    public function get_by_domain($domain) {
        $domain = trim($domain);
        if($domain === '') return array();
        $rows = $this->find_fetch(array('domain' => $domain, 'status >' => 0), array(), 0, 1);
        if(!is_array($rows) || empty($rows)) return array();
        $first = reset($rows);   // 取首个 VALUE（生产框架 multi_get 用 KV 键索引；fake_db 同理）
        return is_array($first) ? $first : array();
    }

    /**
     * 创建站点
     */
    public function create($arr) {
        $arr['status'] = 1;
        $arr['theme'] = isset($arr['theme']) ? $arr['theme'] : 'default';
        if(isset($arr['config']) && is_array($arr['config'])) {
            $arr['config'] = json_encode($arr['config']);
        }
        $arr['created_at'] = $_ENV['_time'];
        $arr['updated_at'] = $_ENV['_time'];
        return parent::create($arr);
    }

    /**
     * 更新站点（封装 JSON config 自动编码、updated_at 时间戳、sid 主键注入）
     * 与父类 model::update($data, $life) 签名冲突，因此另命名为 save()
     */
    public function save($sid, $arr) {
        if(isset($arr['config']) && is_array($arr['config'])) {
            $arr['config'] = json_encode($arr['config']);
        }
        $arr['updated_at'] = $_ENV['_time'];
        $arr['sid'] = $sid;
        return parent::update($arr);
    }

    /**
     * 软删除站点
     */
    public function delete_site($sid) {
        return $this->save($sid, array('status' => -1));
    }

    /**
     * 获取站点级 JSON 配置（数组形式，坏数据容错返回空数组）
     */
    public function get_config($sid) {
        $site = $this->get($sid);
        if(empty($site) || empty($site['config'])) return array();
        $cfg = json_decode($site['config'], true);
        return is_array($cfg) ? $cfg : array();
    }

    /**
     * 一键同步全部启用站点：重建域名映射缓存并刷新各站点运行时缓存
     * @return int 同步的站点数
     */
    public function sync_all() {
        // 清空旧映射，强制下次请求重建
        $_ENV['_config']['site_domain_map'] = null;
        $sites = $this->get_list(1);
        $map = array();
        foreach($sites as $site) {
            $map[$site['domain']] = (int)$site['sid'];
        }
        // 写回全局映射（生产环境由 runtime->xset 持久化；测试环境经全局变量断言）
        $GLOBALS['__site_map_cache'] = $map;
        $_ENV['_config']['site_domain_map'] = $map;
        return count($map);
    }

    /**
     * 切换站点状态
     */
    public function toggle_status($sid) {
        $site = $this->get($sid);
        if(empty($site)) return false;
        $new_status = $site['status'] == 1 ? 0 : 1;
        return $this->save($sid, array('status' => $new_status));
    }
}

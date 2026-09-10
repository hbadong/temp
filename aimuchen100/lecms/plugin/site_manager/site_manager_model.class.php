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
     * @return array
     */
    public function get_list($status = null) {
        if($status !== null) {
            return $this->find_fetch(array('status' => $status));
        }
        return $this->find_fetch();
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

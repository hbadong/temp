<?php
defined('ROOT_PATH') or exit;
require_once ROOT_PATH . 'lecms/plugin/spider_pool/model/spider_pool.class.php';

/**
 * spider_pool_control - 后台蜘蛛池域名管理控制器
 *
 * 提供蜘蛛池域名的后台管理：
 * - index()       展示域名列表（含分页）
 * - add()         处理 POST 添加域名请求
 * - edit()        处理 POST 编辑域名请求
 * - delete()      处理 POST 删除域名请求
 * - toggle()      处理 POST 启用/停用域名请求
 * - sort_batch()  处理 POST 批量排序请求
 *
 * 框架兼容性说明：
 * - 控制器文件被框架 process_all() 编译进 runcache，__DIR__ 失效，
 *   必须用 ROOT_PATH 绝对路径 require 模型文件
 * - LECMS 无全局 db() 函数，通过 $this->db（control::__get 注入）传给模型
 * - admin 上下文无 CURRENT_SITE_ID，get_site_id() 优先取 GET/POST 参数，
 *   无参数时 fallback 查询 spider_pool 表中第一个站点
 * - spider_pool 类名小写 + 下划线，与 core::model() 的 <name>_model.class.php
 *   约定不匹配，无法通过 $this->spider_pool 自动加载，直接 new spider_pool()
 *
 * @author     沐尘100
 * @version    1.0.0
 * @cms_version 3.0.0
 */

// spider_pool 模型类名（spider_pool.class.php）与 core::model() 约定的
// <name>_model.class.php 不匹配，无法通过 $this->spider_pool 自动加载，
// 此处直接引入文件以复用 SpiderPool 的增删改查逻辑。
// 注意：本文件会被编译进 runcache，__DIR__ 失效，必须用 ROOT_PATH 绝对路径。
require_once ROOT_PATH . 'lecms/plugin/spider_pool/model/spider_pool.class.php';

class spider_pool_control extends admin_control
{
    /** @var int 默认分页大小 */
    private $page_size = 20;

    /**
     * 插件默认设置
     */
    private function default_settings() {
        return array(
            'collect_interval_min' => 30,
            'cross_site_deploy'    => 0,
            'outbound_link_limit'  => 10,
            'domain_whitelist'     => '',
        );
    }

    /**
     * 读取设置（runtime 缓存）
     */
    private function get_settings() {
        // runtime 在测试/mock 环境可能为 null，容错回退默认值
        $saved = isset($this->runtime) && is_object($this->runtime) ? $this->runtime->xget('spider_pool_settings') : null;
        if (!is_array($saved)) {
            $saved = array();
        }
        return array_merge($this->default_settings(), $saved);
    }

    /**
     * 获取启用站点列表（site_manager 表，兼容插件缺失场景）。
     *
     * @return array [{sid,site_name,domain}, ...]
     */
    private function get_site_list()
    {
        static $sites = null;
        if ($sites !== null) {
            return $sites;
        }
        $sites = array();
        try {
            $db = $this->db;
            $rows = $db->fetch_all("SELECT `sid`,`site_name`,`domain` FROM `{$db->tablepre}site_manager` WHERE `status` = 1 ORDER BY `sort_order` DESC, `sid` ASC");
            if (is_array($rows)) {
                $sites = $rows;
            }
        } catch (Throwable $e) {
            // site_manager 插件未安装时降级为空列表
        }
        return $sites;
    }

    /**
     * 获取当前站点 ID（多站点隔离）。
     *
     * 优先级：GET/POST site_id 参数 → CURRENT_SITE_ID → 唯一启用站点 →
     * 默认取第一个启用站点（多站未指定时给出可切换下拉）。
     *
     * @return int
     */
    private function get_site_id()
    {
        // 优先 GET 参数
        if (isset($_GET['site_id'])) {
            $sid = (int)$_GET['site_id'];
            if ($sid > 0) {
                return $sid;
            }
        }
        // POST 参数
        if (isset($_POST['site_id'])) {
            $sid = (int)$_POST['site_id'];
            if ($sid > 0) {
                return $sid;
            }
        }
        // 前台注入的 CURRENT_SITE_ID
        if (defined('CURRENT_SITE_ID')) {
            $sid = (int)CURRENT_SITE_ID;
            if ($sid > 0) {
                return $sid;
            }
        }
        // 站点列表兜底：单站直接归属；多站取第一个（页面提供下拉切换）
        $sites = $this->get_site_list();
        if (empty($sites)) {
            return 0;
        }
        return (int)$sites[0]['sid'];
    }

    /**
     * 校验域名格式：仅允许点号、字母、数字、连字符；不允许协议前缀或路径。
     *
     * @param string $domain
     * @return bool
     */
    private function is_valid_domain($domain)
    {
        $domain = trim((string)$domain);
        if ($domain === '') {
            return false;
        }
        // 拒绝包含协议前缀、路径或空格
        if (preg_match('#^(https?://|//|/| )#i', $domain)) {
            return false;
        }
        // 简单域名格式：字母/数字/点/连字符，至少包含一个点
        if (!preg_match('/^[A-Za-z0-9.\-]+$/', $domain)) {
            return false;
        }
        if (strpos($domain, '.') === false) {
            return false;
        }
        return true;
    }

    /**
     * 蜘蛛池管理首页。
     *
     * 展示当前站点的全部域名记录（含停用），支持分页。
     */
    public function index()
    {
        $site_id = $this->get_site_id();
        $page    = max(1, (int)R('page', 'G'));
        $pagesize = $this->page_size;
        $offset  = ($page - 1) * $pagesize;

        $pool = new spider_pool($this->db, $site_id);
        $list = $pool->get_list($site_id);
        $total = is_array($list) ? count($list) : 0;

        // 简单分页切片（域名数量一般不会太大，内存切片即可）
        $page_list = array_slice((array)$list, $offset, $pagesize);

        $this->assign('list', $page_list);
        $this->assign('site_id', $site_id);
        $site_list = $this->get_site_list();
        $this->assign('site_list', $site_list);
        $this->assign('total', $total);
        $this->assign('page', $page);
        $this->assign('pagesize', $pagesize);
        $pagebar = $this->get_pagebar($total, $pagesize, $page); $this->assign('pagebar', $pagebar);
        $form_hash = form_hash();
        $this->assign('form_hash', $form_hash);

        // 插件设置（已合并为 index 页第二个 tab）
        $settings = $this->get_settings();
        $this->assign('settings', $settings);

        // 当前激活的 tab（?spider_pool-index-tab-settings 时定位到设置页签）
        $cur_tab = trim((string)R('tab', 'G'));
        $this->assign('cur_tab', $cur_tab);

        $this->display('spider_pool_index.htm');
    }

    /**
     * 添加域名（POST）。
     *
     * 校验表单提交与域名格式，写入 spider_pool 表后跳转回管理首页。
     */
    public function add()
    {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
            return;
        }

        $site_id = $this->get_site_id();
        $domain  = trim((string)R('domain', 'P'));
        $sort    = (int)R('sort', 'P');

        if (!$this->is_valid_domain($domain)) {
            $this->message(1, '域名不合法：仅允许字母/数字/点/连字符，且必须含点号；禁止协议前缀');
            return;
        }

        $pool = new spider_pool($this->db, $site_id);
        if ($pool->exists($site_id, $domain)) {
            $this->message(1, '该域名已在当前站点蜘蛛池中，请勿重复添加');
            return;
        }
        if (!$pool->add($site_id, $domain, $sort)) {
            $this->message(1, '域名添加失败');
            return;
        }

        $this->message(0, '域名添加成功', '?spider_pool-index&site_id=' . $site_id);
    }

    /**
     * 编辑域名（POST）。
     *
     * 校验表单提交、id 与域名格式，更新 domain + sort。
     */
    public function edit()
    {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
            return;
        }

        $site_id = $this->get_site_id();
        $id      = (int)R('id', 'P');
        $domain  = trim((string)R('domain', 'P'));
        $sort    = (int)R('sort', 'P');

        if ($id <= 0) {
            $this->message(1, '记录 ID 缺失');
            return;
        }
        if (!$this->is_valid_domain($domain)) {
            $this->message(1, '域名不合法');
            return;
        }

        $pool = new spider_pool($this->db, $site_id);
        if (!$pool->update($id, $domain, $sort)) {
            $this->message(1, '域名更新失败');
            return;
        }

        $this->message(0, '域名更新成功', '?spider_pool-index&site_id=' . $site_id);
    }

    /**
     * 删除域名（POST）。
     *
     * 模型 delete() 已带 site_id 隔离，无需在控制器重复校验。
     */
    public function delete()
    {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
            return;
        }

        $site_id = $this->get_site_id();
        $id      = (int)R('id', 'P');

        if ($id <= 0) {
            $this->message(1, '记录 ID 缺失');
            return;
        }

        $pool = new spider_pool($this->db, $site_id);
        if (!$pool->delete($id)) {
            $this->message(1, '域名删除失败');
            return;
        }

        $this->message(0, '域名已删除', '?spider_pool-index&site_id=' . $site_id);
    }

    /**
     * 启用/停用域名（POST）。
     *
     * 模型 toggle() 已带 site_id 隔离。
     */
    public function toggle()
    {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
            return;
        }

        $site_id = $this->get_site_id();
        $id      = (int)R('id', 'P');

        if ($id <= 0) {
            $this->message(1, '记录 ID 缺失');
            return;
        }

        $pool = new spider_pool($this->db, $site_id);
        if (!$pool->toggle($id)) {
            $this->message(1, '状态切换失败');
            return;
        }

        $this->message(0, '状态已切换', '?spider_pool-index&site_id=' . $site_id);
    }

    /**
     * 批量排序（POST）。
     *
     * 接收 id => sort 的映射数组，调用模型 sort_batch() 批量更新。
     */
    public function sort_batch()
    {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
            return;
        }

        $site_id = $this->get_site_id();
        $sort_map = R('sort', 'P');

        if (!is_array($sort_map) || empty($sort_map)) {
            $this->message(1, '排序参数缺失');
            return;
        }

        // 仅保留 int id
        $pairs = array();
        foreach ($sort_map as $id => $sort) {
            $id = (int)$id;
            if ($id <= 0) {
                continue;
            }
            $pairs[$id] = (int)$sort;
        }

        if (empty($pairs)) {
            $this->message(1, '排序参数缺失');
            return;
        }

        $pool = new spider_pool($this->db, $site_id);
        if (!$pool->sort_batch($pairs)) {
            $this->message(1, '批量排序失败');
            return;
        }

        $this->message(0, '批量排序已保存', '?spider_pool-index&site_id=' . $site_id);
    }

    /**
     * 插件设置页（兼容入口，已合并进 index 第二个 tab）
     *
     * 设置界面现位于 index() 页面的「插件设置」tab，此处仅做跳转，
     * 兼容旧链接 / 旧书签（index.php?spider_pool-settings）。
     */
    public function settings() {
        $this->message(0, '', 'index.php?spider_pool-index-tab-settings');
    }

    /**
     * 保存插件设置（POST）
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $interval = (int)R('collect_interval_min', 'P');
        if ($interval < 1) {
            $interval = 1;
        }
        if ($interval > 1440) {
            $interval = 1440;
        }

        $limit = (int)R('outbound_link_limit', 'P');
        if ($limit < 0) {
            $limit = 0;
        }
        if ($limit > 200) {
            $limit = 200;
        }

        $whitelist_raw = trim((string)R('domain_whitelist', 'P'));
        $whitelist_arr = array();
        if ($whitelist_raw !== '') {
            foreach (preg_split('/[\r\n,]+/', $whitelist_raw) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                if ($this->is_valid_domain($line)) {
                    $whitelist_arr[] = $line;
                }
            }
        }

        $settings = array(
            'collect_interval_min' => $interval,
            'cross_site_deploy'    => R('cross_site_deploy', 'P') ? 1 : 0,
            'outbound_link_limit'  => $limit,
            'domain_whitelist'     => $whitelist_arr,
        );

        $this->runtime->set('spider_pool_settings', $settings);
        $this->runtime->save_changed();

        E(0, '插件设置已保存');
    }

    /**
     * 批量导入域名（CSV，POST）。
     *
     * CSV 首行为表头 `domain,sort,status`；每行一个域名。
     * 逐行校验并去重，忽略非法行。
     */
    public function import() {
        if (!form_submit()) E(1, lang('submit_invalid'));
        $site_id = $this->get_site_id();
        $file = isset($_FILES['file']['tmp_name']) ? $_FILES['file']['tmp_name'] : '';
        if (!$file || !is_uploaded_file($file)) E(1, '未上传文件');
        $ext = strtolower(pathinfo(isset($_FILES['file']['name']) ? $_FILES['file']['name'] : '', PATHINFO_EXTENSION));
        if ($ext !== 'csv') E(1, '仅支持 .csv 文件');
        if (isset($_FILES['file']['size']) && $_FILES['file']['size'] > 2 * 1024 * 1024) E(1, '文件过大，最大 2MB');

        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/csv.class.php';
        $content = file_get_contents($file);
        $pool = new spider_pool($this->db, $site_id);
        $added = 0;
        $skipped = 0;

        // 兼容两种格式：带表头 `domain,sort,status` 的 CSV，或每行一个域名的纯文本列表
        $lines = preg_split("/\r?\n/", trim($content));
        $first_line = strtolower(trim(isset($lines[0]) ? $lines[0] : ''));
        $has_header = (strpos($first_line, 'domain') !== false);
        $start_idx = $has_header ? 1 : 0;

        for ($i = $start_idx; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') continue;
            if ($has_header) {
                $cols = str_getcsv($line);
                $domain = isset($cols[0]) ? trim((string)$cols[0]) : '';
                $sort = isset($cols[1]) ? (int)$cols[1] : 0;
                $status = isset($cols[2]) ? (int)$cols[2] : 1;
            } else {
                $domain = $line;
                $sort = 0;
                $status = 1;
            }
            if (!$this->is_valid_domain($domain)) {
                $skipped++;
                continue;
            }
            if ($pool->exists($site_id, $domain)) {
                $skipped++;
                continue;
            }
            if (!$pool->add($site_id, $domain, $sort)) {
                $skipped++;
                continue;
            }
            if ($status == 0) {
                // 导入后统一回写状态（add 默认 status=1）
                $db = $this->db;
                $db->query("UPDATE `{$db->tablepre}spider_pool` SET `status` = 0 WHERE `site_id` = " . intval($site_id) . " AND `domain` = '" . addslashes($domain) . "'");
            }
            $added++;
        }
        E(0, "导入完成：新增 {$added} 条，跳过 {$skipped} 条", '?spider_pool-index&site_id=' . $site_id);
    }

    /**
     * 导出当前站点全部域名（CSV 下载）。
     */
    public function export() {
        $site_id = $this->get_site_id();
        $pool = new spider_pool($this->db, $site_id);
        $list = $pool->get_list($site_id);

        require_once ROOT_PATH . 'lecms/plugin/spider_analytics/lib/csv.class.php';
        // 自定义表头：domain,sort,status
        $header = array('domain', 'sort', 'status');
        $out = implode(',', $header) . "\n";
        foreach ((array)$list as $r) {
            $row = array(
                spider_csv::quote(isset($r['domain']) ? $r['domain'] : ''),
                spider_csv::quote(isset($r['sort']) ? $r['sort'] : 0),
                spider_csv::quote(isset($r['status']) ? $r['status'] : 1),
            );
            $out .= implode(',', $row) . "\n";
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=spider-pool-' . $site_id . '-' . date('Ymd-His') . '.csv');
        echo $out;
        exit;
    }

    /**
     * 池效果统计：联合 spider_visit_log 展示当前站点蜘蛛访问概况。
     */
    public function stats() {
        $site_id = $this->get_site_id();
        $pool = new spider_pool($this->db, $site_id);
        $list = $pool->get_list($site_id);

        $stats = array(
            'total'        => 0,
            'today'        => 0,
            'week'         => 0,
            'by_engine'    => array(),
            'domain_hits'  => array(),
            'has_visit_log'=> false,
        );

        try {
            $db = $this->db;
            $pre = $db->tablepre;
            $now = time();
            $today_start = strtotime(date('Y-m-d', $now));
            $week_start  = strtotime(date('Y-m-d', $now)) - 6 * 86400;

            // 当前站点的访问日志概况
            $agg = $db->fetch_first("SELECT COUNT(*) AS cnt, SUM(created_at >= {$today_start}) AS today, SUM(created_at >= {$week_start}) AS week FROM `{$pre}spider_visit_log` WHERE `site_id` = " . intval($site_id));
            if ($agg) {
                $stats['total'] = (int)$agg['cnt'];
                $stats['today'] = (int)$agg['today'];
                $stats['week'] = (int)$agg['week'];
            }
            $eng_rows = $db->fetch_all("SELECT `engine`, COUNT(*) AS cnt FROM `{$pre}spider_visit_log` WHERE `site_id` = " . intval($site_id) . " GROUP BY `engine` ORDER BY cnt DESC");
            foreach ((array)$eng_rows as $r) {
                $stats['by_engine'][$r['engine']] = (int)$r['cnt'];
            }
            // 每个池域名的蜘蛛访问次数：池域名若对应 site_manager 站点，统计该站点的访问日志；
            // 否则按本站 url 匹配统计（外站池域名无本站日志时计 0）。
            $site_map = array();
            foreach ($this->get_site_list() as $sitem) {
                $site_map[strtolower(trim($sitem['domain']))] = (int)$sitem['sid'];
            }
            foreach ((array)$list as $row) {
                $domain = isset($row['domain']) ? $row['domain'] : '';
                if ($domain === '') continue;
                $dl = strtolower(trim($domain));
                if (isset($site_map[$dl])) {
                    $hit = $db->fetch_first("SELECT COUNT(*) AS cnt, MAX(created_at) AS last_at FROM `{$pre}spider_visit_log` WHERE `site_id` = " . intval($site_map[$dl]));
                } else {
                    $hit = $db->fetch_first("SELECT COUNT(*) AS cnt, MAX(created_at) AS last_at FROM `{$pre}spider_visit_log` WHERE `site_id` = " . intval($site_id) . " AND `url` LIKE '" . addslashes('%' . $domain . '%') . "'");
                }
                $stats['domain_hits'][$domain] = array(
                    'cnt'     => $hit && !empty($hit['cnt']) ? (int)$hit['cnt'] : 0,
                    'last_at' => $hit && !empty($hit['last_at']) ? (int)$hit['last_at'] : 0,
                );
            }
            $stats['has_visit_log'] = true;
        } catch (Throwable $e) {
            // spider_analytics 未安装 / visit_log 表不存在时，只展示池列表
            $stats['has_visit_log'] = false;
        }

        $this->assign('site_id', $site_id);
        $site_list = $this->get_site_list();
        $this->assign('site_list', $site_list);
        $this->assign('stats', $stats);
        $this->display('spider_pool_stats.htm');
    }
}
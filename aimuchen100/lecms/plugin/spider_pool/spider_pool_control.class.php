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
     * 获取当前站点 ID（多站点隔离）。
     *
     * 优先 GET/POST 参数 → CURRENT_SITE_ID → fallback 查询第一个站点。
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
        // fallback：查询 spider_pool 表中第一个 site_id
        $db = $this->db;
        $row = $db->fetch_first("SELECT `site_id` FROM `{$db->tablepre}spider_pool` WHERE `site_id` > 0 ORDER BY `site_id` ASC LIMIT 1");
        if ($row && !empty($row['site_id'])) {
            return (int)$row['site_id'];
        }
        return 0;
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
        $this->assign('total', $total);
        $this->assign('page', $page);
        $this->assign('pagesize', $pagesize);
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
}
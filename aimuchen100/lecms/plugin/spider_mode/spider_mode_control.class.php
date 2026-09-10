<?php
defined('ROOT_PATH') or exit;
require_once ROOT_PATH . 'lecms/plugin/spider_mode/model/access_mode.class.php';

/**
 * spider_mode_control - 后台访问模式管理控制器
 *
 * 提供访问模式（normal / spider_only / icp_filing）的后台管理：
 * - index()        展示当前模式状态与切换表单
 * - switch()       处理 POST 切换请求，更新 pre_spider_mode 并记录操作日志
 * - switch_active() 处理 POST 启用/禁用蜘蛛模式请求，更新 spider_mode_active
 *
 * 框架兼容性说明：
 * - 控制器文件被框架 process_all() 编译进 runcache，__DIR__ 失效，
 *   必须用 ROOT_PATH 绝对路径 require 模型文件
 * - LECMS 无全局 db() 函数，通过 $this->db（control::__get 注入）传给模型
 * - admin 上下文无 CURRENT_SITE_ID，get_site_id() 优先取 GET/POST 参数，
 *   无参数时 fallback 查询第一个站点
 *
 * @author     沐尘100
 * @version    1.0.0
 * @cms_version 3.0.0
 */

// AccessMode 模型类文件名（access_mode.class.php）与 core::model() 约定的
// <name>_model.class.php 不匹配，无法通过 $this->access_mode 自动加载，
// 此处直接引入文件以复用 AccessMode 的切换与日志逻辑。
// 注意：本文件会被编译进 runcache，__DIR__ 失效，必须用 ROOT_PATH 绝对路径。
require_once ROOT_PATH . 'lecms/plugin/spider_mode/model/access_mode.class.php';

class spider_mode_control extends admin_control {

    /**
     * 插件默认设置
     */
    private function default_settings() {
        return array(
            'engines'           => array('baidu', 'google', 'sogou', '360', 'bing', 'bytedance', 'yisou'),
            'dual_render'       => 1,
            'icp_number'        => '',
            'icp_footer_link'   => 'https://beian.miit.gov.cn',
        );
    }

    /**
     * 读取设置（runtime 缓存）
     */
    private function get_settings() {
        // runtime 在测试/mock 环境可能为 null，容错回退默认值
        $saved = isset($this->runtime) && is_object($this->runtime) ? $this->runtime->xget('spider_mode_settings') : null;
        if (!is_array($saved)) {
            $saved = array();
        }
        return array_merge($this->default_settings(), $saved);
    }

    /**
     * 获取当前站点 ID（多站点隔离）
     *
     * admin 上下文无 CURRENT_SITE_ID 常量（仅前台 site-manager hook 定义），
     * 因此优先使用 GET/POST 传入的 site_id 参数，其次 CURRENT_SITE_ID，
     * 最后 fallback 查询 spider_mode 表中第一个站点。
     *
     * @return  int
     */
    private function get_site_id() {
        // 优先 GET/POST 参数
        if (isset($_GET['site_id'])) {
            $sid = (int)$_GET['site_id'];
            if ($sid > 0) {
                return $sid;
            }
        }
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
        // fallback：查询第一个站点
        $db = $this->db;
        $row = $db->fetch_first("SELECT `site_id` FROM `{$db->tablepre}spider_mode` WHERE `site_id` > 0 ORDER BY `site_id` ASC LIMIT 1");
        if ($row && !empty($row['site_id'])) {
            return (int)$row['site_id'];
        }
        return 0;
    }

    /**
     * 访问模式管理首页
     *
     * 展示当前模式状态、激活状态、合法模式列表与切换表单。
     */
    public function index() {
        $site_id = $this->get_site_id();
        $access_mode = new AccessMode($site_id, $this->db);

        // control::assign() 为引用传参，需先赋给变量再传入
        $mode   = $access_mode->get_mode();
        $active = $access_mode->is_active();

        $this->assign('mode', $mode);
        $this->assign('active', $active);
        $this->assign_value('valid_modes', AccessMode::VALID_MODES);
        $this->assign('site_id', $site_id);

        // 插件设置（合并自原 settings() 页面，作为第二个 tab 展示）
        $settings = $this->get_settings();
        $engine_labels = array(
            'baidu'     => '百度 Baidu',
            'google'    => '谷歌 Google',
            'sogou'     => '搜狗 Sogou',
            '360'       => '360 搜索',
            'bing'      => '必应 Bing',
            'bytedance' => '头条 ByteDance',
            'yisou'     => '一搜 Yisou',
        );
        $this->assign('settings', $settings);
        $this->assign('engine_labels', $engine_labels);

        // 当前激活 tab（?spider_mode-index-tab-settings 时定位到「插件设置」）
        $tab = trim((string)R('tab', 'G'));
        if ($tab !== 'settings') {
            $tab = 'main';
        }
        $this->assign('tab', $tab);

        $this->display('spider_mode_index.htm');
    }

    /**
     * 切换访问模式（POST）
     *
     * 校验表单提交与模式合法性，调用 AccessMode::switch_mode() 完成切换，
     * 切换成功后反馈并跳转回管理首页。
     */
    public function switch() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $site_id  = $this->get_site_id();
        $new_mode = trim((string)R('mode', 'P'));
        $uid      = isset($this->_uid) ? (int)$this->_uid : 0;
        $ip       = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

        $access_mode = new AccessMode($site_id, $this->db);

        if ($access_mode->switch_mode($new_mode, $uid, $ip)) {
            // 输出 {"err","msg"} 格式——本页 adminAjax.postform 回调按 json.err/json.msg 解析，
            // 勿用 $this->message()（其输出 status/message 键名不匹配，会被前端误判为失败）
            E(0, '访问模式切换成功');
        }

        E(1, '无效的访问模式');
    }

    /**
     * 启用/禁用蜘蛛模式（POST）
     *
     * 接收 active 参数（0/1），调用 AccessMode::switch_active() 更新
     * pre_spider_mode 表的 spider_mode_active 字段，并记录操作日志。
     */
    public function switch_active() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $site_id = $this->get_site_id();
        $active  = R('active', 'P') ? 1 : 0;
        $uid     = isset($this->_uid) ? (int)$this->_uid : 0;
        $ip      = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

        $access_mode = new AccessMode($site_id, $this->db);
        $access_mode->switch_active($active);

        $log_db = $this->db;
        $dateline = isset($_ENV['_time']) ? $_ENV['_time'] : time();
        $log_sql = "INSERT INTO `{$log_db->tablepre}access_mode_log` (`site_id`, `old_mode`, `new_mode`, `uid`, `ip`, `dateline`) VALUES ("
            . intval($site_id) . ", 'switch_active', '" . ($active ? 'enabled' : 'disabled') . "', "
            . intval($uid) . ", '" . addslashes($ip) . "', " . intval($dateline) . ")";
        $log_db->query($log_sql);

        // 同上：E() 输出 {"err","msg"}，与 adminAjax.postform 回调匹配
        E(0, $active ? '蜘蛛模式已启用' : '蜘蛛模式已禁用');
    }

    /**
     * 插件设置页（兼容重定向）
     *
     * 设置页已合并进主页面第二个 tab，此处仅做跳转，
     * 兼容旧导航/收藏链接。配置展示逻辑见 index()。
     */
    public function settings() {
        $this->message(0, '', '?spider_mode-index-tab-settings');
    }

    /**
     * 保存插件设置（POST）
     */
    public function settings_post() {
        if (!form_submit()) {
            $this->message(1, lang('submit_invalid'));
        }

        $engines = R('engines', 'P');
        $valid = array('baidu', 'google', 'sogou', '360', 'bing', 'bytedance', 'yisou');
        $picked = array();
        if (is_array($engines)) {
            foreach ($engines as $e) {
                $e = (string)$e;
                if (in_array($e, $valid, true)) {
                    $picked[] = $e;
                }
            }
        }

        $icp_number = trim((string)R('icp_number', 'P'));
        $icp_link   = trim((string)R('icp_footer_link', 'P'));
        if ($icp_link !== '' && !preg_match('#^(https?:)?//#i', $icp_link)) {
            $icp_link = 'https://' . $icp_link;
        }

        $settings = array(
            'engines'         => $picked,
            'dual_render'     => R('dual_render', 'P') ? 1 : 0,
            'icp_number'      => $icp_number,
            'icp_footer_link' => $icp_link,
        );

        $this->runtime->set('spider_mode_settings', $settings);
        $this->runtime->save_changed();

        E(0, '插件设置已保存');
    }
}

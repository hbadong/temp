<?php
defined('ROOT_PATH') or exit;
/**
 * 下载控制器（CPS 中间页）
 *
 * 修复（对照验证发现）：空实现 → 完整 CPS 流程
 * - REQ-06-AC2 get_download_url：返回含中间页跳转的下载 URL
 * - REQ-06-AC3 index()：3 秒倒计时中间页（模板 JS 倒计时）
 * - REQ-06-AC4 记录点击日志（IP/UA/Referer/时间）
 * - REQ-06-AC5 去重：同 IP+同链接 24h 内仅计 1 次
 * - REQ-06-AC6 get_download_url_with_fallback：主链接失效降级备用链接
 * - 继承 base_control 获得 CURRENT_SITE_ID（站群站点识别）
 *
 * 访问：index.php?download-index-id-{game_id}（动态，伪静态开/关均可用）
 */

// CpsConfig / CpsLog 类非 core::model 自动加载，必须显式 require
require_once ROOT_PATH . 'lecms/plugin/cps_integration/model/cps_config.class.php';
require_once ROOT_PATH . 'lecms/plugin/cps_integration/model/cps_log.class.php';

class download_control extends base_control {

    /**
     * 下载中间页（REQ-06-AC3）
     * 参数：id = 游戏ID
     */
    public function index() {
        $game_id = (int)R('id', 'R');
        if($game_id <= 0) {
            header('HTTP/1.1 404 Not Found');
            exit('无效的下载链接');
        }

        $site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;

        $config = new CpsConfig();
        $link = $config->get_best_link($site_id, $game_id);

        // 该游戏无启用中的推广链接：404
        if(!$link) {
            header('HTTP/1.1 404 Not Found');
            exit('该游戏暂无推广下载链接');
        }

        $download_url = $link['url'];
        $backup_url = isset($link['backup_url']) ? $link['backup_url'] : '';

        // 记录点击日志（REQ-06-AC4/AC5）
        $log = new CpsLog();
        $ip_text = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $is_unique = $log->is_unique_click($link['id'], $ip_text);
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $log->record_click($site_id, $game_id, $link['id'], $is_unique ? 1 : 0, 0, $ip_text, $ua, $referer);
        $config->increment_click($link['id'], $is_unique);

        // 渲染中间页：显示文本做 HTML 转义，JS 跳转用 JSON 原始 URL
        $this->assign_value('game_id', $game_id);
        $this->assign_value('game_name', $this->get_game_name($game_id));
        $this->assign_value('download_url', htmlspecialchars($download_url, ENT_QUOTES, 'UTF-8'));
        $this->assign_value('backup_url', htmlspecialchars($backup_url, ENT_QUOTES, 'UTF-8'));
        $this->assign_value('download_url_json', json_encode($download_url, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT));
        $this->display('cps_redirect.htm');
    }

    /**
     * 获取下载 URL（含中间页跳转）
     * REQ-06-AC2
     * @return string 如 index.php?download-index-id-123
     */
    public function get_download_url($site_id, $game_id) {
        return 'index.php?download-index-id-' . (int)$game_id;
    }

    /**
     * 带降级的下载 URL 获取（REQ-06-AC6）
     * 主链接失效自动降级到备用链接，并标记 is_degraded=1
     * @return string 下载 URL（空=无可用链接）
     */
    public function get_download_url_with_fallback($site_id, $game_id) {
        $config = new CpsConfig();
        $link = $config->get_best_link((int)$site_id, (int)$game_id);
        if(!$link) return '';

        // 主链接可达 → 直接使用
        if($this->check_url_available($link['url'])) {
            return $link['url'];
        }

        // 主链接失效 → 降级到备用链接，并记录降级日志（REQ-06-AC6）
        if(!empty($link['backup_url'])) {
            $this->mark_degraded((int)$site_id, (int)$game_id, $link['id']);
            return $link['backup_url'];
        }

        return $link['url'];
    }

    /**
     * 标记降级：写入一条 is_degraded=1 的点击日志（可追溯）
     */
    public function mark_degraded($site_id, $game_id, $cps_id) {
        $cps_id = (int)$cps_id;
        if($cps_id <= 0) return;
        require_once ROOT_PATH . 'lecms/plugin/cps_integration/model/cps_log.class.php';
        $log = new CpsLog();
        $ip_text = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $log->record_click((int)$site_id, (int)$game_id, $cps_id, 0, 1, $ip_text, $ua, '');
    }

    /**
     * HTTP HEAD 探测链接可用性
     * @return bool
     */
    private function check_url_available($url) {
        if(empty($url) || !preg_match('#^https?://#i', $url)) return false;
        if(!function_exists('curl_init')) return true; // 无 cURL 时视为可用
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 400;
    }

    /**
     * 查询游戏名称（用于中间页展示）
     */
    private function get_game_name($game_id) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $row = $this->db->fetch_first("SELECT name FROM `{$tablepre}cms_game` WHERE id = " . (int)$game_id);
        return $row && isset($row['name']) ? $row['name'] : '';
    }
}

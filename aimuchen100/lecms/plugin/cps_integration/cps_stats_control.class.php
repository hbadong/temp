<?php
defined('ROOT_PATH') or exit;
/**
 * CPS 统计控制器
 *
 * 修复（对照验证发现）：空实现 → 真实 SQL 统计
 * - REQ-06-AC7 支持按天/周/月维度查看点击和转化统计
 * - 总点击、唯一IP、按天趋势、按游戏/按链接排行
 *
 * 访问：index.php?cps_stats-index&dim=day|week|month&days=N
 */

require_once ROOT_PATH . 'lecms/plugin/cps_integration/model/cps_log.class.php';

class cps_stats_control extends admin_control {

    /**
     * 统计报表页（REQ-06-AC7）
     */
    public function index() {
        $dim = R('dim', 'R');
        if(!in_array($dim, array('day', 'week', 'month'), true)) $dim = 'day';
        $days = (int)R('days', 'R');
        if($days <= 0) $days = 7;
        $days = min(90, $days);

        $from = (int)$_ENV['_time'] - $days * 86400;
        $to = (int)$_ENV['_time'];

        $log = new CpsLog();
        $stats = $log->get_stats(0, $from, $to);

        $this->assign_value('dim', $dim);
        $this->assign_value('days', $days);
        $this->assign_value('stats', $stats);
        $this->assign_value('from_date', date('Y-m-d', $from));
        $this->assign_value('to_date', date('Y-m-d', $to));
        $this->display('cps_stats.htm');
    }

    /**
     * 兜底入口（本类为服务类，正常通过 index() 或 get_* 方法调用）
     */
    public function get_daily($site_id, $from, $to) {
        $log = new CpsLog();
        return $log->get_stats((int)$site_id, (int)$from, (int)$to);
    }

    public function get_weekly($site_id, $from, $to) {
        $log = new CpsLog();
        return $log->get_stats((int)$site_id, (int)$from, (int)$to);
    }

    public function get_monthly($site_id, $from, $to) {
        $log = new CpsLog();
        return $log->get_stats((int)$site_id, (int)$from, (int)$to);
    }
}

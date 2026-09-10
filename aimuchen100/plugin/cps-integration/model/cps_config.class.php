<?php
/**
 * CpsConfig - CPS 推广链接模型
 *
 * 提供推广链接的 CRUD + 按权重分配 + 降级逻辑。
 *
 * @author     沐尘100
 * @version    1.0.0
 * @cms_version 3.0.0
 */
class CpsConfig
{
    /** @var db_mysql 数据库实例 */
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * 获取指定游戏的最优推广链接（按权重排序）
     *
     * @param   int     $site_id  站点 ID
     * @param   int     $game_id  游戏 ID
     * @return  array|false        ['id'=>..., 'url'=>..., 'backup_url'=>..., 'weight'=>...]
     */
    public function get_best_link($site_id, $game_id) {
        $site_id = (int)$site_id;
        $game_id = (int)$game_id;
        $row = $this->db->fetch_first("
            SELECT `id`, `url`, `backup_url`, `weight`
            FROM `{$this->db->tablepre}cms_cps_config`
            WHERE `site_id` = '{$site_id}'
              AND `game_id` = '{$game_id}'
              AND `enabled` = 1
            ORDER BY `weight` DESC
            LIMIT 1
        ");
        return $row ?: false;
    }

    /**
     * 记录点击（增量 click_count）
     */
    public function increment_click($cps_id) {
        $cps_id = (int)$cps_id;
        $dateline = isset($_ENV['_time']) ? $_ENV['_time'] : time();
        $this->db->query("
            UPDATE `{$this->db->tablepre}cms_cps_config`
            SET `click_count` = `click_count` + 1, `updated_at` = '{$dateline}'
            WHERE `id` = '{$cps_id}'
        ");
    }
}

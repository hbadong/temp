<?php
defined('ROOT_PATH') || exit;

/**
 * SidebarRanking 侧栏排行榜组件
 */
class SidebarRanking
{
    public function render($params = [])
    {
        $period = isset($params['period']) ? $params['period'] : 'weekly';
        $limit = isset($params['limit']) ? (int)$params['limit'] : 5;
        $games = isset($params['games']) ? $params['games'] : [];

        $html = '<div class="sidebar-ranking"><h4>排行榜 (' . htmlspecialchars($period, ENT_QUOTES, 'UTF-8') . ')</h4><ol>';

        $i = 0;
        foreach (array_slice($games, 0, $limit) as $game) {
            $i++;
            $title = htmlspecialchars($game['title'] ?? $game['name'] ?? '未知', ENT_QUOTES, 'UTF-8');
            $html .= '<li><span class="rank">' . $i . '</span>' . $title . '</li>';
        }

        $html .= '</ol></div>';
        return $html;
    }
}

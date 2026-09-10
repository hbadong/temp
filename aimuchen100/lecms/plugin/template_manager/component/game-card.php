<?php
defined('ROOT_PATH') || exit;

/**
 * GameCard 游戏卡片组件
 */
class GameCard
{
    public function render($params = [])
    {
        $game_id = isset($params['game_id']) ? (int)$params['game_id'] : 0;
        $thumbnail = isset($params['thumbnail']) ? htmlspecialchars($params['thumbnail'], ENT_QUOTES, 'UTF-8') : '';
        $title = isset($params['title']) ? htmlspecialchars($params['title'], ENT_QUOTES, 'UTF-8') : '未知游戏';
        $rating = isset($params['rating']) ? (float)$params['rating'] : 0;

        if (!$game_id && empty($thumbnail)) {
            $thumbnail = 'placeholder.jpg';
        }

        return '<div class="game-card" data-game-id="' . $game_id . '">' .
            '<img src="' . $thumbnail . '" alt="' . $title . '" loading="lazy">' .
            '<h3>' . $title . '</h3>' .
            '<span class="rating">' . number_format($rating, 1) . '</span>' .
            '</div>';
    }
}

<?php
defined('ROOT_PATH') || exit;

/**
 * RatingStars 评分星星组件
 */
class RatingStars
{
    public function render($params = [])
    {
        $rating = isset($params['rating']) ? (float)$params['rating'] : 0;
        $rating = max(0, min(5, $rating));

        $full = floor($rating);
        $half = ($rating - $full) >= 0.5 ? 1 : 0;
        $empty = 5 - $full - $half;

        $html = '<div class="rating-stars" title="' . number_format($rating, 1) . '/5">';
        $html .= str_repeat('<span class="star full">★</span>', $full);
        $html .= $half ? '<span class="star half">½</span>' : '';
        $html .= str_repeat('<span class="star empty">☆</span>', $empty);
        $html .= '</div>';

        return $html;
    }
}

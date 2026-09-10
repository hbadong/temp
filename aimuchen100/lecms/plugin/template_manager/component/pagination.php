<?php
defined('ROOT_PATH') || exit;

/**
 * Pagination 分页器组件
 */
class Pagination
{
    public function render($params = [])
    {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $total = isset($params['total']) ? (int)$params['total'] : 1;
        $url = isset($params['url']) ? $params['url'] : '?page={page}';

        $html = '<div class="pagination">';

        if ($page > 1) {
            $html .= '<a href="' . htmlspecialchars(str_replace('{page}', $page - 1, $url), ENT_QUOTES, 'UTF-8') . '">上一页</a>';
        }

        $html .= '<span>' . $page . ' / ' . $total . '</span>';

        if ($page < $total) {
            $html .= '<a href="' . htmlspecialchars(str_replace('{page}', $page + 1, $url), ENT_QUOTES, 'UTF-8') . '">下一页</a>';
        }

        $html .= '</div>';
        return $html;
    }
}

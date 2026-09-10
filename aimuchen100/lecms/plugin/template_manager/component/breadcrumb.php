<?php
defined('ROOT_PATH') || exit;

/**
 * Breadcrumb 面包屑组件
 */
class Breadcrumb
{
    public function render($params = [])
    {
        $items = isset($params['items']) ? $params['items'] : [];

        $html = '<nav class="breadcrumb"><ol>';
        foreach ($items as $i => $item) {
            $name = htmlspecialchars($item['name'] ?? '', ENT_QUOTES, 'UTF-8');
            if (isset($item['url'])) {
                $url = htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8');
                $html .= "<li><a href=\"$url\">$name</a></li>";
            } else {
                $html .= "<li class=\"current\">$name</li>";
            }
        }
        $html .= '</ol></nav>';
        return $html;
    }
}

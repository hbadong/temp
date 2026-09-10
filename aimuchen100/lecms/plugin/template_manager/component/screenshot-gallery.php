<?php
defined('ROOT_PATH') || exit;

/**
 * ScreenshotGallery 截图画廊组件
 */
class ScreenshotGallery
{
    public function render($params = [])
    {
        $images = isset($params['images']) ? $params['images'] : [];
        if (empty($images)) {
            return '';
        }

        $html = '<div class="screenshot-gallery"><div class="carousel">';

        foreach ($images as $img) {
            $src = htmlspecialchars($img['src'] ?? $img ?? '', ENT_QUOTES, 'UTF-8');
            $alt = htmlspecialchars($img['alt'] ?? '', ENT_QUOTES, 'UTF-8');
            $html .= '<img src="' . $src . '" alt="' . $alt . '" loading="lazy" class="carousel-slide">';
        }

        $html .= '</div></div>';
        return $html;
    }
}

<?php
defined('ROOT_PATH') || exit;

/**
 * DownloadBtn 下载按钮组件
 */
class DownloadBtn
{
    public function render($params = [])
    {
        $download_url = isset($params['download_url']) ? $params['download_url'] : '/download/default.html';
        $cps_id = isset($params['cps_id']) ? (int)$params['cps_id'] : 0;
        $text = isset($params['text']) ? $params['text'] : '立即下载';

        if ($cps_id > 0 && !empty($params['cps_enabled'])) {
            return '<a href="' . htmlspecialchars($download_url, ENT_QUOTES, 'UTF-8') . '" class="btn-download" data-cps-id="' . $cps_id . '">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</a>';
        }

        return '<a href="' . htmlspecialchars($download_url, ENT_QUOTES, 'UTF-8') . '" class="btn-download">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</a>';
    }
}

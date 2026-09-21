<?php
defined('ROOT_PATH') or exit;
/**
 * 同步主站 API 前台控制器
 * 公开 JSON 同步端点：GET ?master_api-sync&type=games|articles|categories|tags&page=&page_size=&since=&token=
 * 响应 {status,data,pagination,sign}，sign 为 HMAC 出口签名（sync_client 验签用）。
 * 鉴权与限频统一走 master_api 模型；仅输出已启用内容。
 */

class master_api_control extends base_control {

    private $types = array('games', 'articles', 'categories', 'tags');

    public function sync() {
        require_once ROOT_PATH . 'lecms/plugin/master_api/model/master_api_model.class.php';
        $m = new master_api();

        // 1. Token 鉴权
        $token = trim(R('token', 'R'));
        $tok = $m->auth($token);
        if(!$tok) $this->err(401, 'invalid token');

        // 2. 限频
        if(!$m->rate_limit($tok)) $this->err(429, 'rate limit exceeded, max 30/min per token');

        // 3. 类型校验
        $type = trim(R('type', 'R'));
        if(!in_array($type, $this->types, true)) $this->err(400, "invalid type, expect " . implode('|', $this->types));

        $page = max(1, (int)R('page', 'R'));
        $page_size = max(1, min(100, (int)R('page_size', 'R')));
        $since = max(0, (int)R('since', 'R'));

        // 4. 数据导出
        try {
            switch($type) {
                case 'games':
                    list($total, $rows) = $m->export_games($page, $page_size, $since);
                    break;
                case 'articles':
                    list($total, $rows) = $m->export_articles($page, $page_size, $since);
                    break;
                case 'categories':
                    list($total, $rows) = $m->export_categories($page, $page_size);
                    break;
                case 'tags':
                    list($total, $rows) = $m->export_tags($page, $page_size);
                    break;
                default:
                    $this->err(400, 'invalid type');
            }
        } catch(Exception $e) {
            $this->err(500, 'export error: ' . $e->getMessage());
        }

        $has_more = $page * $page_size < $total;
        $pagination = array(
            'page' => $page,
            'page_size' => $page_size,
            'since' => $since,
            'total' => $total,
            'has_more' => $has_more ? 1 : 0,
        );

        // 5. 出口签名
        $sign = master_api::sign_payload($type, $page, $page_size, $since, $rows);

        $this->json_out(array(
            'status' => 1,
            'code' => 0,
            'message' => 'ok',
            'data' => $rows,
            'pagination' => $pagination,
            'sign' => $sign,
        ));
    }

    /**
     * 统一 JSON 输出
     */
    private function json_out($arr) {
        @header('Content-Type: application/json; charset=utf-8');
        @header('X-Content-Type-Options: nosniff');
        echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * 鉴权失败/异常统一出口
     */
    private function err($code, $msg) {
        $this->json_out(array('status' => 0, 'code' => (int)$code, 'message' => $msg, 'data' => null, 'pagination' => null, 'sign' => ''));
    }
}
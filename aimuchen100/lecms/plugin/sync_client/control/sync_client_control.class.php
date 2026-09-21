<?php
defined('ROOT_PATH') or exit;
/**
 * 站群数据同步客户端 前台控制器
 * - cron 端点：?sync_client-cron&key=<cron_key>  按设置执行全部启用类型同步
 * - 手动端点：?sync_client-run&key=<cron_key>&type=games  （type 可指定单一类型）
 * 返回 JSON：{status, code, message, results}
 */

class sync_client_control extends base_control {

    public function cron() {
        $this->run_sync();
    }

    public function run() {
        $this->run_sync();
    }

    private function run_sync() {
        require_once ROOT_PATH . 'lecms/plugin/sync_client/model/sync_client_model.class.php';
        $m = new sync_client();

        $site_id = (int)$m->current_site();
        $st = $m->get_settings($site_id);
        $key = trim(R('key', 'R'));
        $ckey = trim((string)$st['cron_key']);
        if($key === '' || $ckey === '' || !hash_equals($ckey, $key)) {
            $this->json_out(array('status' => 0, 'code' => 403, 'message' => 'invalid cron key', 'results' => null));
        }

        $only = trim(R('type', 'R'));
        $res = $m->sync_all($site_id, $only);
        $this->json_out(array(
            'status' => $res['ok'] ? 1 : 0,
            'code' => $res['ok'] ? 0 : 500,
            'message' => $res['message'],
            'results' => $res['results'],
        ));
    }

    private function json_out($arr) {
        @header('Content-Type: application/json; charset=utf-8');
        @header('X-Content-Type-Options: nosniff');
        echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

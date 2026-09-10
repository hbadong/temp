<?php
/**
 * 缓冲 + 批量 INSERT
 * 通过 spider_runtime::$pdo 测试 init；生产由 db() 提供
 */
class spider_logger {
    private $buf = array();
    private $cols = array('site_id','engine','ip','ip_text','user_agent','url','referer','method','response_code','response_size','duration_ms','is_blacklist_hit','is_intercepted','created_at');

    public function enqueue($rec) {
        $this->buf[] = $rec;
        $size = (int)spider_runtime_get('buffer_size', 20);
        if (count($this->buf) >= $size) $this->flush();
    }

    public function flush() {
        if (empty($this->buf)) return;
        $pdo = spider_runtime::$pdo;
        $pre = spider_runtime::$pre;
        if (!$pdo) {
            $this->buf = array();
            return;
        }
        $placeholders = '(' . implode(',', array_fill(0, count($this->cols), '?')) . ')';
        $sql = "INSERT INTO `" . $pre . "spider_visit_log` (" . implode(',', $this->cols) . ") VALUES " . implode(',', array_fill(0, count($this->buf), $placeholders));
        $flat = array();
        foreach ($this->buf as $r) {
            foreach ($this->cols as $c) $flat[] = $r[$c] ?? null;
        }
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($flat);
        } catch (Exception $e) {
            // 降级为单条
            foreach ($this->buf as $r) {
                try {
                    $flat1 = array();
                    foreach ($this->cols as $c) $flat1[] = $r[$c] ?? null;
                    $stmt = $pdo->prepare("INSERT INTO `" . $pre . "spider_visit_log` (" . implode(',', $this->cols) . ") VALUES " . $placeholders);
                    $stmt->execute($flat1);
                } catch (Exception $e2) {
                    // 单条也失败，log 但不抛
                    error_log('[spider_analytics] ' . $e2->getMessage());
                }
            }
        }
        $this->buf = array();
    }
}

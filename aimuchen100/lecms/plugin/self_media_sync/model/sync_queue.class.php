<?php
/**
 * 同步队列引擎
 */
class sync_queue {

    private $site_id;
    private $tablepre;
    private $db;

    public function __construct($site_id = 0, $db = null) {
        $this->site_id = (int)$site_id;
        $this->tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $this->db = $db;
    }

    /**
     * 添加同步任务
     * @param int $article_id 文章ID
     * @param int $account_id 账号ID
     * @param string $platform 平台
     * @return int 日志ID
     */
    public function add_task($article_id, $account_id, $platform) {
        return $this->db->insert("`{$this->tablepre}media_sync_log`", array(
            'site_id' => $this->site_id,
            'account_id' => (int)$account_id,
            'article_id' => (int)$article_id,
            'platform' => $platform,
            'status' => 0,
            'retry_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    /**
     * 获取待同步任务
     * @param int $limit 数量限制
     * @return array
     */
    public function get_pending_tasks($limit = 10) {
        $sql = "SELECT l.*, a.platform, a.access_token, a.account_name
                FROM `{$this->tablepre}media_sync_log` l
                INNER JOIN `{$this->tablepre}media_account` a ON l.account_id = a.id
                WHERE l.site_id = " . (int)$this->site_id . "
                  AND l.status IN (0, 3)
                  AND a.status = 1
                ORDER BY l.id ASC
                LIMIT " . (int)$limit;

        return $this->db->fetch_all($sql);
    }

    /**
     * 执行同步任务
     * @param array $task 任务数据
     * @return bool
     */
    public function execute_task($task) {
        $article = $this->get_article($task['article_id']);
        if (!$article) {
            $this->update_status($task['id'], 3, '文章不存在');
            return false;
        }

        $adapter = $this->get_adapter($task['platform']);
        if (!$adapter) {
            $this->update_status($task['id'], 3, '不支持的平台');
            return false;
        }

        $this->update_status($task['id'], 1);

        try {
            $result = $adapter->publish($article);

            if ($result['success']) {
                $this->update_status($task['id'], 2, '', array(
                    'platform_url' => $result['url'],
                    'synced_at' => date('Y-m-d H:i:s'),
                ));
                return true;
            } else {
                $this->update_status($task['id'], 3, $result['error']);
                return false;
            }
        } catch (Exception $e) {
            $this->update_status($task['id'], 3, $e->getMessage());
            return false;
        }
    }

    /**
     * 处理队列
     * @param int $limit 处理数量
     * @return int 成功数
     */
    public function process_queue($limit = 10) {
        $tasks = $this->get_pending_tasks($limit);
        $success = 0;

        foreach ($tasks as $task) {
            if ($this->execute_task($task)) {
                $success++;
            }
        }

        return $success;
    }

    /**
     * 重试失败任务（指数退避）
     * @param int $task_id 任务ID
     * @return bool
     */
    public function retry_task($task_id) {
        $task = $this->db->fetch_first("
            SELECT * FROM `{$this->tablepre}media_sync_log`
            WHERE id = " . (int)$task_id . " LIMIT 1
        ");

        if (!$task || $task['retry_count'] >= 3) {
            return false;
        }

        // 指数退避：2^n 秒
        $wait_seconds = pow(2, $task['retry_count']);
        // 使用 synced_at（上次同步时间）作为退避基准；若未同步过则用 created_at
        $last_attempt = !empty($task['synced_at']) ? strtotime($task['synced_at']) : strtotime($task['created_at']);
        if ($last_attempt && (time() - $last_attempt < $wait_seconds)) {
            return false;
        }

        $this->update_status($task_id, 0, '', array(
            'retry_count' => $task['retry_count'] + 1,
        ));

        return true;
    }

    /**
     * 获取文章数据
     */
    private function get_article($article_id) {
        return $this->db->fetch_first("
            SELECT * FROM `{$this->tablepre}article`
            WHERE id = " . (int)$article_id . " LIMIT 1
        ");
    }

    /**
     * 获取适配器实例
     */
    private function get_adapter($platform) {
        $map = array(
            'wechat' => 'wechat_adapter',
            'zhihu' => 'zhihu_adapter',
            'csdn' => 'csdn_adapter',
            'sohu' => 'sohu_adapter',
        );

        if (!isset($map[$platform])) {
            return null;
        }

        $account = $this->db->fetch_first("
            SELECT * FROM `{$this->tablepre}media_account`
            WHERE platform = '" . addslashes($platform) . "' AND site_id = {$this->site_id} AND status = 1 LIMIT 1
        ");

        if (!$account) {
            return null;
        }

        return new $map[$platform]($this->site_id, $account);
    }

    /**
     * 更新任务状态
     */
    private function update_status($id, $status, $error = '', $extra = array()) {
        $data = array(
            'status' => (int)$status,
            'error_message' => $error,
        );
        if ($status === 2) {
            $data['synced_at'] = date('Y-m-d H:i:s');
        }
        $data = array_merge($data, $extra);
        $set = array();
        foreach ($data as $k => $v) {
            $set[] = "`{$k}` = '" . addslashes($v) . "'";
        }
        $sql = "UPDATE `{$this->tablepre}media_sync_log` SET " . implode(', ', $set) . " WHERE id = " . (int)$id;
        $this->db->query($sql);
    }
}

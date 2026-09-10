<?php
/**
 * 文章更新任务调度器
 * 管理文章定期 AI 重写任务，支持 daily / weekly / monthly 三种频率
 *
 * 数据表: pre_article_update_task
 */
class update_scheduler extends model {

    public function __construct() {
        $this->table = 'article_update_task';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    // ==================== 查询 ====================

    /**
     * 获取待执行的任务列表
     * 条件：next_run <= NOW() 且 status = 1（启用）
     *
     * @return array 待执行任务列表
     */
    public function get_pending_tasks() {
        $where = array(
            'next_run <=' => $_ENV['_time'],
            'status'       => 1,
        );
        $order = array('id' => 1);
        return $this->find_fetch($where, $order);
    }

    // ==================== 执行 ====================

    /**
     * 调度器主入口
     * 查询待执行任务并逐一处理
     */
    public function run() {
        $tasks = $this->get_pending_tasks();
        if (empty($tasks)) {
            return;
        }

        foreach ($tasks as $task) {
            $this->execute_task($task);
        }
    }

    /**
     * 执行单个更新任务
     * 获取站点文章列表 -> 调用 AI 重写 -> 更新计数
     *
     * @param array $task 任务数据
     * @return bool 执行是否成功
     */
    public function execute_task($task) {
        $site_id     = (int)$task['site_id'];
        $category_id = (int)$task['category_id'];

        // 获取该站点/分类下的文章列表
        $articles = $this->get_site_articles($site_id, $category_id);
        if (empty($articles)) {
            $this->update_task_result($task['id'], 0, 0);
            return true;
        }

        $success_count = 0;
        $fail_count    = 0;

        foreach ($articles as $article) {
            $result = $this->rewrite_article($article, $task);
            if ($result === true) {
                $success_count++;
            } else {
                $fail_count++;
            }
        }

        // 更新成功/失败计数并计算下次执行时间
        $this->update_task_result($task['id'], $success_count, $fail_count);
        $this->update_next_run($task);

        return true;
    }

    /**
     * 获取站点文章列表
     *
     * @param int $site_id    站点 ID
     * @param int $category_id 分类 ID（0 表示全部分类）
     * @param int $limit      每次获取数量上限
     * @return array 文章列表
     */
    public function get_site_articles($site_id, $category_id = 0, $limit = 100) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id  = (int)$site_id;
        $limit    = (int)$limit;

        // 核心内容表 cms_article 无 site_id 列；db_pdo_mysql 不支持参数绑定，值直接内联（整型）
        $where = "1=1";
        if ($category_id > 0) {
            $where .= " AND cid = " . (int)$category_id;
        }

        // 优先处理未处理或较早的文章
        $sql = "SELECT a.id, a.title, d.content
                FROM `{$tablepre}cms_article` a
                LEFT JOIN `{$tablepre}cms_article_data` d ON d.id = a.id
                WHERE {$where}
                ORDER BY a.id ASC
                LIMIT {$limit}";

        return $this->db->fetch_all($sql);
    }

    /**
     * 调用 AI API 重写文章内容
     *
     * @param array $article 文章数据（包含 id, title, content）
     * @param array $task    任务数据
     * @return bool 是否成功
     */
    protected function rewrite_article($article, $task) {
        if (empty($article['content']) || empty($article['id'])) {
            return false;
        }

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id  = (int)$task['site_id'];

        // 调用 AI 接口获取重写后的内容
        $new_content = $this->call_ai_rewrite($article['content'], $article['title']);

        if ($new_content === false || empty($new_content)) {
            return false;
        }

        // 更新文章内容（正文存于 cms_article_data 分表）
        $content = addslashes($new_content);
        $article_id = (int)$article['id'];
        $sql = "UPDATE `{$tablepre}cms_article_data` SET content = '{$content}' WHERE id = {$article_id} LIMIT 1";
        $result = $this->db->exec($sql);

        return $result !== false;
    }

    /**
     * 调用 AI 接口重写内容
     *
     * @param string $content 原始内容
     * @param string $title   文章标题
     * @return string|false 重写后内容或 false
     */
    protected function call_ai_rewrite($content, $title = '') {
        // 优先使用插件 AI 生成函数（cms_ai_generate hook 提供的函数）
        if (function_exists('cms_ai_generate')) {
            $prompt = '请对以下文章进行深度改写，保持原意但大幅改变表达方式和行文结构，确保与原文有显著差异：';
            $result  = cms_ai_generate($content, array(
                'mode'    => 'rewrite',
                'prompt'  => $prompt,
                'title'   => $title,
            ));
            if (!empty($result)) {
                return $result;
            }
        }

        // 回退：使用本地内容重组（content_reorganizer）
        if (class_exists('content_reorganizer')) {
            $reorganizer = core::model('content_reorganizer');
            return $reorganizer->reorganize($content, array(
                'ai_reorganize' => true,
                'synonym_ratio' => 0.20,
            ));
        }

        return false;
    }

    // ==================== 状态更新 ====================

    /**
     * 更新任务执行结果（成功/失败计数）
     *
     * @param int $task_id      任务 ID
     * @param int $success_count 成功数
     * @param int $fail_count    失败数
     * @return bool
     */
    protected function update_task_result($task_id, $success_count, $fail_count) {
        $data = array(
            'id'           => (int)$task_id,
            'success_count' => (int)$success_count,
            'fail_count'    => (int)$fail_count,
            'updated_at'    => $_ENV['_time'],
        );
        return $this->update($data);
    }

    /**
     * 更新下次执行时间
     * daily  → NOW() + 1 DAY
     * weekly → NOW() + 7 DAYS
     * monthly → NOW() + 1 MONTH
     *
     * @param array $task 任务数据
     * @return bool
     */
    public function update_next_run($task) {
        $frequency = isset($task['frequency']) ? $task['frequency'] : 'daily';

        // next_run 列为 INT UNSIGNED 时间戳，与 get_pending_tasks 的整型比较保持一致
        switch ($frequency) {
            case 'weekly':
                $next_run = strtotime('+7 days');
                break;

            case 'monthly':
                $next_run = strtotime('+1 month');
                break;

            case 'daily':
            default:
                $next_run = strtotime('+1 day');
                break;
        }

        $data = array(
            'id'        => (int)$task['id'],
            'next_run'  => (int)$next_run,
            'updated_at' => $_ENV['_time'],
        );

        return $this->update($data);
    }
}

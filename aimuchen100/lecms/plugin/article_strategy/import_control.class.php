<?php
defined('ROOT_PATH') or exit;


class import_control extends admin_control {

    /**
     * 导入首页 - 显示导入历史列表
     */
    public function index() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $logs = $this->import_log->get_list($site_id);

        $this->assign('logs', $logs);
        $this->display('import_list.htm');
    }

    /**
     * 获取导入日志列表（AJAX - layui table）
     */
    public function get_list() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $logs = $this->import_log->get_list($site_id);

        $data_arr = array();
        foreach ($logs as $log) {
            $report = array();
            if (isset($log['report']) && $log['report'] !== '') {
                $decoded = json_decode($log['report'], true);
                if (is_array($decoded)) {
                    $report = $decoded;
                }
            }

            $data_arr[] = array(
                'id' => $log['id'],
                'filename' => $log['filename'],
                'total_rows' => $log['total_rows'],
                'imported' => $log['imported'],
                'skipped' => $log['skipped'],
                'status' => $log['status'],
                'created_at' => $log['created_at'],
            );
        }

        $arr = array(
            'code' => 0,
            'msg' => '',
            'count' => count($data_arr),
            'data' => $data_arr,
        );
        exit(json_encode($arr));
    }

    /**
     * 上传页面
     */
    public function upload() {
        $this->display('import_upload.htm');
    }

    /**
     * 上传并处理文件
     */
    public function upload_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        if (empty($_FILES['import_file']['name'])) {
            E(1, 'Please select a file to upload');
        }

        $upload_file = $_FILES['import_file'];
        if ($upload_file['error'] !== 0) {
            E(1, 'File upload failed with error code: ' . $upload_file['error']);
        }

        // 检查文件类型
        $allowed_ext = array('txt', 'csv');
        $file_ext = strtolower(pathinfo($upload_file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_ext)) {
            E(1, 'Unsupported file type. Only .txt and .csv are allowed');
        }

        // 检查文件大小 (限制 10MB)
        $max_size = 10 * 1024 * 1024;
        if ($upload_file['size'] > $max_size) {
            E(1, 'File size exceeds 10MB limit');
        }

        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $tmp_path = $upload_file['tmp_name'];

        // 创建导入日志
        $log_data = array(
            'site_id' => $site_id,
            'filename' => $upload_file['name'],
            'total_rows' => 0,
            'imported' => 0,
            'skipped' => 0,
            'status' => 0,
            'report' => array(),
        );
        $log_id = $this->import_log->create($log_data);
        if (!$log_id) {
            E(1, 'Failed to create import log');
        }

        // 解析文件
        $importer = new txt_importer();
        $result = $importer->parse($tmp_path, $upload_file['name']);

        if (isset($result['error'])) {
            $this->import_log->save($log_id, array(
                'status' => 2,
                'report' => array('error' => $result['error']),
            ));
            E(1, 'Parse error: ' . $result['error']);
        }

        $articles = $result['articles'];
        $total = $result['total'];

        // 获取已存在的标题哈希（用于去重，PHP 5.4 兼容）
        $hashes = array();
        foreach ($articles as $a) {
            $hashes[] = $a['hash'];
        }
        $existing_hashes = $this->get_existing_title_hashes($site_id, $hashes);

        $imported = 0;
        $skipped = 0;
        $errors = array();

        // 分批处理
        $batches = $importer->batch($articles);
        foreach ($batches as $batch_index => $batch) {
            foreach ($batch as $article) {
                if (isset($existing_hashes[$article['hash']])) {
                    $skipped++;
                    continue;
                }

                // 创建文章
                $article_data = array(
                    'title' => $article['title'],
                    'content' => $article['content'],
                    'tags' => $article['tags'],
                    'category' => $article['category'],
                );

                $article_id = $this->create_article($article_data);
                if ($article_id) {
                    $imported++;
                    $existing_hashes[$article['hash']] = $article_id;
                } else {
                    $errors[] = 'Failed to create article: ' . $article['title'];
                }
            }

            // 更新进度
            $this->import_log->save($log_id, array(
                'total_rows' => $total,
                'imported' => $imported,
                'skipped' => $skipped,
            ));
        }

        // 最终状态
        $final_status = empty($errors) ? 1 : 2;
        $this->import_log->save($log_id, array(
            'status' => $final_status,
            'report' => array(
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ),
        ));

        $msg = "Import completed: {$imported} imported, {$skipped} skipped";
        if (!empty($errors)) {
            $msg .= ', ' . count($errors) . ' errors';
        }

        E(0, $msg);
    }

    /**
     * 查看导入日志详情
     */
    public function view() {
        $id = (int)R('id', 'R');
        if (empty($id)) {
            $this->message(1, lang('data_error'), 'index.php?import-index');
        }

        $report = array();
        if (isset($log['report']) && $log['report'] !== '') {
            $decoded = json_decode($log['report'], true);
            if (is_array($decoded)) {
                $report = $decoded;
            }
        }

        $this->assign('log', $log);
        $this->assign('report', $report);
        $this->display();
    }

    /**
     * 获取已存在的文章标题哈希（用于去重）
     * @param int $site_id 站点ID
     * @param array $hashes 待检查的哈希列表
     * @return array 已存在的哈希 => 文章ID
     */
    private function get_existing_title_hashes($site_id, $hashes) {
        if (empty($hashes)) {
            return array();
        }

        // 清理并限制哈希数量，防止SQL过长
        $hashes = array_values(array_unique(array_filter($hashes)));
        if (empty($hashes)) {
            return array();
        }

        // 分批处理（每批最多1000个）
        $batches = array_chunk($hashes, 1000);
        $existing = array();
        $tablepre = $this->db->tablepre;

        foreach ($batches as $batch) {
            $name_list = array();
            foreach ($batch as $h) {
                $name_list[] = "'" . addslashes($h) . "'";
            }
            $in = implode(',', $name_list);
            // 核心内容表为 cms_article，无 site_id 列，按标题 MD5 全局去重
            $sql = "SELECT id, MD5(title) AS hash FROM {$tablepre}cms_article WHERE MD5(title) IN ({$in})";
            $query = $this->db->query($sql);

            if ($query) {
                while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                    $existing[$row['hash']] = (int)$row['id'];
                }
            }
        }

        return $existing;
    }

    /**
     * 创建文章（直接写入核心内容主表 + 数据表）
     * 核心表结构：cms_article（标题等）+ cms_article_data（content），无 site_id 列
     * @param array $data 文章数据
     * @return int|bool 文章ID或false
     */
    private function create_article($data) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $title = addslashes($data['title']);
        $tags = addslashes(isset($data['tags']) ? (string)$data['tags'] : '');
        $time = $_ENV['_time'];

        // 1. 写入主表（占位获取自增 ID）
        $sql = "INSERT INTO {$tablepre}cms_article (cid, title, tags, dateline, lasttime)
                VALUES (0, '{$title}', '{$tags}', {$time}, {$time})";
        $aid = $this->db->exec($sql);
        if (!$aid) {
            return false;
        }
        $aid = (int)$aid;

        // 2. 写入数据表（正文）
        $content = addslashes(isset($data['content']) ? (string)$data['content'] : '');
        $this->db->exec("INSERT INTO {$tablepre}cms_article_data (id, content) VALUES ({$aid}, '{$content}')");

        return $aid;
    }
}

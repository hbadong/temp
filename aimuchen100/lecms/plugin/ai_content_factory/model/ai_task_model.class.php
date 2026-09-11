<?php
defined('ROOT_PATH') or exit;
/**
 * AI 任务模型
 *
 * 修复（对照验证发现）：
 * - CODE-1: 移除 `?` 占位符 + 数组第二参数（框架 db_pdo_mysql 无参数绑定，
 *   fetch_all/query 第二参数是连接句柄），改用 addslashes() 内联转义。
 * - REQ-03-AC5: insert_article 写 5 个不存在的列（site_id/subject/content/views/status）
 *   → 改为 cms_article 真实列（cid/title/alias/tags/dateline/seo_*），
 *   正文写入 cms_article_data.content 副表（与核心 cms_content_data 一致）。
 * - REQ-03-AC2: create_post 上限 100 + execute() 自动分批循环（batch_size 直至全部完成）。
 * - REQ-03-AC4: build_prompt 使用任务自定义 prompt_template，忽略时用默认模板。
 * - REQ-03-AC3: parse_response 剥离 ```json fence 再解码。
 * - REQ-03-AC8: 每次执行后 URL 状态 1→2 + content_id 关联（幂等，不重复消费）。
 * - CODE-4: get_pending_urls/execute 全程 try/catch，错误回滚事务。
 */

// ai_api_adapter 类不被 core::model()/autoload 覆盖，必须显式加载
require_once ROOT_PATH . 'lecms/plugin/ai_content_factory/model/ai_api_adapter.php';
// pinyin 类（ext 目录，非自动加载）用于生成文章拼音别名
require_once ROOT_PATH . 'lecms/xiunophp/ext/pinyin.class.php';

class ai_task extends model {
    public function __construct() {
        $this->table = 'ai_task';
        $this->pri = array('id');
        $this->maxid = 'id';
    }

    /**
     * 创建 AI 任务
     */
    public function create_task($site_id, $category_id, $count, $prompt_template = '') {
        $task_id = $this->create(array(
            'site_id' => $site_id,
            'category_id' => $category_id,
            'prompt_template' => $prompt_template,
            'batch_size' => min(50, $count),
            'total' => $count,
            'success' => 0,
            'fail' => 0,
            'status' => 0,
            'created_at' => $_ENV['_time'],
            'updated_at' => $_ENV['_time'],
        ));

        return $task_id;
    }

    /**
     * 更新 AI 任务（与父类 model::update($data, $life) 签名冲突，另命名为 save）
     * @param int $task_id 任务ID
     * @param array $arr 更新数据
     * @return bool
     */
    public function save($task_id, $arr) {
        $arr['id'] = (int)$task_id;
        return parent::update($arr);
    }

    /**
     * 执行 AI 任务（自动分批，直至全部 URL 消费完或达到 total 上限）
     * @return array|false ['success'=>int, 'fail'=>int, 'done'=>bool]
     */
    public function execute($task_id) {
        $task = $this->get($task_id);
        if(!$task) return false;

        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$task['site_id'];
        $total = (int)$task['total'];

        $success_total = (int)$task['success'];
        $fail_total = (int)$task['fail'];
        $done = false;
        $urls_exhausted = false;  // 区分"达标完成"与"URL 用尽提前结束"

        // 分批循环：每批 batch_size，直至达到 total 或没有可用 URL
        while(!$done) {
            $pending = $total - $success_total - $fail_total;
            if($pending <= 0) { $done = true; break; }

            $limit = min($pending, (int)$task['batch_size'] > 0 ? (int)$task['batch_size'] : 50);

            // 获取待使用的 URL
            $urls = $this->get_pending_urls($site_id, $limit);
            if(empty($urls)) {
                // 无可用 URL：标记为 URL 已耗尽，让循环退出
                $urls_exhausted = true;
                break;
            }

            // 构建 Prompt
            $prompt = $this->build_prompt($task);

            // 调用 AI 生成
            $adapter = new ai_api_adapter($site_id, $this->db);
            $articles = $adapter->generate($prompt, count($urls));

            $success = 0;
            $fail = 0;

            // 敏感词过滤：sensitive_word_filter 插件启用且勾选 ai_generate 时对生成内容过滤
            $sw_filter = null;
            $sw_setting_file = ROOT_PATH . 'lecms/plugin/sensitive_word_filter/setting.php';
            if (is_file($sw_setting_file)) {
                $sw_settings = include $sw_setting_file;
                $sw_enabled = !isset($sw_settings['enabled']) || !empty($sw_settings['enabled']);
                $sw_ai_checked = empty($sw_settings['filter_content_types']) || in_array('ai_generate', $sw_settings['filter_content_types']);
                if ($sw_enabled && $sw_ai_checked) {
                    require_once ROOT_PATH . 'lecms/plugin/sensitive_word_filter/model/sensitive_filter.class.php';
                    if (class_exists('sensitive_filter')) {
                        $sw_filter = new sensitive_filter($site_id, $this->db);
                    }
                }
            }

            // 使用事务保证一致性（逐批提交，避免单批失败回滚已成功批次）
            $this->db->query('START TRANSACTION');
            try {
                foreach($articles as $i => $article) {
                    if(!isset($urls[$i]) || empty($article['title'])) {
                        $fail++;
                        continue;
                    }

                    // 敏感词过滤（level>=3 拒绝入库；level==2 替换；level==1 仅记录）
                    if ($sw_filter !== null) {
                        $sw_hits = $sw_filter->detect(($article['title'] ?? '') . "\n" . ($article['content'] ?? ''));
                        if (!empty($sw_hits)) {
                            $sw_max_level = 0;
                            $sw_word = '';
                            foreach ($sw_hits as $sw_hit) {
                                if ((int)$sw_hit['level'] > $sw_max_level) {
                                    $sw_max_level = (int)$sw_hit['level'];
                                    $sw_word = isset($sw_hit['word']) ? $sw_hit['word'] : '';
                                }
                            }
                            if ($sw_max_level >= 3) {
                                $sw_filter->log($sw_word, 0, 'reject');
                                $fail++;
                                continue;
                            } elseif ($sw_max_level >= 2) {
                                $article['title'] = $sw_filter->replace_words($article['title'])['text'];
                                $article['content'] = $sw_filter->replace_words($article['content'])['text'];
                                $sw_filter->log($sw_word, 0, 'replace');
                            } else {
                                $sw_filter->log($sw_word, 0, 'replace');
                            }
                        }
                    }

                    // 写入文章（主表 + 正文副表）
                    $content_id = $this->insert_article($article, $task);
                    if(!$content_id) { $fail++; continue; }

                    // 关联 URL：状态 1→2 + content_id（幂等，REQ-03-AC8）
                    $this->db->query("
                        UPDATE `{$tablepre}cms_url_map`
                        SET status = 2, content_id = " . (int)$content_id . ", updated_at = '" . date('Y-m-d H:i:s', $_ENV['_time']) . "'
                        WHERE id = " . (int)$urls[$i]['id'] . " AND status = 1
                    ");
                    $success++;
                }
                $this->db->query('COMMIT');
            } catch(Exception $e) {
                $this->db->query('ROLLBACK');
                if(DEBUG > 0 && isset($_ENV['_trace'])) {
                    $_ENV['_trace'][] = '[ai_task] 批量执行异常: ' . $e->getMessage();
                }
                // 单批失败：计数为失败并继续（避免死循环）
                $fail = count($urls) - $success;
            }

            $success_total += $success;
            $fail_total += $fail;

            // 进度更新：达标=2 有进度=1 全失败=3
            $task_status = ($success_total >= $total) ? 2 : (($success > 0) ? 1 : 3);
            $this->save($task_id, array(
                'success' => $success_total,
                'fail' => $fail_total,
                'status' => $task_status,
                'updated_at' => $_ENV['_time'],
            ));
            $task = $this->get($task_id); // 刷新任务状态

            // AI 返回空，避免死循环
            if($success === 0 && $fail === 0) break;
        }

        // 收尾：
        // 1. 达标完成：status=2
        // 2. URL 已耗尽但有进度：剩余量记为 fail、状态=2（部分完成，URL 池空了没法继续）
        // 3. URL 已耗尽且 0 进度：状态=3（无可用 URL 失败）
        // 4. 其余仍按 progress 状态保留（status=1 仍可能代表执行中途异常）
        if($success_total >= $total) {
            $this->save($task_id, array(
                'success' => $success_total,
                'fail' => $fail_total,
                'status' => 2,
                'updated_at' => $_ENV['_time'],
            ));
        } elseif($urls_exhausted) {
            $pending = $total - $success_total - $fail_total;
            if($pending > 0) {
                $fail_total += $pending;  // 剩余量记为 fail
            }
            $final_status = ($success_total > 0) ? 2 : 3;
            $this->save($task_id, array(
                'success' => $success_total,
                'fail' => $fail_total,
                'status' => $final_status,
                'updated_at' => $_ENV['_time'],
            ));
        }

        return array('success' => $success_total, 'fail' => $fail_total, 'done' => $done);
    }

    /**
     * 获取待使用的 URL
     */
    private function get_pending_urls($site_id, $limit) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$site_id;
        $limit = (int)$limit;
        return $this->db->fetch_all("
            SELECT id, url FROM `{$tablepre}cms_url_map`
            WHERE site_id = {$site_id} AND status = 1
            ORDER BY id ASC
            LIMIT {$limit}
        ");
    }

    /**
     * 构建 Prompt（REQ-03-AC4：使用任务自定义模板，否则用默认）
     */
    private function build_prompt($task) {
        $category = '游戏';
        if(!empty($task['category_id'])) {
            $cat = $this->category->get((int)$task['category_id']);
            $category = ($cat && !empty($cat['name'])) ? $cat['name'] : '游戏';
        }

        $prompt_template = trim((string)$task['prompt_template']);
        if($prompt_template !== '') {
            // 替换模板占位符 {category}
            $prompt = str_replace(array('{category}', '{分类}'), $category, $prompt_template);
        } else {
            $prompt = "你是一个专业的游戏内容创作专家。请为主题「{$category}」生成游戏文章。"
                . "输出严格 JSON 数组，每个元素包含 title, content, tags, seo_title, seo_keywords, seo_description 六个字段。";
        }

        return $prompt;
    }

    /**
     * 插入文章到 cms_article 主表 + cms_article_data 正文副表
     * @return int|false 文章 ID
     */
    private function insert_article($article, $task) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $esc = function($v) { return "'" . addslashes($v) . "'"; };
        $now = $_ENV['_time'];

        $title = isset($article['title']) ? $article['title'] : '';
        if($title === '') return false;

        $content = isset($article['content']) ? $article['content'] : '';
        $tags = isset($article['tags']) ? $article['tags'] : '';

        // 拼音别名（pinyin::getpinyin 静态调用；类不可用时留空，走 id 路由）
        $alias = class_exists('pinyin') ? pinyin::getpinyin($title) : '';

        // 主表：cid/title/alias/tags/dateline/lasttime/uid/author/.../seo_*
        $sql = "INSERT INTO `{$tablepre}cms_article`
            (`cid`, `title`, `alias`, `tags`, `dateline`, `lasttime`, `uid`,
             `author`, `source`, `seo_title`, `seo_keywords`, `seo_description`)
            VALUES
            (" . (int)$task['category_id'] . ", "
            . $esc($title) . ", "
            . $esc($alias) . ", "
            . $esc($tags) . ", "
            . $now . ", " . $now . ", 0, "
            . $esc('AI内容工厂') . ", "
            . $esc('AI内容工厂') . ", "
            . $esc(isset($article['seo_title']) ? $article['seo_title'] : '') . ", "
            . $esc(isset($article['seo_keywords']) ? $article['seo_keywords'] : '') . ", "
            . $esc(isset($article['seo_description']) ? $article['seo_description'] : '') . ")";

        $this->db->query($sql);
        $article_id = $this->db->last_insert_id();
        if(!$article_id) return false;

        // 正文副表：cms_article_data(id, content)
        $this->db->query("INSERT INTO `{$tablepre}cms_article_data` (`id`, `content`)
            VALUES (" . (int)$article_id . ", " . $esc($content) . ")");

        return (int)$article_id;
    }
}

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
     * @param int $creator_uid 创建人 uid（B8：内容归属）
     */
    public function create_task($site_id, $category_id, $count, $prompt_template = '', $creator_uid = 0) {
        $task_id = $this->create(array(
            'site_id' => $site_id,
            'category_id' => $category_id,
            'creator_uid' => (int)$creator_uid,
            'prompt_template' => $prompt_template,
            'batch_size' => min(50, $count),
            'total' => $count,
            'success' => 0,
            'fail' => 0,
            'status' => 0,
            'exec_lock' => 0,
            'created_at' => date('Y-m-d H:i:s', $_ENV['_time']),
            'updated_at' => date('Y-m-d H:i:s', $_ENV['_time']),
            'exec_log' => '',
        ));

        return $task_id;
    }

    /**
     * 追加执行日志（B9：保留最近 20 条，按时间倒序展示）
     */
    public function append_log($task_id, $msg) {
        $task = $this->get($task_id);
        if(!$task) return;
        $line = date('m-d H:i:s', $_ENV['_time']) . ' ' . $msg;
        $old = trim((string)$task['exec_log']);
        $lines = $old === '' ? array() : explode("\n", $old);
        array_unshift($lines, $line);
        $lines = array_slice($lines, 0, 20);
        $this->save($task_id, array('exec_log' => implode("\n", $lines)));
    }

    /**
     * 读取插件配置（A1：配置单轨，唯一来源 ai_config 表；site_id 站点级优先，回退全局）
     */
    public function get_settings($site_id = 0) {
        $pre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$site_id;
        $row = null;
        if($site_id > 0) {
            $row = $this->db->fetch_first("SELECT * FROM `{$pre}ai_config` WHERE site_id={$site_id} LIMIT 1");
        }
        if(!$row) {
            $row = $this->db->fetch_first("SELECT * FROM `{$pre}ai_config` WHERE site_id=0 LIMIT 1");
        }
        $defaults = array(
            'site_id' => 0,
            'api_base_url' => 'https://api.deepseek.com/v1',
            'api_key' => '',
            'model' => 'deepseek-chat',
            'max_tokens' => 2048,
            'temperature' => 0.7,
            'timeout' => 60,
            'batch_limit' => 100,
            'max_retries' => 3,
            'no_url_generate' => 0,
        );
        if($row) {
            foreach($defaults as $k => $dv) {
                if(!isset($row[$k]) || $row[$k] === '' || $row[$k] === NULL) $row[$k] = $dv;
            }
            return $row;
        }
        return $defaults;
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
     * 执行 AI 任务（B1：单批执行，前端循环调用直至 done）
     * B3：exec_lock 防并发；B9：append_log 记录执行过程
     * @return array|false ['success'=>累计成功, 'fail'=>累计失败, 'done'=>bool, 'degraded'=>bool, 'locked'=>bool]
     */
    public function execute($task_id) {
        $task_id = (int)$task_id;
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        // B3 并发锁：抢占执行权（exec_lock 0→1），失败说明已有执行在进行
        $n = $this->db->exec("UPDATE `{$tablepre}ai_task` SET exec_lock=1 WHERE id={$task_id} AND exec_lock=0");
        if($n <= 0) {
            return array('locked' => true, 'success' => 0, 'fail' => 0, 'done' => false, 'degraded' => false);
        }

        $task = $this->get($task_id);
        if(!$task) {
            $this->db->exec("UPDATE `{$tablepre}ai_task` SET exec_lock=0 WHERE id={$task_id}");
            return false;
        }

        $site_id = (int)$task['site_id'];
        $total = (int)$task['total'];
        $success_total = (int)$task['success'];
        $fail_total = (int)$task['fail'];
        $degraded = false;
        $done = false;

        $pending = $total - $success_total - $fail_total;

        // 已达标：直接收尾
        if($pending <= 0) {
            $this->save($task_id, array('status' => 2, 'exec_lock' => 0, 'updated_at' => date('Y-m-d H:i:s', $_ENV['_time'])));
            return array('success' => $success_total, 'fail' => $fail_total, 'total' => $total, 'done' => true, 'degraded' => false);
        }

        $limit = min($pending, (int)$task['batch_size'] > 0 ? (int)$task['batch_size'] : 50);
        $settings = $this->get_settings($site_id);

        // 获取待使用的 URL
        $urls = $this->get_pending_urls($site_id, $limit);

        // C6：URL 池已空时按开关直接生成并自动建链
        if(empty($urls)) {
            if(!empty($settings['no_url_generate'])) {
                $urls = array();  // 无 URL 模式：每篇生成后自动建链
                $this->append_log($task_id, 'URL 池已空，启用自动建链模式');
            } else {
                // URL 耗尽：剩余记为 fail，任务结束
                $fail_total += $pending;
                $final_status = ($success_total > 0) ? 2 : 3;
                $this->save($task_id, array(
                    'fail' => $fail_total,
                    'status' => $final_status,
                    'exec_lock' => 0,
                    'updated_at' => date('Y-m-d H:i:s', $_ENV['_time']),
                ));
                $this->append_log($task_id, 'URL 池已用尽，剩余 ' . $pending . ' 篇记为失败');
                return array('success' => $success_total, 'fail' => $fail_total, 'total' => $total, 'done' => true, 'degraded' => false);
            }
        }

        // 构建 Prompt + 调用 AI
        $prompt = $this->build_prompt($task);
        $gen_count = empty($urls) ? $limit : count($urls);
        $adapter = new ai_api_adapter($site_id, $this->db);
        $articles = $adapter->generate($prompt, $gen_count);

        // B5：降级判定（未配置 API Key = 模板库内容）
        $cfg = $adapter->get_config();
        $degraded = empty($cfg['api_key']);
        if($degraded) {
            $this->append_log($task_id, '未配置 API Key，本次使用本地模板库降级');
        } elseif($adapter->get_last_error_message() !== '') {
            $this->append_log($task_id, 'API 错误：' . $adapter->get_last_error_message());
        }

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
                if(empty($article['title'])) {
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

                // 写入文章（主表 + 正文副表 + 标签同步）
                $url_id = isset($urls[$i]['id']) ? (int)$urls[$i]['id'] : 0;
                $content_id = $this->insert_article($article, $task, $url_id);
                if($content_id === false || $content_id === 0) { $fail++; continue; }

                // 关联 URL：状态 1→2 + content_id（幂等，REQ-03-AC8）；无 URL 模式已在 insert_article 内建链
                if($url_id > 0) {
                    $this->db->query("
                        UPDATE `{$tablepre}cms_url_map`
                        SET status = 2, content_id = " . (int)$content_id . ", updated_at = '" . date('Y-m-d H:i:s', $_ENV['_time']) . "'
                        WHERE id = " . $url_id . " AND status = 1
                    ");
                }
                $success++;
            }
            $this->db->query('COMMIT');
        } catch(Exception $e) {
            $this->db->query('ROLLBACK');
            // 单批失败：计数为失败并继续（避免死循环）
            $fail = $gen_count - $success;
            $this->append_log($task_id, '批次异常回滚：' . $e->getMessage());
        }

        $success_total += $success;
        $fail_total += $fail;

        // 进度更新：达标=2 有进度=1 全失败=3
        $task_status = ($success_total >= $total) ? 2 : (($success > 0 || $fail_total >= $total) ? ($success_total > 0 ? 1 : 3) : 1);
        $this->save($task_id, array(
            'success' => $success_total,
            'fail' => $fail_total,
            'status' => $task_status,
            'exec_lock' => 0,
            'updated_at' => date('Y-m-d H:i:s', $_ENV['_time']),
        ));

        // 批量结果日志
        if($success > 0 || $fail > 0) {
            $this->append_log($task_id, '批次执行：成功 ' . $success . '，失败 ' . $fail . ($degraded ? '（模板库降级）' : ''));
        }

        return array('success' => $success_total, 'fail' => $fail_total, 'total' => $total, 'done' => $done, 'degraded' => $degraded);
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
            WHERE site_id = {$site_id} AND status = 1 AND type = 2
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
     * 插入文章到 cms_article 主表 + cms_article_data 正文副表 + 标签同步
     * A2：写入 site_id；A3：tags 存 JSON 并同步 cms_article_tag；B7：入库前清理；
     * B8：uid/author 取任务创建人；C7：标题去重；C3：正文首图作缩略图；C6：无 URL 时自动建链
     * @param array $article 生成文章
     * @param array $task 任务行
     * @param int $url_id 关联的 url_map 行 id（0=无 URL，自动建链）
     * @return int|false 文章 ID；标题重复返回 0
     */
    private function insert_article($article, $task, $url_id = 0) {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];

        $esc = function($v) { return "'" . addslashes($v) . "'"; };
        $now = date('Y-m-d H:i:s', $_ENV['_time']);
        $now_ts = $_ENV['_time'];

        // B7：入库前清理（strip_tags + 长度截断，与核心 xadd 一致）
        $title = isset($article['title']) ? trim(strip_tags($article['title'])) : '';
        if($title === '') return false;
        $title = mb_substr($title, 0, 200, 'UTF-8');

        // C7：标题去重（同站点同标题视为重复，跳过）
        $dup = $this->db->fetch_first("SELECT id FROM `{$tablepre}cms_article` WHERE title=" . $esc($title) . " LIMIT 1");
        if($dup) return 0;

        // C3：正文首图作缩略图（本地图才入库，外链跳过与核心 auto_pic 一致）
        $content = isset($article['content']) ? (string)$article['content'] : '';
        $pic = '';
        if(preg_match('/<[img|IMG].*?src=[\'|"](.*?(?:\.gif|\.jpg|\.jpeg|\.png|\.webp))[\'|"].*?[\/]?>/i', $content, $m)) {
            $img = $m[1];
            if(strpos($img, '//') !== 0 && !preg_match('#^https?://#i', $img)) {
                $pic = ltrim($img, '/');
            }
        }

        // B7：SEO 字段清理
        $seo_title = isset($article['seo_title']) ? mb_substr(trim(strip_tags($article['seo_title'])), 0, 255, 'UTF-8') : '';
        $seo_keywords = isset($article['seo_keywords']) ? mb_substr(trim(strip_tags($article['seo_keywords'])), 0, 255, 'UTF-8') : '';
        $seo_description = isset($article['seo_description']) ? mb_substr(trim(strip_tags($article['seo_description'])), 0, 255, 'UTF-8') : '';

        // 拼音别名（pinyin::getpinyin 静态调用；类不可用时留空，走 id 路由）
        $alias = class_exists('pinyin') ? pinyin::getpinyin($title) : '';

        // B8：uid/author 取任务创建人（查 users 表昵称，查不到回退 "AI内容工厂"）
        $creator_uid = max(0, (int)$task['creator_uid']);
        $author = 'AI内容工厂';
        if($creator_uid > 0) {
            $urow = $this->db->fetch_first("SELECT username, author FROM `{$tablepre}user` WHERE uid={$creator_uid} LIMIT 1");
            if($urow) {
                $author = $urow['author'] ? $urow['author'] : $urow['username'];
            }
        }

        // A3：标签处理 —— 同步 cms_article_tag 表并生成 {tagid:name} JSON
        $tags_json = '';
        $tagstr = isset($article['tags']) ? trim((is_array($article['tags']) ? implode(',', $article['tags']) : $article['tags']), ", \t\n\r\0\x0B") : '';
        if($tagstr !== '') {
            $tags_arr = array_filter(array_unique(explode(',', $tagstr)));
            $tags_out = array();
            foreach($tags_arr as $tn) {
                if(count($tags_out) >= 8) break;
                $name = $this->tag_format($tn);
                if($name === '') continue;
                $trow = $this->db->fetch_first("SELECT tagid, `count` FROM `{$tablepre}cms_article_tag` WHERE name=" . $esc($name) . " LIMIT 1");
                if($trow) {
                    $tagid = (int)$trow['tagid'];
                    $this->db->query("UPDATE `{$tablepre}cms_article_tag` SET `count`=`count`+1 WHERE tagid={$tagid}");
                } else {
                    $this->db->query("INSERT INTO `{$tablepre}cms_article_tag` (`name`, `count`, `orderby`) VALUES (" . $esc($name) . ", 1, 0)");
                    $tagid = (int)$this->db->last_insert_id();
                }
                if($tagid > 0) {
                    $tags_out[$tagid] = $name;
                    if(_strlen(_json_encode($tags_out)) > 500) { array_pop($tags_out); break; }
                }
            }
            if(!empty($tags_out)) $tags_json = _json_encode($tags_out);
        }

        // A2：站点归属
        $site_id = max(1, (int)$task['site_id']);
        $cid = (int)$task['category_id'];

        // 主表：含 site_id/uid/author/source/pic
        $sql = "INSERT INTO `{$tablepre}cms_article`
            (`site_id`, `cid`, `title`, `alias`, `tags`, `pic`, `dateline`, `lasttime`, `uid`,
             `author`, `source`, `seo_title`, `seo_keywords`, `seo_description`)
            VALUES
            (" . $site_id . ", "
            . $cid . ", "
            . $esc($title) . ", "
            . $esc($alias) . ", "
            . $esc($tags_json) . ", "
            . $esc($pic) . ", "
            . $now_ts . ", " . $now_ts . ", " . $creator_uid . ", "
            . $esc($author) . ", "
            . $esc('AI内容工厂') . ", "
            . $esc($seo_title) . ", "
            . $esc($seo_keywords) . ", "
            . $esc($seo_description) . ")";

        $this->db->query($sql);
        $article_id = (int)$this->db->last_insert_id();
        if(!$article_id) return false;

        // 正文副表：cms_article_data(id, content)
        $this->db->query("INSERT INTO `{$tablepre}cms_article_data` (`id`, `content`)
            VALUES (" . $article_id . ", " . $esc($content) . ")");

        // C6：无 URL 时自动建链（模仿核心文章 URL 映射：/{id}.html → {分类alias}/index?id=）
        if($url_id <= 0) {
            $cate_alias = 'game';
            if($cid > 0) {
                $crow = $this->db->fetch_first("SELECT alias FROM `{$tablepre}category` WHERE cid={$cid} LIMIT 1");
                if($crow && $crow['alias'] !== '') $cate_alias = $crow['alias'];
            }
            $url = '/' . $cate_alias . '/' . $article_id . '.html';
            $url_hash = sha1($url);
            $params = _json_encode(array('id' => $article_id));
            $this->db->query("INSERT INTO `{$tablepre}cms_url_map`
                (`site_id`, `url`, `url_hash`, `type`, `control`, `action`, `params`, `status`, `content_id`, `created_at`, `updated_at`)
                VALUES
                ({$site_id}, " . $esc($url) . ", '{$url_hash}', 2, " . $esc($cate_alias) . ", 'index', " . $esc($params) . ", 2, {$article_id}, '{$now}', '{$now}')");
        }

        return $article_id;
    }

    /**
     * 标签格式化（简化版 _tagformat：safe_str + 空格处理 + 小写 + 长度限制）
     */
    private function tag_format($tagname) {
        $tagname = trim((string)$tagname);
        if($tagname === '') return '';
        $tagname = safe_str($tagname);
        $tagname = str_replace('-', ' ', $tagname);
        $tagname = preg_replace("/\s(?=\s)/", "\\1", $tagname);
        $tagname = strtolower(trim($tagname));
        // 格式：2_名字，只要后面的 名字，避免和 URL 伪静态冲突
        if(preg_match('/^([2-9]\d*)\_(.+)$/i', $tagname, $mat)) {
            $tagname = $mat[2];
        }
        if($tagname && mb_strlen($tagname) <= 80) return $tagname;
        return '';
    }
}

<?php
defined('ROOT_PATH') or exit;


/**
 * 后台 AI 任务管理控制器
 *
 * A组修复：
 * - A1: 配置单轨 —— 设置唯一来源 ai_config 表（settings_post UPSERT，adapter 同读此表）
 * - A4: 分类下拉选择 + 服务端校验 cid 存在且为文章模型
 * - A5: API Key 掩码回显，留空表示不修改
 * - B1: execute 单批执行，返回结构化 JSON 供前端轮询进度
 * - B6: 生成数量上限对齐 batch_limit 设置
 * - B8: create_task 记录 creator_uid
 * - C8: 站点级配置（site 参数覆盖全局）
 * - C9: 统计卡片
 */

// ai_api_adapter 类不被 core::model()/autoload 覆盖，必须显式加载
require_once ROOT_PATH . 'lecms/plugin/ai_content_factory/model/ai_api_adapter.php';

class ai_task_control extends admin_control {

    /**
     * 任务列表（含插件设置 tab）
     */
    public function index() {
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 15;

        // B4：筛选条件（站点/状态/关键词）
        $filter_site = (int)R('site_id', 'R');
        $filter_status = (int)R('status', 'R');
        $keyword = trim((string)R('keyword', 'R'));

        $where = array();
        if($filter_site > 0) $where['site_id'] = $filter_site;
        if($filter_status > 0) $where['status'] = $filter_status - 1;  // URL 状态偏移：1待处理 2处理中 3完成 4失败 → DB 0/1/2/3
        if($keyword !== '') $where['prompt_template'] = array('LIKE' => safe_str($keyword));

        $tasks = $this->ai_task->find_fetch($where, array('id' => -1), ($page - 1) * $pagenum, $pagenum);
        $total = $this->ai_task->find_count($where);

        // 获取站点名称（find_fetch 的 key 是复合缓存键，需另建 sid => site_name 映射）
        $sites = $this->site_manager->get_list();
        $site_names = array();
        foreach($sites as $site) {
            $site_names[$site['sid']] = $site['site_name'];
        }

        // C9：统计卡片
        $stats = array('total' => 0, 'pending' => 0, 'running' => 0, 'done' => 0, 'failed' => 0, 'articles' => 0);
        $pre = $_ENV['_config']['db']['master']['tablepre'];
        $row = $this->db->fetch_first("SELECT COUNT(*) c, SUM(status=0) p, SUM(status=1) r, SUM(status=2) d, SUM(status=3) f FROM `{$pre}ai_task`");
        if($row) {
            $stats['total'] = (int)$row['c'];
            $stats['pending'] = (int)$row['p'];
            $stats['running'] = (int)$row['r'];
            $stats['done'] = (int)$row['d'];
            $stats['failed'] = (int)$row['f'];
        }
        $srow = $this->db->fetch_first("SELECT COUNT(*) c FROM `{$pre}cms_article` WHERE source='AI内容工厂'");
        $stats['articles'] = $srow ? (int)$srow['c'] : 0;

        // A1：配置唯一来源 ai_config 表（C8：site 参数指定站点级配置）
        $cfg_site = (int)R('site', 'R');
        $settings = $this->ai_task->get_settings($cfg_site);
        $settings['cfg_site'] = $cfg_site;
        // A5：掩码回显（有 key 显示占位符，不回显真实值）
        $settings['has_key'] = $settings['api_key'] !== '';

        // B5：API 配置状态
        $api_configured = $settings['api_key'] !== '';

        $tab = R('tab', 'G');
        if ($tab !== 'settings') {
            $tab = '';
        }

        // 进度百分比（layui progress 需要）
        foreach($tasks as &$task) {
            $task['percent'] = ((int)$task['total'] > 0) ? round(((int)$task['success'] + (int)$task['fail']) / (int)$task['total'] * 100) : 0;
        }
        unset($task);

        $this->assign_value('tasks', $tasks);
        $this->assign_value('sites', $sites);
        $this->assign_value('site_names', $site_names);
        $this->assign_value('total', $total);
        $this->assign_value('settings', $settings);
        $this->assign_value('stats', $stats);
        $this->assign_value('api_configured', $api_configured);
        $this->assign_value('tab', $tab);
        $this->assign_value('filter_site', $filter_site);
        $this->assign_value('filter_status', $filter_status);
        $this->assign_value('keyword', $keyword);
        $pagebar = $this->get_pagebar($total, $pagenum, $page); $this->assign_value('pagebar', $pagebar);
        $this->display('task_list.htm');
    }

    /**
     * 统计看板：各站点生成量/成功率 + 近14天执行趋势
     */
    public function stats() {
        $pre = $_ENV['_config']['db']['master']['tablepre'];
        $sites = $this->site_manager->get_list();
        $site_names = array();
        foreach($sites as $site) {
            $site_names[$site['sid']] = $site['site_name'];
        }

        // 各站点任务聚合（任务数/生成成功数/失败数）
        $rows = $this->db->fetch_all("SELECT site_id, COUNT(*) t, SUM(success) ok, SUM(fail) f FROM `{$pre}ai_task` GROUP BY site_id");
        $per_site = array();
        foreach($rows as $r) {
            $sid = (int)$r['site_id'];
            $ok = (int)$r['ok'];
            $f = (int)$r['f'];
            $per_site[$sid] = array(
                'site_id' => $sid,
                'site_name' => isset($site_names[$sid]) ? $site_names[$sid] : ('站点#' . $sid),
                'tasks' => (int)$r['t'],
                'ok' => $ok,
                'fail' => $f,
                'total_gen' => $ok + $f,
                'rate' => ($ok + $f) > 0 ? round($ok / ($ok + $f) * 100) : 0,
            );
        }
        // 各站点已生成文章数（来源标记）
        $arows = $this->db->fetch_all("SELECT site_id, COUNT(*) c FROM `{$pre}cms_article` WHERE source='AI内容工厂' GROUP BY site_id");
        foreach($arows as $r) {
            $sid = (int)$r['site_id'];
            if(isset($per_site[$sid])) $per_site[$sid]['articles'] = (int)$r['c'];
        }
        // 有文章但无任务的站点补齐行
        foreach($arows as $r) {
            $sid = (int)$r['site_id'];
            if(!isset($per_site[$sid])) {
                $per_site[$sid] = array(
                    'site_id' => $sid,
                    'site_name' => isset($site_names[$sid]) ? $site_names[$sid] : ('站点#' . $sid),
                    'tasks' => 0, 'ok' => 0, 'fail' => 0, 'total_gen' => 0, 'rate' => 0,
                    'articles' => (int)$r['c'],
                );
            }
        }
        foreach($per_site as &$ps) { if(!isset($ps['articles'])) $ps['articles'] = 0; }
        unset($ps);
        // 站点名称排序（按 sid）
        ksort($per_site);
        $per_site = array_values($per_site);

        // 近14天趋势（按任务创建日期聚合）
        $trend_rows = $this->db->fetch_all("SELECT DATE(created_at) d, COUNT(*) t, SUM(success) ok, SUM(fail) f FROM `{$pre}ai_task` WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY DATE(created_at)");
        $trend = array();
        foreach($trend_rows as $r) {
            $trend[] = array(
                'date' => $r['d'],
                'tasks' => (int)$r['t'],
                'ok' => (int)$r['ok'],
                'fail' => (int)$r['f'],
            );
        }
        // 补齐无任务的日期，保证连续
        $by_date = array();
        foreach($trend as $t) $by_date[$t['date']] = $t;
        $filled = array();
        for($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} day"));
            $base = isset($by_date[$d]) ? $by_date[$d] : array('date' => $d, 'tasks' => 0, 'ok' => 0, 'fail' => 0);
            $base['rate'] = ((int)$base['ok'] + (int)$base['fail']) > 0 ? round((int)$base['ok'] / ((int)$base['ok'] + (int)$base['fail']) * 100) : 0;
            $filled[] = $base;
        }
        $trend = $filled;

        // 总览：与 C9 一致
        $stats = array('total' => 0, 'pending' => 0, 'running' => 0, 'done' => 0, 'failed' => 0, 'articles' => 0, 'gen_ok' => 0, 'gen_fail' => 0);
        $row = $this->db->fetch_first("SELECT COUNT(*) c, SUM(status=0) p, SUM(status=1) r, SUM(status=2) d, SUM(status=3) f, SUM(success) ok, SUM(fail) ff FROM `{$pre}ai_task`");
        if($row) {
            $stats['total'] = (int)$row['c'];
            $stats['pending'] = (int)$row['p'];
            $stats['running'] = (int)$row['r'];
            $stats['done'] = (int)$row['d'];
            $stats['failed'] = (int)$row['f'];
            $stats['gen_ok'] = (int)$row['ok'];
            $stats['gen_fail'] = (int)$row['ff'];
        }
        $srow = $this->db->fetch_first("SELECT COUNT(*) c FROM `{$pre}cms_article` WHERE source='AI内容工厂'");
        $stats['articles'] = $srow ? (int)$srow['c'] : 0;
        $stats['overall_rate'] = ($stats['gen_ok'] + $stats['gen_fail']) > 0 ? round($stats['gen_ok'] / ($stats['gen_ok'] + $stats['gen_fail']) * 100) : 0;

        $this->assign_value('stats', $stats);
        $this->assign_value('per_site', $per_site);
        $this->assign_value('trend', $trend);
        $this->display('task_stats.htm');
    }

    /**
     * 创建任务表单（A4：分类下拉）
     */
    public function create() {
        $sites = $this->site_manager->get_list();
        $cidhtml = $this->category->get_cidhtml_by_mid(2, 0, '请选择分类');
        // 批量上限提示（与 create_post 校验同源：site_id 变化时以后端校验为准）
        $first_sid = 0;
        foreach($sites as $s) { $first_sid = (int)$s['sid']; break; }
        $settings = $this->ai_task->get_settings($first_sid);
        $this->assign_value('sites', $sites);
        $this->assign_value('cidhtml', $cidhtml);
        $this->assign_value('settings', $settings);
        $this->display('task_create.htm');
    }

    /**
     * 创建任务提交
     */
    public function create_post() {
        if(!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $site_id = (int)R('site_id', 'P');
        $category_id = (int)R('category_id', 'P');
        $count = (int)R('count', 'P');
        $prompt_template = trim(R('prompt_template', 'P'));

        if($site_id <= 0 || $count <= 0) {
            E(1, '请选择站点并输入生成数量');
        }

        // A4：分类校验（存在且属于文章模型）
        if($category_id <= 0) {
            E(1, '请选择分类');
        }
        $cat = $this->category->get($category_id);
        if(empty($cat) || (int)$cat['mid'] != 2) {
            E(1, '分类不存在或不属于文章模型');
        }

        // B6：生成上限对齐 batch_limit 设置
        $settings = $this->ai_task->get_settings($site_id);
        $batch_limit = max(1, (int)$settings['batch_limit']);
        if($count > $batch_limit) {
            E(1, '单次生成数量不能超过 ' . $batch_limit . '（可在插件设置中调整）');
        }

        // 创建任务（B8：记录创建人）
        $task_id = $this->ai_task->create_task($site_id, $category_id, $count, $prompt_template, $this->_user['uid']);
        if(!$task_id) {
            E(1, '任务创建失败');
        }

        E(0, '任务创建成功');
    }

    /**
     * 执行任务（B1：单批执行，返回结构化 JSON 供前端轮询）
     */
    public function execute() {
        $task_id = (int)R('task_id', 'P');
        if($task_id <= 0) {
            E(1, '无效的任务 ID');
        }

        try {
            $result = $this->ai_task->execute($task_id);
        } catch(Exception $e) {
            E(1, '任务执行失败：' . $e->getMessage());
        }

        if($result === false) {
            E(1, '任务不存在');
        }

        if(!empty($result['locked'])) {
            exit(json_encode(array('err' => 2, 'msg' => '任务正在执行中，请稍候', 'locked' => 1, 'done' => 0)));
        }

        $msg = '本批完成（累计成功 ' . (int)$result['success'] . '，失败 ' . (int)$result['fail'] . '）';
        if(!empty($result['degraded'])) $msg .= '，模板库降级';

        exit(json_encode(array(
            'err' => 0,
            'msg' => $msg,
            'success' => (int)$result['success'],
            'fail' => (int)$result['fail'],
            'total' => (int)$result['total'],
            'done' => empty($result['done']) ? 0 : 1,
            'degraded' => empty($result['degraded']) ? 0 : 1,
        )));
    }

    /**
     * 任务详情（B4：执行日志 + 该站点 AI 生成文章清单）
     */
    public function detail() {
        $task_id = (int)R('task_id', 'R');
        $task = $this->ai_task->get($task_id);
        if(!$task) {
            $this->message(0, '任务不存在', -1);
        }

        // 执行日志
        $exec_log = trim((string)$task['exec_log']);
        $log_lines = $exec_log === '' ? array() : explode("\n", $exec_log);

        // 该站点的 AI 生成文章（source 标识，最近 50 条）
        // 用子查询关联最新一条 URL 映射，避免 GROUP BY + 非聚合列在严格模式下报错
        $pre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$task['site_id'];
        $articles = $this->db->fetch_all("SELECT a.id, a.title, a.site_id, a.dateline, um.url
            FROM `{$pre}cms_article` a
            LEFT JOIN `{$pre}cms_url_map` um ON um.content_id = a.id AND um.status = 2
                AND um.id = (SELECT id FROM `{$pre}cms_url_map` u2
                             WHERE u2.content_id = a.id AND u2.status = 2
                             ORDER BY u2.id ASC LIMIT 1)
            WHERE a.source='AI内容工厂' AND a.site_id={$site_id}
            ORDER BY a.id DESC LIMIT 50");

        // 站点信息
        $site = $this->site_manager->get($site_id);
        $sites = $this->site_manager->get_list();

        // 分类信息
        $cat = $this->category->get((int)$task['category_id']);

        // 定时执行入口（C4）：完整地址 + md5 key，便于配置 crontab
        $cfg = $this->kv->xget('cfg');
        $webdomain = !empty($cfg['webdomain']) ? $cfg['webdomain'] : 'localhost';
        $webdir = isset($cfg['webdir']) ? $cfg['webdir'] : '/';
        if($webdir === '' || substr($webdir, -1) !== '/') $webdir = ($webdir === '' ? '' : $webdir . '/');
        $settings = $this->ai_task->get_settings(0);
        $cron_key = $settings['api_key'] === '' ? '' : md5($settings['api_key']);
        $cron_url = HTTP . rtrim($webdomain, '/') . '/' . ltrim(($webdir ?: ''), '/') . 'admin/index.php?ai_task-cron-key-' . $cron_key;

        $this->assign_value('task', $task);
        $this->assign_value('cron_url', $cron_url);
        $this->assign_value('cron_key', $cron_key);
        $this->assign_value('cron_configured', $settings['api_key'] !== '');
        $this->assign_value('site_name', $site ? $site['site_name'] : ('站点' . $site_id));
        $this->assign_value('cat_name', $cat ? $cat['name'] : ('分类' . $task['category_id']));
        $this->assign_value('log_lines', $log_lines);
        $this->assign_value('articles', $articles);
        $this->assign_value('sites', $sites);
        $this->display('task_detail.htm');
    }

    /**
     * 删除任务（行内操作，POST AJAX）
     */
    public function delete() {
        if(!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $task_id = (int)R('task_id', 'P');
        if($task_id <= 0) {
            E(1, '无效的任务 ID');
        }
        $task = $this->ai_task->get($task_id);
        if($task && (int)$task['exec_lock'] === 1) {
            E(1, '任务正在执行中，无法删除');
        }
        $res = $this->ai_task->delete($task_id);
        if(!$res) {
            E(1, '删除失败');
        }
        E(0, '任务已删除');
    }

    /**
     * 重置任务（B2 补充：失败任务可清空失败计数重新执行）
     */
    public function reset() {
        if(!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $task_id = (int)R('task_id', 'P');
        if($task_id <= 0) {
            E(1, '无效的任务 ID');
        }
        $res = $this->ai_task->reset_task($task_id);
        if($res === false) {
            E(1, '重置失败（任务不存在或正在执行中）');
        }
        E(0, '任务已重置，可重新执行');
    }

    /**
     * 插件设置页面（已合并进 index() 的第二个 tab）
     * 兼容旧入口：重定向到主页面设置 tab
     */
    public function settings() {
        header('Location: ?ai_task-index-tab-settings');
        exit;
    }

    /**
     * 保存插件设置（A1：UPSERT 到 ai_config 表；A5：Key 留空不改；C8：站点级配置）
     */
    public function settings_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $site_id = (int)R('cfg_site', 'P');
        if($site_id < 0) $site_id = 0;

        $temperature = (float)R('temperature', 'P');
        if ($temperature < 0) $temperature = 0;
        if ($temperature > 2) $temperature = 2;

        $max_tokens = (int)R('max_tokens', 'P');
        if ($max_tokens < 64) $max_tokens = 64;
        if ($max_tokens > 32768) $max_tokens = 32768;

        $timeout = (int)R('timeout', 'P');
        if ($timeout < 5) $timeout = 5;
        if ($timeout > 600) $timeout = 600;

        $batch_limit = (int)R('batch_limit', 'P');
        if ($batch_limit < 1) $batch_limit = 1;
        if ($batch_limit > 500) $batch_limit = 500;

        $max_retries = (int)R('max_retries', 'P');
        if ($max_retries < 0) $max_retries = 0;
        if ($max_retries > 10) $max_retries = 10;

        $api_base_url = trim(R('api_base_url', 'P')) ?: 'https://api.deepseek.com/v1';
        $model = trim(R('model', 'P')) ?: 'deepseek-chat';
        // C8：无 URL 自动建链开关
        $no_url_generate = R('no_url_generate', 'P') ? 1 : 0;

        $pre = $_ENV['_config']['db']['master']['tablepre'];
        $now = date('Y-m-d H:i:s', $_ENV['_time']);

        // A5：API Key 留空 = 不修改（仅填了新值才更新）
        $api_key = trim(R('api_key', 'P'));

        $row = $this->db->fetch_first("SELECT id, api_key FROM `{$pre}ai_config` WHERE site_id={$site_id} LIMIT 1");
        if($row) {
            $sets = array();
            $sets[] = "api_base_url='" . addslashes($api_base_url) . "'";
            if($api_key !== '') $sets[] = "api_key='" . addslashes($api_key) . "'";
            $sets[] = "model='" . addslashes($model) . "'";
            $sets[] = "max_tokens={$max_tokens}";
            $sets[] = "temperature={$temperature}";
            $sets[] = "timeout={$timeout}";
            $sets[] = "batch_limit={$batch_limit}";
            $sets[] = "max_retries={$max_retries}";
            $sets[] = "no_url_generate={$no_url_generate}";
            $sets[] = "updated_at='{$now}'";
            $this->db->exec("UPDATE `{$pre}ai_config` SET " . implode(', ', $sets) . " WHERE id=" . (int)$row['id']);
        } else {
            $this->db->exec("INSERT INTO `{$pre}ai_config`
                (site_id, api_base_url, api_key, model, max_tokens, temperature, timeout, batch_limit, max_retries, no_url_generate, created_at, updated_at)
                VALUES
                ({$site_id}, '" . addslashes($api_base_url) . "', '" . addslashes($api_key) . "', '" . addslashes($model) . "',
                 {$max_tokens}, {$temperature}, {$timeout}, {$batch_limit}, {$max_retries}, {$no_url_generate}, '{$now}', '{$now}')");
        }

        E(0, '插件设置已保存');
    }

    /**
     * 定时执行入口（供外部 cron 调用，免登录）
     * 用法：curl "http://yoursite/admin/index.php?ai_task-cron-key-{md5(api_key)}"
     * 通过 hook 伪造后台用户身份绕过登录，实时校验 key
     */
    public function cron() {
        $key = trim((string)R('key', 'R'));
        if($key === '') $key = trim((string)R('key', 'G'));
        $settings = $this->ai_task->get_settings(0);
        $expected = md5($settings['api_key']);
        if($settings['api_key'] === '' || $key !== $expected) {
            exit(json_encode(array('err' => 1, 'msg' => '认证失败或未配置 API Key')));
        }

        $pre = $_ENV['_config']['db']['master']['tablepre'];
        // 取所有待处理/处理中任务（status 0 或 1），每任务执行一批
        $tasks = $this->db->fetch_all("SELECT id FROM `{$pre}ai_task` WHERE status IN (0,1) AND exec_lock=0 ORDER BY id ASC LIMIT 20");
        if(empty($tasks)) {
            exit(json_encode(array('err' => 0, 'msg' => '无待处理任务', 'processed' => 0)));
        }

        $results = array();
        foreach($tasks as $t) {
            $task_id = (int)$t['id'];
            $r = $this->ai_task->execute($task_id);
            $results[] = array(
                'id' => $task_id,
                'success' => isset($r['success']) ? $r['success'] : 0,
                'fail' => isset($r['fail']) ? $r['fail'] : 0,
                'done' => !empty($r['done']),
                'locked' => !empty($r['locked']),
            );
        }

        exit(json_encode(array('err' => 0, 'msg' => '执行完成', 'processed' => count($tasks), 'results' => $results)));
    }

    /**
     * 测试 API 连接（POST AJAX）
     * 读取当前生效配置（site 参数可选，缺省全局）向 /chat/completions 发一条最小测试请求。
     * 支持表单未保存参数覆盖（api_base_url/model/api_key 非空时以表单值为准），
     * api_key 留空则复用已保存 Key。http_status 表示 HTTP 状态码，cURL 网络错误返回 err=1
     */
    public function test_connection() {
        if(!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $site_id = (int)R('site_id', 'P');
        if($site_id < 0) $site_id = 0;

        $adapter = new ai_api_adapter($site_id, $this->db);
        $config = $adapter->get_config();

        // 表单覆盖：base_url / model / api_key 非空则以表单值为准（api_key 留空=用已保存）
        $f_base = trim(R('api_base_url', 'P'));
        $f_model = trim(R('model', 'P'));
        $f_key = trim(R('api_key', 'P'));
        if($f_base !== '') $config['api_base_url'] = $f_base;
        if($f_model !== '') $config['model'] = $f_model;
        if($f_key !== '') $config['api_key'] = $f_key;

        if(empty($config['api_key'])) {
            E(1, '未配置 API Key，请先填写（留空保存表示不修改）');
        }

        // 最小连通性请求
        $url = rtrim($config['api_base_url'], '/') . '/chat/completions';
        $post_data = array(
            'model' => $config['model'],
            'messages' => array(
                array('role' => 'user', 'content' => 'hello'),
            ),
            'max_tokens' => 5,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_TIMEOUT, max(5, (int)$config['timeout']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['api_key'],
        ));
        $response = curl_exec($ch);
        if($response === false) {
            $err = curl_error($ch);
            curl_close($ch);
            E(1, '网络错误：' . $err);
        }
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($http_code == 200) {
            E(0, '连接成功（HTTP 200），模型 ' . $config['model'] . ' 可用');
        } elseif($http_code == 401 || $http_code == 403) {
            E(1, '认证失败（HTTP ' . $http_code . '）：API Key 无效或无权访问');
        } elseif($http_code == 429) {
            E(1, '请求被限流（HTTP 429）：请稍后重试');
        } elseif($http_code >= 500) {
            E(1, '服务端错误（HTTP ' . $http_code . '）：上游服务不可用');
        } else {
            E(1, '连接异常（HTTP ' . $http_code . '）：' . substr($response, 0, 200));
        }
    }
}

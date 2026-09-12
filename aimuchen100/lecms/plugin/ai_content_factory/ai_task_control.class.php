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
        $pre = $_ENV['_config']['db']['master']['tablepre'];
        $site_id = (int)$task['site_id'];
        $articles = $this->db->fetch_all("SELECT a.id, a.title, a.site_id, a.dateline, um.url
            FROM `{$pre}cms_article` a
            LEFT JOIN `{$pre}cms_url_map` um ON um.content_id = a.id AND um.status = 2
            WHERE a.source='AI内容工厂' AND a.site_id={$site_id}
            GROUP BY a.id
            ORDER BY a.id DESC LIMIT 50");

        // 站点信息
        $site = $this->site_manager->get($site_id);
        $sites = $this->site_manager->get_list();

        // 分类信息
        $cat = $this->category->get((int)$task['category_id']);

        $this->assign_value('task', $task);
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
}

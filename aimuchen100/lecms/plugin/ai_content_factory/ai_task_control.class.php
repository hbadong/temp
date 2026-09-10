<?php
defined('ROOT_PATH') or exit;


/**
 * 后台 AI 任务管理控制器
 *
 * 修复（对照验证发现）：
 * - REQ-03-AC2: create_post 数量上限 100（防单次生成量过大）
 * - CODE-4: execute 全程 try/catch，失败提示错误而非白屏
 * - CODE-3: 所有 assign() 改 assign_value()（按值传参，兼容字面量）
 */

class ai_task_control extends admin_control {

    /**
     * 任务列表
     */
    public function index() {
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 15;

        $tasks = $this->ai_task->find_fetch(array(), array('id' => -1), ($page - 1) * $pagenum, $pagenum);
        $total = $this->ai_task->find_count();

        // 获取站点名称
        $sites = $this->site_manager->get_list();

        // 插件设置（合并进主页面的第二个 tab）
        // 字段与 ai_api_adapter.php 中的 config 字段对应
        $plugin_settings = $this->runtime->xget('ai_content_factory_settings');
        if (!$plugin_settings || !is_array($plugin_settings)) {
            $plugin_settings = array(
                'api_base_url' => 'https://api.deepseek.com/v1',
                'api_key' => '',
                'model' => 'deepseek-chat',
                'temperature' => 0.7,
                'max_tokens' => 2048,
                'timeout' => 60,
                'batch_limit' => 100,
                'max_retries' => 3,
            );
        }

        // 当前激活 tab（index.php?ai_task-index-tab-settings）
        $tab = R('tab', 'G');
        if ($tab !== 'settings') {
            $tab = '';
        }

        $this->assign_value('tasks', $tasks);
        $this->assign_value('sites', $sites);
        $this->assign_value('total', $total);
        $this->assign_value('settings', $plugin_settings);
        $this->assign_value('tab', $tab);
        $pagebar = $this->get_pagebar($total, $pagenum, $page); $this->assign_value('pagebar', $pagebar);
        $this->display('task_list.htm');
    }

    /**
     * 创建任务表单
     */
    public function create() {
        $sites = $this->site_manager->get_list();
        $this->assign_value('sites', $sites);
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

        // REQ-03-AC2：单次生成上限 100
        if($count > 100) {
            E(1, '单次生成数量不能超过 100');
        }

        // 创建任务
        $task_id = $this->ai_task->create_task($site_id, $category_id, $count, $prompt_template);
        if(!$task_id) {
            E(1, '任务创建失败');
        }

        E(0, '任务创建成功');
    }

    /**
     * 执行任务（行内操作，POST AJAX）
     */
    public function execute() {
        $task_id = (int)R('task_id', 'P');
        if($task_id <= 0) {
            E(1, '无效的任务 ID');
        }

        // CODE-4：try/catch 防止致命错误白屏
        try {
            $result = $this->ai_task->execute($task_id);
        } catch(Exception $e) {
            E(1, '任务执行失败：' . $e->getMessage());
        }

        if($result === false) {
            E(1, '任务不存在');
        }

        E(0, '任务执行完成（成功 ' . (int)$result['success'] . '，失败 ' . (int)$result['fail'] . '）');
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
     * 保存插件设置
     */
    public function settings_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

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

        $settings = array(
            'api_base_url' => trim(R('api_base_url', 'P')) ?: 'https://api.deepseek.com/v1',
            'api_key' => trim(R('api_key', 'P')),
            'model' => trim(R('model', 'P')) ?: 'deepseek-chat',
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
            'timeout' => $timeout,
            'batch_limit' => $batch_limit,
            'max_retries' => $max_retries,
        );

        // 用 set() 整行写（键与 index() 的 xget('ai_content_factory_settings') 一致）；
        // 勿用 xset($k, $v)——其第3参 $key 默认 'cfg'，会把设置嵌进全局 cfg JSON 而非独立键
        $this->runtime->set('ai_content_factory_settings', $settings);
        E(0, '插件设置已保存');
    }
}

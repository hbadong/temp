<?php
defined('ROOT_PATH') or exit;

require_once ROOT_PATH . 'lecms/plugin/template_manager/model/template.class.php';
require_once ROOT_PATH . 'lecms/plugin/template_manager/model/block_config.class.php';
require_once ROOT_PATH . 'lecms/plugin/template_manager/model/template_file.class.php';
require_once ROOT_PATH . 'lecms/plugin/template_manager/model/audit_log.class.php';

/**
 * 模板管理插件 - 后台控制器
 *
 * 功能：主题记录管理 / Block 配置 / 模板文件版本 / 审计日志 / 插件设置
 */
class template_manager_control extends admin_control {

    private $tablepre;

    public function __construct() {
        parent::__construct();
        $this->tablepre = $_ENV['_config']['db']['master']['tablepre'];
        // 模板管理涉及写主题目录、切换站点主题、清空审计日志等高危操作，仅允许超管访问
        $this->check_isadmin();
    }

    // ==================== 默认入口 ====================

    public function index() {
        $this->themes();
    }

    // ==================== 主题管理 ====================

    /**
     * 主题列表页
     */
    public function themes() {
        $current_theme = $this->current_theme();
        $this->assign('theme', $current_theme);

        // 插件设置（合并进主页面第二个 tab）
        $settings = $this->get_settings();
        $theme_dirs = $this->scan_theme_dirs();
        $theme_names = array_keys($theme_dirs);
        if (empty($theme_names)) {
            $theme_names = array('default');
        }
        $whitelist = isset($settings['sandbox_whitelist_extensions']) ? $settings['sandbox_whitelist_extensions'] : '';
        if (is_array($whitelist)) {
            $whitelist = implode(',', $whitelist);
        }
        $this->assign('settings', $settings);
        $this->assign('settings_themes', $theme_names);
        $this->assign('sandbox_whitelist_text', $whitelist);

        $this->display('template_manager_themes.htm');
    }

    /**
     * 主题列表 JSON（目录 + 表记录合并）
     */
    public function theme_get_list() {
        $tpl = new TplTheme($this->db);
        $records = $tpl->get_all();
        $record_map = array();
        foreach ($records as $rec) {
            $record_map[$rec['name']] = $rec;
        }
        $dirs = $this->scan_theme_dirs();
        $current = $this->current_theme();

        $data_arr = array();
        $names = array_keys($dirs + array_flip(array_keys($record_map)));
        sort($names);
        foreach ($names as $name) {
            $rec = isset($record_map[$name]) ? $record_map[$name] : null;
            $data_arr[] = array(
                'name' => $name,
                'title' => $rec ? $rec['title'] : (isset($dirs[$name]['info_title']) ? $dirs[$name]['info_title'] : ''),
                'dir_exists' => isset($dirs[$name]) ? 1 : 0,
                'is_current' => ($name == $current) ? 1 : 0,
                'enabled' => $rec ? (int)$rec['enabled'] : -1, // -1=未登记
                'is_default' => $rec ? (int)$rec['is_default'] : 0,
                'version' => $rec ? $rec['version'] : '',
                'template_id' => $rec ? (int)$rec['id'] : 0,
                'file_count' => isset($dirs[$name]) ? count($this->scan_theme_files($name)) : 0,
            );
        }

        $arr = array('code' => 0, 'msg' => '', 'count' => count($data_arr), 'data' => $data_arr);
        exit(json_encode($arr));
    }

    /**
     * 登记主题：扫描 view/ 目录，把未登记的主题写入 cms_template
     */
    public function theme_sync_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $tpl = new TplTheme($this->db);
        $dirs = $this->scan_theme_dirs();
        $added = 0;
        foreach ($dirs as $name => $info) {
            if (!$tpl->get_by_name($name)) {
                $tpl->create(array(
                    'name' => $name,
                    'title' => $info['info_title'] ?: $name,
                    'enabled' => 1,
                ));
                $added++;
            }
        }
        if ($added) {
            (new AuditLog($this->db))->log($this->_uid, null, '', 'theme_sync', null, null);
        }
        E(0, $added ? "已登记 {$added} 个主题" : '已全部登记，无需新增');
    }

    /**
     * 新建主题表单
     */
    public function theme_create() {
        $empty_data = array();
        $this->assign('data', $empty_data);
        $edit_action = 'create';
        $this->assign('action', $edit_action);
        $this->display('template_manager_theme_edit.htm');
    }

    /**
     * 新建主题提交
     */
    public function theme_create_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $name = trim(R('name', 'P'));
        if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $name)) {
            E(1, '主题标识仅允许字母、数字、下划线和连字符');
        }
        $tpl = new TplTheme($this->db);
        if ($tpl->get_by_name($name)) {
            E(1, '该主题已存在');
        }
        if ($tpl->create(array(
            'name' => $name,
            'title' => trim(R('title', 'P')) ?: $name,
            'description' => trim(R('description', 'P')),
            'version' => trim(R('version', 'P')) ?: '1.0.0',
            'is_default' => R('is_default', 'P') ? 1 : 0,
            'enabled' => 1,
        ))) {
            (new AuditLog($this->db))->log($this->_uid, null, '', 'theme_create', null, null);
            E(0, '创建成功');
        }
        E(1, '创建失败');
    }

    /**
     * 编辑主题表单
     */
    public function theme_edit() {
        $id = (int)R('id', 'R');
        $tpl = new TplTheme($this->db);
        $data = $tpl->get($id);
        if (empty($data)) {
            $this->message(1, lang('data_no_exists'), 'index.php?template_manager-themes');
        }
        $this->assign('data', $data);
        $edit_action = 'edit';
        $this->assign('action', $edit_action);
        $this->display('template_manager_theme_edit.htm');
    }

    /**
     * 编辑主题提交
     */
    public function theme_edit_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $id = (int)R('id', 'P');
        $tpl = new TplTheme($this->db);
        if (empty($tpl->get($id))) {
            E(1, lang('data_no_exists'));
        }
        if ($tpl->save($id, array(
            'title' => trim(R('title', 'P')),
            'description' => trim(R('description', 'P')),
            'version' => trim(R('version', 'P')),
            'is_default' => R('is_default', 'P') ? 1 : 0,
        ))) {
            E(0, '更新成功');
        }
        E(1, '更新失败');
    }

    /**
     * 主题启停
     */
    public function theme_toggle_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $id = (int)R('id', 'P');
        $tpl = new TplTheme($this->db);
        $rec = $tpl->get($id);
        if (empty($rec)) {
            E(1, lang('data_no_exists'));
        }
        $new = $rec['enabled'] == 1 ? 0 : 1;
        if ($tpl->save($id, array('enabled' => $new))) {
            (new AuditLog($this->db))->log($this->_uid, $id, $rec['name'], 'theme_' . ($new ? 'enable' : 'disable'), null, null);
            E(0, $new ? '已启用' : '已禁用');
        }
        E(1, '操作失败');
    }

    /**
     * 设为默认主题（同时切换站点当前主题）
     */
    public function theme_set_default_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $id = (int)R('id', 'P');
        $tpl = new TplTheme($this->db);
        $rec = $tpl->get($id);
        if (empty($rec)) {
            E(1, lang('data_no_exists'));
        }

        // 清除其它默认标记
        $this->db->query("UPDATE `{$this->tablepre}cms_template` SET `is_default` = 0");
        $tpl->save($id, array('is_default' => 1, 'enabled' => 1));

        // 切换站点当前主题（与核心 theme_control.enable 一致）
        $this->kv->xset('theme', $rec['name'], 'cfg');
        $this->kv->save_changed();
        $this->runtime->delete('cfg');
        $this->clear_cache();

        (new AuditLog($this->db))->log($this->_uid, $id, $rec['name'], 'theme_set_default', null, null);
        E(0, '已设为默认主题');
    }

    /**
     * 删除主题记录（不删除目录）
     */
    public function theme_delete_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $id = (int)R('id', 'P');
        $tpl = new TplTheme($this->db);
        $rec = $tpl->get($id);
        if (empty($rec)) {
            E(1, lang('data_no_exists'));
        }
        if ($tpl->delete($id)) {
            (new AuditLog($this->db))->log($this->_uid, $id, $rec['name'], 'theme_delete', null, null);
            E(0, lang('delete_successfully'));
        }
        E(1, lang('delete_failed'));
    }

    // ==================== Block 配置 ====================

    /**
     * Block 配置列表页
     */
    public function blocks() {
        $this->display('template_manager_blocks.htm');
    }

    /**
     * Block 配置列表 JSON
     */
    public function block_get_list() {
        $site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $page = max(1, (int)R('page', 'R'));
        $limit = max(1, (int)R('limit', 'R'));
        $block = new BlockConfig($this->db);
        $res = $block->list_configs($site_id, $page, $limit);

        $data_arr = array();
        foreach ($res['list'] as $row) {
            $params = json_decode($row['params'], true);
            $param_count = is_array($params) ? count($params) : 0;
            $data_arr[] = array(
                'id' => (int)$row['id'],
                'site_id' => (int)$row['site_id'],
                'block_name' => $row['block_name'],
                'target' => $row['target'],
                'param_count' => $param_count,
                'enabled' => (int)$row['enabled'],
                'updated_at' => date('Y-m-d H:i', (int)$row['updated_at']),
            );
        }

        $arr = array('code' => 0, 'msg' => '', 'count' => (int)$res['total'], 'data' => $data_arr);
        exit(json_encode($arr));
    }

    /**
     * Block 配置表单（新建/编辑）
     */
    public function block_edit() {
        $id = (int)R('id', 'R');
        $block = new BlockConfig($this->db);
        $data = array('id' => 0, 'site_id' => defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0, 'block_name' => '', 'target' => '', 'params_text' => '', 'enabled' => 1);
        if ($id) {
            $row = $block->get_by_id($id);
            if (empty($row)) {
                $this->message(1, lang('data_no_exists'), 'index.php?template_manager-blocks');
            }
            $params = json_decode($row['params'], true);
            $data = array(
                'id' => (int)$row['id'],
                'site_id' => (int)$row['site_id'],
                'block_name' => $row['block_name'],
                'target' => $row['target'],
                'params_text' => is_array($params) ? _json_encode($params) : $row['params'],
                'enabled' => (int)$row['enabled'],
            );
        }
        $this->assign('data', $data);
        $this->display('template_manager_block_edit.htm');
    }

    /**
     * Block 配置保存
     */
    public function block_save_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $id = (int)R('id', 'P');
        $site_id = (int)R('site_id', 'P');
        $block_name = trim(R('block_name', 'P'));
        $target = trim(R('target', 'P'));
        $params_raw = trim(R('params', 'P'));

        if (empty($block_name)) {
            E(1, 'Block 名称不能为空');
        }
        $params = array();
        if ($params_raw) {
            $decoded = json_decode($params_raw, true);
            if (!is_array($decoded)) {
                E(1, '参数格式错误：必须是 JSON 对象');
            }
            $params = $decoded;
        }

        $block = new BlockConfig($this->db);

        // 唯一键冲突预检：同一 (site_id, block_name, target) 已被其它记录占用则拒绝
        $dup = $block->get($site_id, $block_name, $target);
        if ($dup && (int)$dup['id'] !== (int)$id) {
            E(1, '该站点下已存在相同 Block 名称与目标的配置');
        }

        if ($id) {
            $ok = $block->update_by_id($id, $site_id, $block_name, $target, $params);
        } else {
            $ok = $block->save($site_id, $block_name, $target, $params);
        }
        if ($ok) {
            (new AuditLog($this->db))->log($this->_uid, null, $block_name, 'block_save', null, null);
            E(0, '保存成功');
        }
        E(1, '保存失败');
    }

    /**
     * Block 配置启停
     */
    public function block_toggle_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $id = (int)R('id', 'P');
        $block = new BlockConfig($this->db);
        $row = $block->get_by_id($id);
        if (empty($row)) {
            E(1, lang('data_no_exists'));
        }
        $new = $row['enabled'] == 1 ? 0 : 1;
        if ($block->set_enabled($id, $new)) {
            E(0, $new ? '已启用' : '已禁用');
        }
        E(1, '操作失败');
    }

    /**
     * Block 配置删除
     */
    public function block_delete_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $id = (int)R('id', 'P');
        $block = new BlockConfig($this->db);
        if ($block->delete($id)) {
            E(0, lang('delete_successfully'));
        }
        E(1, lang('delete_failed'));
    }

    // ==================== 模板文件版本 ====================

    /**
     * 文件版本管理页
     */
    public function files() {
        $tpl = new TplTheme($this->db);
        $themes = $tpl->get_all();
        $selected = (int)R('template_id', 'R');
        $this->assign('themes', $themes);
        $this->assign('selected', $selected);
        $this->display('template_manager_files.htm');
    }

    /**
     * 文件列表 JSON（按主题过滤）
     */
    public function file_get_list() {
        $template_id = (int)R('template_id', 'R');
        $tpl = new TplTheme($this->db);
        $rec = $tpl->get($template_id);
        if (empty($rec)) {
            $arr = array('code' => 0, 'msg' => '', 'count' => 0, 'data' => array());
            exit(json_encode($arr));
        }

        $files = $this->scan_theme_files($rec['name']);
        $file_model = new TemplateFile($this->db);
        $data_arr = array();
        foreach ($files as $path) {
            $cur = $file_model->get_current($template_id, $path);
            $data_arr[] = array(
                'path' => $path,
                'version' => $cur ? (int)$cur['version_no'] : 0,
                'file_size' => is_file(ROOT_PATH . "view/{$rec['name']}/{$path}") ? filesize(ROOT_PATH . "view/{$rec['name']}/{$path}") : 0,
                'versions' => $file_model->count_versions($template_id, $path),
            );
        }

        $arr = array('code' => 0, 'msg' => '', 'count' => count($data_arr), 'data' => $data_arr);
        exit(json_encode($arr));
    }

    /**
     * 保存当前磁盘文件为新版本
     */
    public function file_save_version_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $template_id = (int)R('template_id', 'P');
        $path = trim(R('path', 'P'));
        if (empty($path)) {
            E(1, '文件路径不能为空');
        }
        $tpl = new TplTheme($this->db);
        $rec = $tpl->get($template_id);
        if (empty($rec)) {
            E(1, lang('data_no_exists'));
        }
        if (!$this->check_path($path)) {
            E(1, '非法文件路径');
        }
        $full = ROOT_PATH . "view/{$rec['name']}/{$path}";
        if (!is_file($full)) {
            E(1, '文件不存在');
        }
        $content = file_get_contents($full);
        $file_model = new TemplateFile($this->db);
        // 记录保存前真实当前版本号（回滚后指针可能不在 version_no-1）
        $cur = $file_model->get_current($template_id, $path);
        $old_version = $cur ? (int)$cur['version_no'] : 0;
        $version_no = $file_model->save_version($template_id, $path, $content, $this->_uid);
        if ($version_no) {
            (new AuditLog($this->db))->log($this->_uid, $template_id, $path, 'edit', $old_version, $version_no);
            E(0, "已保存版本 v{$version_no}");
        }
        E(1, '版本保存失败');
    }

    /**
     * 文件历史页
     */
    public function file_history() {
        $template_id = (int)R('template_id', 'R');
        // dash URL 不解码 %2F/%2D，此处还原（JS 端已把 '/' 与 '-' 编码）
        $path = trim(rawurldecode((string)R('path', 'R')));
        $tpl = new TplTheme($this->db);
        $rec = $tpl->get($template_id);
        if (empty($rec) || !$this->check_path($path)) {
            $this->message(1, lang('data_error'), 'index.php?template_manager-files');
        }
        $file_model = new TemplateFile($this->db);
        $history = $file_model->get_history($template_id, $path, 50);
        $this->assign('data_tid', $template_id);
        $this->assign('theme_name', $rec['name']);
        $this->assign('path', $path);
        $this->assign('history', $history);
        $this->display('template_manager_file_history.htm');
    }

    /**
     * 查看历史版本内容（AJAX，返回 JSON）
     */
    public function file_preview_post() {
        if (!form_submit()) {
            exit(json_encode(array('status' => 1, 'message' => lang('submit_invalid'))));
        }
        $template_id = (int)R('template_id', 'P');
        $path = trim(R('path', 'P'));
        $version_no = (int)R('version_no', 'P');
        if (!$this->check_path($path)) {
            exit(json_encode(array('status' => 1, 'message' => '非法文件路径')));
        }
        $file_model = new TemplateFile($this->db);
        $ver = $file_model->get_version($template_id, $path, $version_no);
        if (empty($ver)) {
            exit(json_encode(array('status' => 1, 'message' => '版本不存在')));
        }
        exit(json_encode(array('status' => 0, 'content' => $ver['content'], 'version_no' => $version_no)));
    }

    /**
     * 回滚到指定版本
     */
    public function file_rollback_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        $template_id = (int)R('template_id', 'P');
        $path = trim(R('path', 'P'));
        $version_no = (int)R('version_no', 'P');
        if (!$this->check_path($path)) {
            E(1, '非法文件路径');
        }
        $tpl = new TplTheme($this->db);
        $rec = $tpl->get($template_id);
        if (empty($rec)) {
            E(1, lang('data_no_exists'));
        }
        $file_model = new TemplateFile($this->db);
        $cur = $file_model->get_current($template_id, $path);
        $from_version = $cur ? (int)$cur['version_no'] : 0;
        $res = $file_model->rollback($template_id, $path, $version_no);
        if ($res['ok']) {
            (new AuditLog($this->db))->log($this->_uid, $template_id, $path, 'rollback', $from_version, $res['version_no']);
            E(0, "已回滚到 v{$res['version_no']}");
        }
        E(1, isset($res['error']) ? $res['error'] : '回滚失败');
    }

    // ==================== 审计日志 ====================

    /**
     * 审计日志页
     */
    public function audit_logs() {
        $this->display('template_manager_audit.htm');
    }

    /**
     * 审计日志 JSON
     */
    public function audit_get_list() {
        $page = max(1, (int)R('page', 'R'));
        $limit = max(1, (int)R('limit', 'R'));
        $audit = new AuditLog($this->db);
        $res = $audit->get_logs($page, $limit);
        $actions = array('edit' => '编辑', 'rollback' => '回滚', 'upload' => '上传', 'delete' => '删除',
            'theme_sync' => '登记主题', 'theme_create' => '新建主题', 'theme_enable' => '启用主题',
            'theme_disable' => '禁用主题', 'theme_set_default' => '设为默认', 'theme_delete' => '删除主题', 'block_save' => '保存Block');

        $data_arr = array();
        foreach ($res['list'] as $row) {
            $data_arr[] = array(
                'id' => (int)$row['id'],
                'operator_uid' => (int)$row['operator_uid'],
                'template_id' => (int)$row['template_id'],
                'path' => $row['path'],
                'action' => $row['action'],
                'action_text' => isset($actions[$row['action']]) ? $actions[$row['action']] : $row['action'],
                'old_version' => $row['old_version'],
                'new_version' => $row['new_version'],
                'ip' => $row['ip'],
                'created_at' => date('Y-m-d H:i:s', (int)$row['created_at']),
            );
        }

        $arr = array('code' => 0, 'msg' => '', 'count' => (int)$res['total'], 'data' => $data_arr);
        exit(json_encode($arr));
    }

    /**
     * 清空审计日志
     */
    public function audit_clear_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }
        if ((new AuditLog($this->db))->clear()) {
            E(0, '日志已清空');
        }
        E(1, '清空失败');
    }

    // ==================== 插件设置 ====================

    /**
     * 设置页已合并进主页面（themes）的「插件设置」tab，此处仅做兼容跳转
     */
    public function settings() {
        $this->message(0, '', 'index.php?template_manager-themes-tab-settings');
    }

    public function settings_post() {
        if (!form_submit()) {
            E(1, lang('submit_invalid'));
        }

        $default_theme = trim((string)R('default_theme', 'P'));
        if ($default_theme === '' || !preg_match('/^[a-zA-Z0-9_\-]+$/', $default_theme)) {
            $default_theme = 'default';
        }

        $backup_days = (int)R('backup_keep_days', 'P');
        if ($backup_days < 1) {
            $backup_days = 1;
        }
        if ($backup_days > 365) {
            $backup_days = 365;
        }

        $whitelist_raw = trim((string)R('sandbox_whitelist_extensions', 'P'));
        $whitelist_arr = array();
        if ($whitelist_raw !== '') {
            foreach (preg_split('/[\r\n,]+/', $whitelist_raw) as $ext) {
                $ext = strtolower(trim(ltrim((string)$ext, '.')));
                if ($ext === '') {
                    continue;
                }
                if (preg_match('/^[a-z0-9]+$/', $ext)) {
                    $whitelist_arr[] = $ext;
                }
            }
        }

        $settings = array(
            'enabled'                      => R('enabled', 'P') ? 1 : 0,
            'css_injection_enabled'        => R('css_injection_enabled', 'P') ? 1 : 0,
            'schema_enabled'               => R('schema_enabled', 'P') ? 1 : 0,
            'brand_injection_enabled'      => R('brand_injection_enabled', 'P') ? 1 : 0,
            'block_enabled'                => R('block_enabled', 'P') ? 1 : 0,
            'sandbox_enabled'              => R('sandbox_enabled', 'P') ? 1 : 0,
            'audit_log_enabled'            => R('audit_log_enabled', 'P') ? 1 : 0,
            'version_control_enabled'      => R('version_control_enabled', 'P') ? 1 : 0,
            'default_theme'                => $default_theme,
            'backup_keep_days'             => $backup_days,
            'sandbox_whitelist_extensions' => $whitelist_arr,
        );

        $setting_file = PLUGIN_PATH . 'template_manager/setting.php';
        $export = "<?php\nreturn " . var_export($settings, true) . ";\n";
        $written = file_put_contents($setting_file, $export);
        if ($written === false) {
            E(1, '配置保存失败，请检查目录权限');
        }

        E(0, '配置保存成功');
    }

    // ==================== 工具方法 ====================

    /**
     * 读取插件设置（不存在时返回默认值）
     */
    private function get_settings() {
        $setting_file = PLUGIN_PATH . 'template_manager/setting.php';
        $settings = $this->default_settings();
        if (file_exists($setting_file)) {
            $saved = include $setting_file;
            if (is_array($saved)) {
                $settings = array_merge($settings, $saved);
            }
        }
        return $settings;
    }

    /**
     * 默认设置
     */
    private function default_settings() {
        return array(
            'enabled'                      => 1,
            'css_injection_enabled'        => 1,
            'schema_enabled'               => 1,
            'brand_injection_enabled'      => 0,
            'block_enabled'                => 1,
            'sandbox_enabled'              => 1,
            'audit_log_enabled'            => 1,
            'version_control_enabled'      => 1,
            'default_theme'                => 'default',
            'backup_keep_days'             => 30,
            'sandbox_whitelist_extensions' => array('htm', 'html', 'css', 'js'),
        );
    }

    /**
     * 当前站点主题
     */
    private function current_theme() {
        $cfg = $this->runtime->xget('cfg');
        return isset($cfg['theme']) ? $cfg['theme'] : 'default';
    }

    /**
     * 扫描 view/ 目录（主题目录名 + info.ini 标题）
     */
    private function scan_theme_dirs() {
        $dir = ROOT_PATH . 'view/';
        $dirs = array();
        if (is_dir($dir)) {
            foreach (glob($dir . '*', GLOB_ONLYDIR) as $d) {
                $name = basename($d);
                // 白名单校验：允许字母/数字/下划线/连字符（如 cartoon-cute），
                // 拒绝空格、点、路径分隔符等；原 /\W/ 会把含连字符的主题全部过滤掉
                if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $name)) {
                    continue;
                }
                $title = '';
                $info = $d . '/info.ini';
                if (is_file($info) && $lines = file($info)) {
                    foreach ($lines as $line) {
                        if (strpos($line, '=') !== false && stripos($line, 'title') === 0) {
                            $title = trim(explode('=', $line, 2)[1]);
                            break;
                        }
                    }
                }
                $dirs[$name] = array('dir_exists' => 1, 'info_title' => $title);
            }
        }
        return $dirs;
    }

    /**
     * 扫描主题目录下的模板文件（递归 .htm/.html）
     */
    private function scan_theme_files($theme) {
        $theme = preg_replace('/[^a-zA-Z0-9_\-]/', '', $theme);
        $base = ROOT_PATH . "view/{$theme}/";
        $files = array();
        if (!is_dir($base)) {
            return $files;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, array('htm', 'html', 'xml', 'css', 'js'))) {
                    $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($base)));
                    if (strpos($rel, '.') === 0 || strpos($rel, '../') !== false) {
                        continue;
                    }
                    $files[] = $rel;
                }
            }
        }
        sort($files);
        return $files;
    }

    /**
     * 模板文件路径防护
     */
    private function check_path($path) {
        if ($path === '' || strpos($path, '..') !== false || strpos($path, ':') !== false) {
            return false;
        }
        return true;
    }
}

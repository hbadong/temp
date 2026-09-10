<?php
defined('ROOT_PATH') or exit;

/**
 * 补丁管理控制器
 */

class patch_control extends admin_control {

    private $tablepre;
    private $site_id;

    public function __construct() {
        parent::__construct();
        $this->site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
        $this->tablepre = $_ENV['_config']['db']['master']['tablepre'];
        require_once ROOT_PATH . 'lecms/plugin/patch_system/model/patch_manager.class.php';
        require_once ROOT_PATH . 'lecms/plugin/patch_system/model/rollback_manager.class.php';
    }

    /**
     * 更新首页
     */
    public function index() {
        // 获取最新版本记录
        $latest = $this->db->fetch_first("
            SELECT * FROM `{$this->tablepre}patch_version`
            WHERE site_id = {$this->site_id}
            ORDER BY id DESC LIMIT 1
        ");

        // 获取最近操作日志
        $patch_site_id = (int)$this->site_id;
        $logs = $this->db->fetch_all("
            SELECT l.*, v.version, v.name
            FROM `{$this->tablepre}patch_log` l
            LEFT JOIN `{$this->tablepre}patch_version` v ON l.version_id = v.id
            WHERE l.site_id = {$patch_site_id}
            ORDER BY l.id DESC LIMIT 10
        ");

        $cms_version = C('version');

        // 插件设置（合并自原 settings 页）
        $settings = $this->runtime->xget('patch_settings');
        if (!$settings || !is_array($settings)) {
            $settings = array(
                'auto_check' => 0,
                'update_source' => '',
                'backup_keep' => 5,
            );
        }

        $this->assign('latest', $latest);
        $this->assign('logs', $logs);
        $this->assign('cms_version', $cms_version);
        $this->assign('plugin_settings', $settings);
        $this->display('patch_index.htm');
    }

    /**
     * 上传补丁包
     */
    public function upload_post() {
        if (!form_submit()) $this->message(0, lang('submit_invalid'));
        if (!isset($_FILES['patch_file']) || $_FILES['patch_file']['error'] !== UPLOAD_ERR_OK) {
            $this->message(1, '请选择补丁包文件');
        }

        $upload_dir = RUNTIME_PATH . '/patch_system/uploads/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0755, true);
        }

        $tmp_name = $_FILES['patch_file']['tmp_name'];
        $file_name = 'patch-' . date('YmdHis') . '-' . substr(md5(microtime(true)), 0, 6) . '.zip';
        $target = $upload_dir . $file_name;

        if (!move_uploaded_file($tmp_name, $target)) {
            $this->message(1, '上传失败，请检查目录权限');
        }

        try {
            $manager = new patch_manager($this->site_id, $this->db);
            $manifest = $manager->parse_patch($target);

            // 记录上传日志
            $this->log('upload', 0, '', array(
                'version' => $manifest['version'],
                'name' => isset($manifest['name']) ? $manifest['name'] : '',
                'files_count' => count($manifest['files']),
                'file_path' => $target,
            ));

            $this->message(0, '上传成功', array(
                'manifest' => $manifest,
                'file_path' => $target,
            ));

        } catch (Exception $e) {
            @unlink($target);
            $this->log('upload', 1, $e->getMessage());
            $this->message(1, '解析失败: ' . $e->getMessage());
        }
    }

    /**
     * 验证补丁包
     */
    public function verify_post() {
        if (!form_submit()) $this->message(0, lang('submit_invalid'));
        $file_path = trim(R('file_path', 'P'));
        if (empty($file_path) || !file_exists($file_path)) {
            $this->message(1, '补丁包文件不存在');
        }

        try {
            $manager = new patch_manager($this->site_id, $this->db);
            $manifest = $manager->parse_patch($file_path);

            // 验证签名和 MD5
            $verified = $manager->verify_patch($manifest, $file_path);
            if (!$verified) {
                $this->message(1, '补丁包验证失败：签名或文件校验不通过');
            }

            // 版本兼容性检查
            $compatible = $manager->check_version_compatible($manifest, C('version'));
            if ($compatible !== true) {
                $this->message(1, '版本不兼容: ' . $compatible);
            }

            $this->message(0, '验证通过', array(
                'manifest' => $manifest,
                'files_count' => count($manifest['files']),
            ));

        } catch (Exception $e) {
            $this->message(1, '验证失败: ' . $e->getMessage());
        }
    }

    /**
     * 应用补丁
     */
    public function apply_post() {
        if (!form_submit()) $this->message(0, lang('submit_invalid'));
        $file_path = trim(R('file_path', 'P'));
        if (empty($file_path) || !file_exists($file_path)) {
            $this->message(1, '补丁包文件不存在');
        }

        try {
            $manager = new patch_manager($this->site_id, $this->db);
            $manifest = $manager->parse_patch($file_path);

            // 验证签名和 MD5
            $verified = $manager->verify_patch($manifest, $file_path);
            if (!$verified) {
                $this->message(1, '补丁包验证失败：签名或文件校验不通过');
            }

            // 版本兼容性检查
            $compatible = $manager->check_version_compatible($manifest, C('version'));
            if ($compatible !== true) {
                $this->message(1, '版本不兼容: ' . $compatible);
            }

            // 应用补丁
            $result = $manager->apply_patch($manifest, $file_path);

            if ($result['success']) {
                $this->message(0, '补丁应用成功', array(
                    'version_id' => $result['version_id'],
                    'version' => $manifest['version'],
                ));
            } else {
                $this->message(1, '补丁应用失败: ' . $result['error']);
            }

        } catch (Exception $e) {
            $this->message(1, '应用失败: ' . $e->getMessage());
        }
    }

    /**
     * 回滚到指定版本
     */
    public function rollback_post() {
        if (!form_submit()) $this->message(0, lang('submit_invalid'));
        $version_id = (int)R('version_id', 'P');
        if (!$version_id) {
            $this->message(1, '参数错误');
        }

        // 获取版本信息
        $version = $this->db->fetch_first("
            SELECT * FROM `{$this->tablepre}patch_version`
            WHERE id = {$version_id} AND site_id = {$this->site_id} LIMIT 1
        ");

        if (!$version) {
            $this->message(1, '版本记录不存在');
        }

        if ($version['status'] == 1) {
            $this->message(1, '该版本已回滚，无需重复操作');
        }

        if (empty($version['backup_path'])) {
            $this->message(1, '备份路径为空，无法回滚');
        }

        try {
            $rollback = new rollback_manager($this->site_id);
            $result = $rollback->restore($version['backup_path']);

            if ($result) {
                // 更新版本状态
                $rollback_time = (int)$_ENV['_time'];
                $version_id = (int)$version_id;
                $patch_site_id = (int)$this->site_id;
                $this->db->query("UPDATE `{$this->tablepre}patch_version` SET status = 1, rolled_back_at = {$rollback_time} WHERE id = {$version_id} AND site_id = {$patch_site_id} LIMIT 1");

                // 记录回滚日志
                $this->log('rollback', 0, '', array(
                    'version_id' => $version_id,
                    'version' => $version['version'],
                    'backup_path' => $version['backup_path'],
                ));

                $this->message(0, '回滚成功');
            } else {
                $this->message(1, '回滚失败，请检查备份文件');
            }

        } catch (Exception $e) {
            $this->log('rollback', 1, $e->getMessage());
            $this->message(1, '回滚失败: ' . $e->getMessage());
        }
    }

    /**
     * 操作历史
     */
    public function history() {
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;

        $sql = "SELECT l.*, v.version, v.name
                FROM `{$this->tablepre}patch_log` l
                LEFT JOIN `{$this->tablepre}patch_version` v ON l.version_id = v.id
                WHERE l.site_id = {$this->site_id}
                ORDER BY l.id DESC";

        $total_row = $this->db->fetch_first("SELECT COUNT(*) AS cnt FROM ({$sql}) t");
        $total = $total_row ? $total_row['cnt'] : 0;

        $sql .= " LIMIT {$pagenum} OFFSET " . (($page - 1) * $pagenum);
        $list = $this->db->fetch_all($sql);

        $this->assign('list', $list);
        $this->assign('total', $total);
        $pagebar = $this->get_pagebar($total, $pagenum, $page); $this->assign('pagebar', $pagebar);
        $this->display('patch_history.htm');
    }

    /**
     * 补丁系统设置页（已合并进 index 页第二个 tab，兼容重定向）
     */
    public function settings() {
        $this->message(0, '', 'index.php?patch-index-tab-settings');
    }

    /**
     * 保存补丁系统设置
     */
    public function settings_post() {
        if (!form_submit()) $this->message(0, lang('submit_invalid'));

        $auto_check = R('auto_check', 'P') ? 1 : 0;

        $update_source = trim(R('update_source', 'P'));
        if ($update_source !== '' && !preg_match('#^https?://#i', $update_source)) {
            $this->message(1, '远程更新源 URL 必须以 http:// 或 https:// 开头');
        }
        $update_source = addslashes($update_source);

        $backup_keep = (int)R('backup_keep', 'P');
        if ($backup_keep < 0) {
            $backup_keep = 0;
        }
        if ($backup_keep > 100) {
            $backup_keep = 100;
        }

        $settings = array(
            'auto_check' => $auto_check,
            'update_source' => $update_source,
            'backup_keep' => $backup_keep,
        );

        $this->runtime->set('patch_settings', $settings);
        $this->runtime->save_changed();
        E(0, '保存成功');
    }

    /**
     * 记录操作日志
     */
    private function log($action, $status, $error = '', $detail = array()) {
        $action_s = addslashes($action);
        $error_s = addslashes($error);
        $detail_s = !empty($detail) ? "'" . addslashes(json_encode($detail, JSON_UNESCAPED_UNICODE)) . "'" : 'NULL';
        $time = (int)$_ENV['_time'];
        $sql = "INSERT INTO `{$this->tablepre}patch_log` (site_id,action,status,error_message,detail,created_at) VALUES ("
            . (int)$this->site_id . ",'$action_s'," . (int)$status . ",'$error_s',$detail_s,$time)";
        $this->db->exec($sql);
        return $this->db->last_insert_id();
    }
}

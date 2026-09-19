<?php
defined('ROOT_PATH') or exit;
/**
 * 软件管理控制器
 * 软件列表 / 表单 / 删除 / 上下架 / CSV 批量导入 / 模板下载
 */

class admin_soft_control extends admin_control {

    private $platforms = array('Windows', 'macOS', 'Linux', 'Android', 'iOS');

    /**
     * 软件列表
     */
    public function index() {
        $filter = array(
            'site_id' => (int)(R('site_id', 'R') ?: 1),
            'cat_id' => (int)R('cat_id', 'R'),
            'status' => R('status', 'R') === '' ? '' : (int)R('status', 'R'),
            'platform' => trim(R('platform', 'R')),
            'keyword' => trim(R('keyword', 'R')),
        );
        $page = max(1, (int)R('page', 'R'));
        $pagenum = 20;

        $total = $this->software->admin_count($filter);
        $rows = $this->software->admin_list($filter, $page, $pagenum);

        // 分类名映射（模板不支持动态下标插值，控制器拼好显示字段）
        $cat_names = $this->get_cat_names($filter['site_id']);
        foreach($rows as $k => $r) {
            $rows[$k]['cat_name'] = isset($cat_names[$r['cat_id']]) ? $cat_names[$r['cat_id']] : '-';
        }

        $pagebar = $this->get_pagebar($total, $pagenum, $page, 5, array(
            'site_id' => $filter['site_id'],
            'cat_id' => $filter['cat_id'],
            'status' => $filter['status'],
            'platform' => $filter['platform'],
            'keyword' => $filter['keyword'],
        ));

        $cats = $this->software_category->admin_all($filter['site_id']);
        $platforms = $this->platforms;
        $site_names = $this->get_site_names();
        $this->assign('rows', $rows);
        $this->assign('total', $total);
        $this->assign('pagebar', $pagebar);
        $this->assign('filter', $filter);
        $this->assign('cats', $cats);
        $this->assign('platforms', $platforms);
        $this->assign('site_names', $site_names);
        $this->display('admin_soft_list.htm');
    }

    /**
     * 添加/编辑软件表单
     */
    public function set() {
        $id = (int)R('id', 'R');
        $site_id = (int)(R('site_id', 'R') ?: 1);
        $data = $id > 0 ? $this->software->get($id) : array(
            'id' => 0, 'site_id' => $site_id, 'cat_id' => 0, 'name' => '', 'version' => '',
            'size' => '', 'platform' => 'Windows', 'download_url' => '', 'cover' => '',
            'intro' => '', 'content' => '', 'tags' => '', 'status' => 1,
        );
        if($id > 0 && !$data) $this->message(1, lang('data_no_exists'));

        $cats = $this->software_category->admin_all($data['site_id']);
        $this->assign('data', $data);
        $this->assign('cats', $cats);
        $this->assign('platforms', $this->platforms);
        $this->display('admin_soft_set.htm');
    }

    /**
     * 保存软件（新增/更新）
     */
    public function set_post() {
        if(!form_submit()) $this->message(1, lang('submit_invalid'));
        $id = (int)R('id', 'P');
        $site_id = (int)(R('site_id', 'P') ?: 1);
        $name = trim(strip_tags(R('name', 'P')));
        $cat_id = (int)R('cat_id', 'P');

        if($name === '') $this->message(1, '软件名称不能为空');

        // 站点内按名称去重（编辑时排除自身）
        $dup = $this->software->get_by_name($name, $site_id);
        if($dup && (int)$dup['id'] !== $id) $this->message(1, '该软件名称已存在（#' . $dup['id'] . '）');

        $download_url = trim(R('download_url', 'P'));
        if($download_url === '') $this->message(1, '下载地址不能为空');

        $data = array(
            'site_id' => $site_id,
            'cat_id' => $cat_id,
            'name' => $name,
            'version' => trim(R('version', 'P')),
            'size' => trim(R('size', 'P')),
            'platform' => trim(R('platform', 'P')),
            'download_url' => $download_url,
            'cover' => trim(R('cover', 'P')),
            'intro' => mb_substr(trim(R('intro', 'P')), 0, 1000, 'UTF-8'),
            'content' => trim(R('content', 'P')),
            'tags' => trim(R('tags', 'P')),
            'status' => (int)R('status', 'P') ? 1 : 0,
        );

        if($id > 0) {
            $data['id'] = $id;
            $ret = $this->software->update_soft($data);
            $msg = '保存成功';
        } else {
            $id = $this->software->create_soft($data);
            $ret = (bool)$id;
            $msg = '添加成功';
        }
        if(!$ret) $this->message(1, lang('edit_failed'));
        $this->message(0, $msg, '?admin_soft-index');
    }

    /**
     * 删除软件
     */
    public function del() {
        if(!form_submit()) $this->message(1, lang('submit_invalid'));
        $id = (int)R('id', 'P');
        if(!$this->software->get($id)) $this->message(1, lang('data_no_exists'));
        $ret = $this->software->delete_soft($id);
        $this->message($ret ? 0 : 1, $ret ? '已删除' : lang('delete_failed'));
    }

    /**
     * 上下架切换
     */
    public function toggle() {
        if(!form_submit()) $this->message(1, lang('submit_invalid'));
        $id = (int)R('id', 'P');
        $row = $this->software->get($id);
        if(!$row) $this->message(1, lang('data_no_exists'));
        $new_status = $row['status'] == 1 ? 0 : 1;
        $ret = $this->software->update_soft(array('id' => $id, 'status' => $new_status));
        $this->message($ret ? 0 : 1, $new_status == 1 ? '已上架' : '已下架');
    }

    /**
     * 批量导入页
     */
    public function import() {
        $site_id = (int)(R('site_id', 'R') ?: 1);
        $logs = $this->software_import_log->recent($site_id, 10);
        // 拼显示字段（模板不支持动态下标插值）
        $cat_names = $this->get_cat_names($site_id);
        foreach($logs as $k => $l) {
            $logs[$k]['dateline_text'] = date('Y-m-d H:i:s', $l['dateline']);
        }
        $site_names = $this->get_site_names();
        $this->assign('site_id', $site_id);
        $this->assign('site_names', $site_names);
        $this->assign('logs', $logs);
        $this->assign('cat_names', $cat_names);
        $this->display('admin_soft_import.htm');
    }

    /**
     * CSV 导入处理
     * 列顺序：软件名称,所属分类,版本号,软件大小,系统平台,下载地址,封面图,软件简介,标签
     */
    public function import_post() {
        if(!form_submit()) $this->message(1, lang('submit_invalid'));
        $site_id = (int)(R('site_id', 'P') ?: 1);
        if(!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->message(1, '请选择 CSV 文件');
        }
        $tmp_name = $_FILES['csv_file']['tmp_name'];
        $orig_name = $_FILES['csv_file']['name'];

        $handle = fopen($tmp_name, 'r');
        if(!$handle) $this->message(1, 'CSV 文件读取失败');

        // 现有分类（名称 => id），用于自动匹配与创建
        $cat_names = array();
        foreach($this->software_category->admin_all($site_id) as $c) {
            $cat_names[$c['name']] = (int)$c['id'];
        }

        $total = 0; $success = 0; $failed = 0; $fail_detail = array();
        $line = 0;
        while(($raw = fgetcsv($handle)) !== false) {
            $line++;
            // 剥离 UTF-8 BOM（首行首列）
            if($line === 1 && isset($raw[0])) {
                $raw[0] = preg_replace('/^\xEF\xBB\xBF/', '', $raw[0]);
            }
            // 跳过表头
            if($line === 1 && isset($raw[0]) && trim($raw[0]) === '软件名称') continue;
            // 跳过空行
            $joined = implode('', $raw);
            if(trim($joined) === '') continue;

            $total++;
            $name = isset($raw[0]) ? trim($raw[0]) : '';
            $cate_name = isset($raw[1]) ? trim($raw[1]) : '';
            $version = isset($raw[2]) ? trim($raw[2]) : '';
            $size = isset($raw[3]) ? trim($raw[3]) : '';
            $platform = isset($raw[4]) ? trim($raw[4]) : '';
            $download_url = isset($raw[5]) ? trim($raw[5]) : '';
            $cover = isset($raw[6]) ? trim($raw[6]) : '';
            $intro = isset($raw[7]) ? mb_substr(trim($raw[7]), 0, 1000, 'UTF-8') : '';
            $tags = isset($raw[8]) ? trim($raw[8]) : '';

            if($name === '') {
                $failed++;
                $fail_detail[] = "第{$line}行：软件名称为空";
                continue;
            }
            // 去重
            if($this->software->get_by_name($name, $site_id)) {
                $failed++;
                $fail_detail[] = "第{$line}行：{$name} 已存在，跳过";
                continue;
            }
            // 分类匹配或自动创建
            $cat_id = 0;
            if($cate_name !== '') {
                if(isset($cat_names[$cate_name])) {
                    $cat_id = $cat_names[$cate_name];
                } else {
                    $new_alias = $this->software_category->unique_alias($this->pinyin_fallback($cate_name), $site_id);
                    $cat_id = (int)$this->software_category->create(array(
                        'site_id' => $site_id,
                        'name' => $cate_name,
                        'alias' => $new_alias,
                        'orderby' => 99,
                        'enabled' => 1,
                    ));
                    if($cat_id) {
                        $cat_names[$cate_name] = $cat_id;
                        $this->software_category->register_url_map($new_alias, $site_id, 2);
                    }
                }
            }

            $new_id = $this->software->create_soft(array(
                'site_id' => $site_id,
                'cat_id' => $cat_id,
                'name' => $name,
                'version' => mb_substr($version, 0, 50, 'UTF-8'),
                'size' => mb_substr($size, 0, 50, 'UTF-8'),
                'platform' => mb_substr($platform, 0, 100, 'UTF-8'),
                'download_url' => mb_substr($download_url, 0, 500, 'UTF-8'),
                'cover' => mb_substr($cover, 0, 500, 'UTF-8'),
                'intro' => $intro,
                'content' => $intro,
                'tags' => mb_substr($tags, 0, 500, 'UTF-8'),
                'status' => 1,
            ));
            if($new_id) {
                $success++;
            } else {
                $failed++;
                $fail_detail[] = "第{$line}行：{$name} 写入失败";
            }
        }
        fclose($handle);

        $this->software_import_log->log($site_id, $orig_name, $total, $success, $failed, implode("\n", array_slice($fail_detail, 0, 50)));
        $this->message(0, "导入完成：共 {$total} 行，成功 {$success}，失败 {$failed}", '?admin_soft-import');
    }

    /**
     * CSV 模板下载（UTF-8 BOM，Excel 直接打开不乱码）
     */
    public function csvtemplate() {
        $rows = array(
            array('软件名称', '所属分类', '版本号', '软件大小', '系统平台', '下载地址', '封面图', '软件简介', '标签'),
            array('示例播放器', '影音娱乐', '5.2.1', '48MB', 'Windows', 'https://example.com/setup.exe', 'https://example.com/cover.jpg', '这是一款示例播放软件', '视频,播放器'),
            array('示例清理工具', '系统工具', '9.0', '25MB', 'Windows,macOS', 'https://example.com/clean.exe', '', '这是一款示例清理软件', '清理,优化'),
        );
        $csv = "\xEF\xBB\xBF";
        foreach($rows as $r) {
            $csv .= implode(',', array_map(array($this, 'csv_escape'), $r)) . "\r\n";
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="software_import_template.csv"');
        header('Content-Length: ' . strlen($csv));
        echo $csv;
        exit;
    }

    private function csv_escape($v) {
        if(strpos($v, ',') !== false || strpos($v, '"') !== false || strpos($v, "\n") !== false) {
            return '"' . str_replace('"', '""', $v) . '"';
        }
        return $v;
    }

    /**
     * 中文名转分类别名兜底（无拼音扩展时使用 cat+随机后缀）
     */
    private function pinyin_fallback($name) {
        if(preg_match('/^[a-z0-9\-]+$/i', $name)) {
            return strtolower($name);
        }
        return 'cat-' . substr(md5($name), 0, 6);
    }

    private function get_cat_names($site_id) {
        $arr = array();
        foreach($this->software_category->admin_all($site_id) as $c) {
            $arr[$c['id']] = $c['name'];
        }
        return $arr;
    }

    private function get_site_names() {
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        $rows = $this->db->fetch_all("SELECT sid, site_name FROM `{$tablepre}site_manager` ORDER BY sid ASC");
        $arr = array();
        if($rows) {
            foreach($rows as $r) {
                $arr[$r['sid']] = $r['site_name'];
            }
        }
        return $arr;
    }
}

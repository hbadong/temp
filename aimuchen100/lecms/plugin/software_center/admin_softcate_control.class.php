<?php
defined('ROOT_PATH') or exit;
/**
 * 软件分类管理控制器
 */

class admin_softcate_control extends admin_control {

    public function index() {
        $site_id = (int)(R('site_id', 'R') ?: 1);
        $rows = $this->software_category->admin_all($site_id);

        // 拼接每分类软件数（模板不支持动态下标插值）
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        foreach($rows as $k => $r) {
            $cnt = $this->db->fetch_first("SELECT COUNT(*) AS num FROM `{$tablepre}cms_software` WHERE cat_id=" . (int)$r['id']);
            $rows[$k]['soft_count'] = $cnt ? (int)$cnt['num'] : 0;
        }

        $total_row = $this->db->fetch_first("SELECT COUNT(*) AS num FROM `{$tablepre}cms_software_category` WHERE site_id=" . (int)$site_id);
        $total = $total_row ? (int)$total_row['num'] : 0;

        $site_names = $this->get_site_names();
        $this->assign('rows', $rows);
        $this->assign('total', $total);
        $this->assign('site_id', $site_id);
        $this->assign('site_names', $site_names);
        $this->display('admin_softcate_list.htm');
    }

    /**
     * 添加/编辑分类表单
     */
    public function set() {
        $id = (int)R('id', 'R');
        $site_id = (int)(R('site_id', 'R') ?: 1);
        $data = $id > 0 ? $this->software_category->get($id) : array(
            'id' => 0, 'site_id' => $site_id, 'name' => '', 'alias' => '',
            'orderby' => 0, 'seo_title' => '', 'seo_keywords' => '', 'seo_description' => '', 'enabled' => 1,
        );
        if($id > 0 && !$data) $this->message(1, lang('data_no_exists'));

        $this->assign('data', $data);

        // 上级分类下拉：仅列一级分类（parent_id=0），且排除自身
        $parents = array();
        foreach($this->software_category->admin_all($site_id) as $c) {
            if((int)$c['parent_id'] === 0 && (int)$c['id'] !== $id) {
                $parents[] = $c;
            }
        }
        $this->assign('parents', $parents);

        $this->display('admin_softcate_set.htm');
    }

    /**
     * 保存分类
     */
    public function set_post() {
        if(!form_submit()) $this->message(1, lang('submit_invalid'));
        $id = (int)R('id', 'P');
        $site_id = (int)(R('site_id', 'P') ?: 1);
        $name = trim(strip_tags(R('name', 'P')));
        if($name === '') $this->message(1, '分类名称不能为空');

        // 同名查重（编辑时排除自身）
        foreach($this->software_category->admin_all($site_id) as $c) {
            if($c['name'] === $name && (int)$c['id'] !== $id) {
                $this->message(1, '分类名称已存在');
            }
        }

        $alias = trim(R('alias', 'P'));
        if($alias === '') $alias = $name;
        $alias = $this->software_category->unique_alias($alias, $site_id, $id);

        $data = array(
            'site_id' => $site_id,
            'parent_id' => (int)R('parent_id', 'P'),
            'name' => $name,
            'alias' => $alias,
            'intro' => trim(R('intro', 'P')),
            'orderby' => (int)R('orderby', 'P'),
            'seo_title' => trim(R('seo_title', 'P')),
            'seo_keywords' => trim(R('seo_keywords', 'P')),
            'seo_description' => trim(R('seo_description', 'P')),
            'enabled' => (int)R('enabled', 'P') ? 1 : 0,
        );

        if($id > 0) {
            $data['id'] = $id;
            $ret = (bool)$this->software_category->save_cate($data);
            $msg = '保存成功';
        } else {
            $new_id = $this->software_category->save_cate($data);
            $ret = (bool)$new_id;
            $msg = '添加成功';
        }
        if(!$ret) $this->message(1, lang('edit_failed'));
        $this->message(0, $msg, '?admin_softcate-index');
    }

    /**
     * 删除分类
     */
    public function del() {
        if(!form_submit()) $this->message(1, lang('submit_invalid'));
        $id = (int)R('id', 'P');
        $site_id = (int)(R('site_id', 'P') ?: 1);
        list($ok, $msg) = $this->software_category->delete_cate($id, $site_id);
        $this->message($ok ? 0 : 1, $msg);
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

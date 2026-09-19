<?php
defined('ROOT_PATH') or exit;
/**
 * 游戏分类管理控制器（对齐软件分类管理）
 */

class admin_gamecate_control extends admin_control {

    public function index() {
        $site_id = (int)(R('site_id', 'R') ?: 1);
        $rows = $this->game_category->admin_all($site_id);

        // 拼接每分类游戏数与前台地址（模板不支持动态下标插值）
        $tablepre = $_ENV['_config']['db']['master']['tablepre'];
        foreach($rows as $k => $r) {
            $cnt = $this->db->fetch_first("SELECT COUNT(*) AS num FROM `{$tablepre}cms_game` WHERE category_id=" . (int)$r['id']);
            $rows[$k]['game_count'] = $cnt ? (int)$cnt['num'] : 0;
            $rows[$k]['url'] = $r['alias'] !== '' ? '/category/' . $r['alias'] . '.html' : '';
        }

        $total_row = $this->db->fetch_first("SELECT COUNT(*) AS num FROM `{$tablepre}cms_game_category` WHERE site_id=" . (int)$site_id);
        $total = $total_row ? (int)$total_row['num'] : 0;

        $site_names = $this->get_site_names();
        $this->assign('rows', $rows);
        $this->assign('total', $total);
        $this->assign('site_id', $site_id);
        $this->assign('site_names', $site_names);
        $this->display('admin_gamecate_list.htm');
    }

    /**
     * 添加/编辑分类表单
     */
    public function set() {
        $id = (int)R('id', 'R');
        $site_id = (int)(R('site_id', 'R') ?: 1);
        $data = $id > 0 ? $this->game_category->get($id) : array(
            'id' => 0, 'site_id' => $site_id, 'name' => '', 'alias' => '',
            'intro' => '', 'orderby' => 0, 'seo_title' => '', 'seo_keywords' => '', 'seo_description' => '', 'enabled' => 1,
        );
        if($id > 0 && !$data) $this->message(1, lang('data_no_exists'));

        $this->assign('data', $data);
        $this->display('admin_gamecate_set.htm');
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
        foreach($this->game_category->admin_all($site_id) as $c) {
            if($c['name'] === $name && (int)$c['id'] !== $id) {
                $this->message(1, '分类名称已存在');
            }
        }

        $alias = trim(R('alias', 'P'));
        if($alias === '') $alias = $name;
        $alias = $this->game_category->unique_alias($alias, $site_id, $id);

        $data = array(
            'site_id' => $site_id,
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
            $ret = (bool)$this->game_category->save_cate($data);
            $msg = '保存成功';
        } else {
            $new_id = $this->game_category->save_cate($data);
            $ret = (bool)$new_id;
            $msg = '添加成功';
        }
        if(!$ret) $this->message(1, lang('edit_failed'));
        $this->message(0, $msg, '?admin_gamecate-index');
    }

    /**
     * 删除分类
     */
    public function del() {
        if(!form_submit()) $this->message(1, lang('submit_invalid'));
        $id = (int)R('id', 'P');
        $site_id = (int)(R('site_id', 'P') ?: 1);
        list($ok, $msg) = $this->game_category->delete_cate($id, $site_id);
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
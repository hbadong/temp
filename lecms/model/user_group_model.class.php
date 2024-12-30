<?php
defined('ROOT_PATH') or exit;

class user_group extends model {
    private $data = array();		// 防止重复查询

    public $_field_length = array(
        'groupname'=>20,
        // hook user_group_model_field_length_after.php
    );

	function __construct() {
		$this->table = 'user_group';	// 表名
		$this->pri = array('groupid');	// 主键
		$this->maxid = 'groupid';		// 自增字段
	}

    // 根据用户组名获取用户组数据
    public function get_user_group_by_groupname($groupname = '') {
        // hook usre_group_model_get_user_group_by_groupname_before.php
        $data = $this->find_fetch(array('groupname'=>$groupname), array(), 0, 1);
        $user_group = $data ? current($data) : array();

        // hook usre_group_model_get_user_group_by_groupname_after.php
        return $user_group;
    }

    // 根据用户组ID获取用户组数据，为什么不直接用 get ？ 方便后续通过钩子扩展
    public function get_user_group_by_gid($gid = 0) {
        // hook usre_group_model_get_user_group_by_gid_before.php
        $user_group = $this->get($gid);

        // hook usre_group_model_get_user_group_by_gid_after.php
        return $user_group;
    }

    // 获取所有用户组
	public function get_groups(){
        if(isset($this->data['groups'])) {
            return $this->data['groups'];
        }

        return $this->data['groups'] = $this->find_fetch();
    }

    // 获取所有用户组的名称
    public function get_name(){
        if(isset($this->data['name'])) {
            return $this->data['name'];
        }

        $groups_arr = $this->get_groups();
        $arr = array();
        foreach ($groups_arr as $v) {
            $arr[$v['groupid']] = $v['groupname'];
        }
        return $this->data['name'] = $arr;
    }

    // 获取内容列表
    public function list_arr($where = array(), $orderby = 'groupid', $orderway = 1, $start = 0, $limit = 0, $total = 0, $extra = array()) {
        // hook user_group_model_list_arr_before.php

        // 优化大数据量翻页
        if($start > 1000 && $total > 2000 && $start > $total/2) {
            $orderway = -$orderway;
            $newstart = $total-$start-$limit;
            if($newstart < 0) {
                $limit += $newstart;
                $newstart = 0;
            }
            $list_arr = $this->find_fetch($where, array($orderby => $orderway), $newstart, $limit);
            $list_arr = array_reverse($list_arr, TRUE);
        }else{
            $list_arr = $this->find_fetch($where, array($orderby => $orderway), $start, $limit);
        }

        // hook user_group_model_list_arr_after.php
        return $list_arr;
    }

    //用户组下拉框
    public function get_groupidhtml($groupid = 0, $tips = '选择用户组'){
        $tmp = $this->find_fetch(array(), array('groupid'=>1));
        $s = '<select name="groupid" id="groupid" lay-filter="groupid">';
        if(empty($tmp)) {
            $s .= '<option value="0">'.lang('no_data').'</option>';
        }else{
            $s .= '<option value="0"'.(empty($groupid) ? ' selected="selected"': '').'>'.$tips.'</option>';
            foreach($tmp as $v) {
                $s .= '<option value="'.$v['groupid'].'"'.($v['groupid'] == $groupid ? ' selected="selected"' : '').'>'.$v['groupname'].'</option>';
            }

        }
        $s .= '</select>';
        // hook user_group_model_get_groupidhtml_after.php
        return $s;
    }

    // 内容关联删除
    public function xdelete($groupid) {
        // 内容读取
        $data = $this->get($groupid);
        if(empty($data)) return lang('data_no_exists');
        if( $data['system'] ) return lang('system_group_no_delete');

        if( $this->user->find_count(array('groupid'=>$groupid)) ){
            return lang('groupname_exists_user');
        }

        $ret = $this->delete($groupid);
        return $ret ? '' : lang('delete_failed');
    }

    // hook user_group_model_after.php
}

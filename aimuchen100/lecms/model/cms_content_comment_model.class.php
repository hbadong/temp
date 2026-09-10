<?php
defined('ROOT_PATH') or exit;

class cms_content_comment extends model {
    private $data = array();		// 防止重复查询

    public $_field_length = array(
        'author'=>50,
        'content'=>255,
        // hook cms_content_comment_model_field_length_after.php
    );

	function __construct() {
		$this->table = 'cms_comment';			// 表名
		$this->pri = array('commentid');	// 主键
		$this->maxid = 'commentid';		// 自增字段
	}

	// 格式化评论数组
	public function format(&$v, $dateformat = 'Y-m-d H:i:s', $humandate = TRUE) {
		// hook cms_content_comment_model_format_before.php

		if(empty($v)) return FALSE;

		$v['date'] = $humandate ? human_date($v['dateline'], $dateformat) : date($dateformat, $v['dateline']);
		$v['fullip'] = long2ip($v['ip']);
		$v['ip'] = substr($v['fullip'], 0, strrpos($v['fullip'], '.')).'.*';

        if( !isset($this->data['cfg']) ) {
            $this->data['cfg'] = $this->runtime->xget();
        }

        $v['user_url'] = $this->urls->space_url($v['uid']);
        $v['avatar'] = $this->urls->user_avatar($v['uid']);

        $v['reply_comment'] = array();
        $v['reply_comment_content'] = '';    //兼容旧版本的~~~

		// hook cms_content_comment_model_format_after.php
	}

	// 获取评论列表
	public function list_arr($where = array(), $orderby = 'commentid', $orderway = 1, $start = 0, $limit = 0, $total = 0, $extra = array()) {
        // hook cms_content_comment_model_list_arr_before.php

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

        // hook cms_content_comment_model_list_arr_after.php
        return $list_arr;
	}

	//判断用户是否已评价过某内容
    public function is_comment_uid_id($uid = 0 , $id = 0){
	    $where = array('uid'=>$uid, 'id'=>$id);
        // hook cms_content_comment_model_is_comment_uid_id_where_after.php

        $count = $this->find_count($where);
        if( $count ){
            return $count;
        }else{
            return false;
        }
    }

    //获取模型评论下拉框
    public function get_commenthtml_mid($mid = 2, $str = '', $mid1 = 0){
        $tmp = $this->models->find_fetch(array(), array('mid'=>1));
        $s = '<select name="mid" id="mid" '.$str.'>';
        if(empty($tmp)) {
            $s .= '<option value="0">'.lang('no_data').'</option>';
        }else{
            foreach($tmp as $v) {
                if($mid1 == 0 && $v['mid'] == 1){
                    continue;
                }
                if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
                    $modelname = $v['name'];
                }else{
                    $modelname = ucfirst($v['tablename']).' ';
                }
                $s .= '<option value="'.$v['mid'].'"'.($v['mid'] == $mid ? ' selected="selected"' : '').'>'.$modelname.lang('comment').'</option>';
            }
        }
        $s .= '</select>';
        // hook cms_content_comment_model_get_commenthtml_mid_after.php
        return $s;
    }

	// 评论关联删除
	public function xdelete($table = 'article', $commentid = 0) {
		// hook cms_content_comment_model_xdelete_before.php

        $comments = $this->get($commentid);
        if(empty($comments)){
            return lang('data_no_exists');
        }

        //单页评论，无内容关联
        if($table == 'page'){
            return $this->delete($commentid) ? '' : lang('delete_failed');
        }

        $this->cms_content->table = 'cms_'.$table;

		// 更新内容评论数
		$data = $this->cms_content->get($comments['id']);
		if($data && $data['comments'] > 0) {
			$data['comments']--;
			if(!$this->cms_content->update($data)) return lang('write_table_failed');
		}else{
            $data['comments'] = 0;
        }

		//更新评论排序表的评论数
        $data2 = $this->cms_content_comment_sort->find_fetch(array('mid'=>$comments['mid'], 'id'=>$comments['id']), array(), 0, 1);
        if($data2) {
            $data2 = current($data2);
            $data2['comments'] = $data['comments'];
            if( empty($data2['comments']) ){
                $this->cms_content_comment_sort->find_delete(array('mid'=>$comments['mid'], 'id'=>$comments['id']));
            }else{
                $this->cms_content_comment_sort->update($data2);
            }
        }
        // hook cms_content_comment_model_xdelete_after.php

        $r = $this->delete($commentid);
        // hook cms_content_comment_model_xdelete_success.php
		return $r ? '' : lang('delete_failed');
	}

    // hook cms_content_comment_model_after.php
}

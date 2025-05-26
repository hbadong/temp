<?php
defined('ROOT_PATH') or exit;

class cms_content_tag extends model {
    private $data = array();		// 防止重复查询

    public $_field_length = array(
        'name'=>80,
        'content'=>255,
        'pic'=>255,
        'seo_title'=>255,
        'seo_keywords'=>255,
        'seo_description'=>255,
        // hook cms_content_tag_model_field_length_after.php
    );

	function __construct() {
		$this->table = '';				// 内容标签表表名 比如 cms_article_tag
		$this->pri = array('tagid');	// 主键
		$this->maxid = 'tagid';			// 自增字段
	}

	//标签处理
	public function _tagformat($tagname = '', $add = 1){
        // hook cms_content_tag_model_tagformat_before.php
		if(empty($tagname)){
            return '';
        }
	    if($add){
            $tagname = safe_str($tagname);
            $tagname = str_replace('-', ' ', $tagname);//横线转空格
            $tagname = preg_replace("/\s(?=\s)/","\\1",$tagname); //多个空格转成一个空格
            $tagname = strtolower($tagname);

            //格式：2_名字，只要后面的 名字，避免和URL伪静态冲突
            preg_match('/^([2-9]\d*)\_(.+)$/i', $tagname, $mat);
            if(isset($mat[2])){
                $tagname = $mat[2];
            }

            // hook cms_content_tag_model_tagformat_add_after.php
            if($tagname && mb_strlen($tagname)<=80) {
                return $tagname;
            }else{
                return '';
            }
        }else{
            $tagname = str_replace(' ', '-', $tagname);   //标签里面的 空格转 -
            $tagname = preg_replace('/-{2,}/', '-', $tagname);    //多个 - 改成 一个 -
            // hook cms_content_tag_model_tagformat_after.php
            return $tagname;
        }
    }


	// 获取标签列表
    public function list_arr($where = array(), $orderby = 'tagid', $orderway = 1, $start = 0, $limit = 0, $total = 0, $extra = array()) {
        // hook cms_content_tag_model_list_arr_before.php

        // 优化大数据量翻页
        if($start > 1000 && $total > 2000 && $start > $total/2) {
            $orderway = -$orderway;
            $newstart = $total-$start-$limit;
            if($newstart < 0) {
                $limit += $newstart;
                $newstart = 0;
            }
            //不知道为什么 只按count排序 当他们的值一样时，会出现 不同页数，一样的数据
            if($orderby == 'count'){
                $order = array($orderby => $orderway, 'tagid'=>$orderway);
            }else{
                $order = array($orderby => $orderway);
            }
            $list_arr = $this->find_fetch($where, $order, $newstart, $limit);
            $list_arr = array_reverse($list_arr, TRUE);
        }else{
            if($orderby == 'count'){
                $order = array($orderby => $orderway, 'tagid'=>$orderway);
            }else{
                $order = array($orderby => $orderway);
            }
            $list_arr = $this->find_fetch($where, $order, $start, $limit);
        }

        // hook cms_content_tag_model_list_arr_after.php
        return $list_arr;
    }

    //根据标签名获取标签信息
    public function get_tag_by_tagname($name, $table = '')
    {
        // hook cms_content_tag_model_get_tag_by_tagname_before.php
        if($table){
            $this->table = 'cms_'.$table.'_tag';
        }
        $tags = $this->find_fetch(array('name'=>$name), array(), 0, 1);
        if(!$tags){
            return array();
        }
        $tags = current($tags);
        // hook cms_content_tag_model_get_tag_by_tagname_after.php
        return $tags;
    }

    //根据表名和标签ID获取标签信息
    public function get_tag_by_table_tagid($table = 'article', $tagid = 0){
	    if( empty($tagid) ){
	        return array();
        }
        $this->table = 'cms_'.$table.'_tag';
        return $this->get($tagid);
    }

    // 格式化分类数组
    public function format(&$v, $mid = 2) {
        // hook cms_content_tag_model_format_before.php

        if(empty($v)) return FALSE;

        if( isset($this->data['cfg']) ){
            $cfg = $this->data['cfg'];
        }else{
            $this->data['cfg'] = $cfg = $this->runtime->xget();
        }

        if( isset($v['pic']) && !empty($v['pic']) ){
            $v['haspic'] = 1;
            if( substr($v['pic'], 0, 2) != '//' && substr($v['pic'], 0, 4) != 'http' ){ //不是外链图片
                $v['pic'] = $cfg['weburl'].$v['pic'];
            }
        }else{
            $v['haspic'] = 0;
            $v['pic'] = $cfg['weburl'].'static/img/nopic.gif';
        }
        !isset($v['url']) && $v['url'] = $this->cms_content->tag_url($mid, $v);

        // hook cms_content_tag_model_format_after.php
    }

    //获取模型标签下拉框
    public function get_taghtml_mid($mid = 2, $str = ''){
        $tmp = $this->models->find_fetch(array(), array('mid'=>1));
        $s = '<select name="mid" id="mid" '.$str.'>';
        if(empty($tmp)) {
            $s .= '<option value="0">'.lang('no_data').'</option>';
        }else{
            foreach($tmp as $v) {
                if($v['mid'] > 1){
                    if( $_ENV['_config']['admin_lang'] == 'zh-cn' ){
                        $modelname = $v['name'];
                    }else{
                        $modelname = ucfirst($v['tablename']).' ';
                    }
                    $s .= '<option value="'.$v['mid'].'"'.($v['mid'] == $mid ? ' selected="selected"' : '').'>'.$modelname.lang('tag').'</option>';
                }
            }
        }
        $s .= '</select>';
        // hook cms_content_tag_model_get_taghtml_mid_after.php
        return $s;
    }

	// 标签关联删除 (需要删除三个表: cms_content_tag cms_content_tag_data cms_content)
	public function xdelete($table, $tagid) {
        // hook cms_content_tag_model_xdelete_before.php

		$this->table = 'cms_'.$table.'_tag';
		$this->cms_content->table = 'cms_'.$table;
		$this->cms_content_tag_data->table = 'cms_'.$table.'_tag_data';

		$tag_data = $this->get($tagid);
		if(empty($tag_data)){
		    return lang('data_no_exists');
        }

		// 删除 cms_content 表的内容
		try{
		    $total = $this->cms_content_tag_data->find_count(array('tagid'=>$tagid));
            $pagenum = 500;
		    if($total > $pagenum){  // 如果内容数太大，做分批删除设计
                $maxpage = max(1, ceil($total/$pagenum));
                for($i = 0; $i < $maxpage; $i++){
                    $list_arr = $this->cms_content_tag_data->find_fetch(array('tagid'=>$tagid), array('tagid' => 1), $i*$pagenum, $pagenum);
                    foreach($list_arr as $v) {
                        $data = $this->cms_content->get($v['id']);
                        if($data){
                            $row = _json_decode($data['tags']);
                            unset($row[$tagid]);

                            $updata = array('id'=>$data['id'], 'tags'=>_json_encode($row));
                            if(!$this->cms_content->update($updata)) return lang('write_table_failed');
                        }
                    }
                }
            }else{
                $list_arr = $this->cms_content_tag_data->find_fetch(array('tagid'=>$tagid));
                foreach($list_arr as $v) {
                    $data = $this->cms_content->get($v['id']);
                    if($data){
                        $row = _json_decode($data['tags']);
                        unset($row[$tagid]);
                        $updata = array('id'=>$data['id'], 'tags'=>_json_encode($row));

                        if(!$this->cms_content->update($updata)) return lang('write_table_failed');
                    }
                }
            }
		}catch(Exception $e) {
			return lang('write_table_failed');
		}

		// 删除 cms_content_tag_data 表的内容
		try{
			$this->cms_content_tag_data->find_delete(array('tagid'=>$tagid));
		}catch(Exception $e) {
			return lang('delete_failed');
		}

		if( $this->delete($tagid) ){
		    if($tag_data['pic']){
                $file = ROOT_PATH.$tag_data['pic'];
                is_file($file) && unlink($file);
            }
            // hook cms_content_tag_model_xdelete_success.php
		    return '';
        }else{
            return  lang('delete_failed');
        }
	}

    // hook cms_content_tag_model_after.php
}

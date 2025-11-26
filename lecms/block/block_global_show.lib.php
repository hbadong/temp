<?php
defined('ROOT_PATH') || exit;

/**
 * 内容页模块
 * @param string dateformat 时间格式
 * @param int show_prev_next 显示上下翻页
 * @param int cid 上下翻页读取数据是否要分类CID参数
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框、图集等）
 * @param int pageoffset 内容分页显示偏移量
 * @param string page_function 分页函数，xiunophp/ext/paginator.class.php
 * @param int life 缓存时间
 * @return array
 */
function block_global_show($conf) {
	global $run, $_show, $_user;

	// hook block_global_show_before.php
    $cid = isset($_show['cid']) ? (int)$_show['cid'] : 0;       //避免缓存数据错乱
    $id = &$_show['id'];
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$show_prev_next = isset($conf['show_prev_next']) && (int)$conf['show_prev_next'] ? true : false;
    $prev_next_cid = isset($conf['cid']) ? (int)$conf['cid'] : intval($_GET['cid']);
    $field_format = _int($conf, 'field_format', 0);
    $pageoffset = _int($conf, 'pageoffset', 5);
    $page_function = empty($conf['page_function']) ? 'pages' : $conf['page_function'];
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_global_show');
    $page = max(1, intval(R('page')));
    $cache_params = array($page, $cid, $id, $dateformat, $show_prev_next, $prev_next_cid, $field_format, $pageoffset, $page_function);
    // hook block_global_show_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('global_show'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

	// 排除单页模型
	$mid = &$run->_var['mid'];
	if($mid == 1) return FALSE;

	//当前登录用户UID
	$uid = isset($_user['uid']) ? (int)$_user['uid'] : 0;

	// 初始模型表名
	$run->cms_content_data->table = 'cms_'.$run->_var['table'].'_data';

	// 格式化
	$run->cms_content->format($_show, $mid, $dateformat, 0, 0, $field_format);

	// 合并大数据字段
	$id = &$_show['id'];
	$_show['comment_url'] = $run->cms_content->comment_url($run->_var['cid'], $id); //评论URL
    $_show['views_url'] = $run->_cfg['webdir'].'index.php?views--cid-'.$run->_var['cid'].'-id-'.$id;  //异步获取和更新浏览量URL

    // hook block_global_show_data_before.php
	$data = $run->cms_content_data->get_cms_content_data($id, $run->_var['table']);
    // hook block_global_show_data_after.php
	if($data){
        if($field_format){
			$models_field = $run->cms_content->get_model_fields_by_mid($mid);
            $models_field && $run->models_field->field_val_format($models_field, $data, 0);
            // hook block_global_show_field_format_after.php
        }
        $_show += $data;

        //内容字段分页
        $_show = $run->cms_content_data->format_content($_show, $page);
        if( isset($_show['content_page']) && isset($_show['maxpage']) ){
            $_show['pages'] = paginator::$page_function($page, $_show['maxpage'], $run->cms_content->content_url($_show, $mid, TRUE), $pageoffset);
        }else{
            $_show['pages'] = false;
        }
    }else{
        // hook block_global_show_data_error.php
        $_show['pages'] = false;
    }

	//获取浏览量
    $run->cms_content_views->table = 'cms_'.$run->_var['table'].'_views';
    $views_data = $run->cms_content_views->get_cms_content_views($id, $run->_var['table']);
    if($views_data){
        if( empty($run->_cfg['close_views']) ){
            $_show['views'] = $views_data['views']+1;
            $run->cms_content_views->update_views($id);
        }else{
            $_show['views'] = $views_data['views'];
        }
    }else{
        $_show['views'] = 1;
        empty($run->_cfg['close_views']) && $run->cms_content_views->set_cms_content_views($id, array('views'=>1,'cid'=>$_show['cid']), $run->_var['table']);
    }

    //附件信息
    if(isset($_show['filenum']) && !empty($_show['filenum'])){
        list($attachlist, $imagelist, $filelist) = $run->cms_content_attach->attach_find_by_id($run->_var['table'], $id, array('id'=>$id, 'isimage'=>0));
        $_show['filelist'] = $filelist;
        if($_show['uid'] == $uid && $uid){
            $file_delete = true;
        }else{
            $file_delete = false;
        }
        $_show['filelist_html'] = $run->cms_content_attach->file_list_html($filelist, $mid, $file_delete);
    }else{
        $_show['filelist'] = array();
        $_show['filelist_html'] = '';
    }

    // hook block_global_show_center.php

	// 显示上下翻页 (大数据站点建议关闭)
	if($show_prev_next) {
	    if($prev_next_cid){
            $prev_where = array('cid'=>$prev_next_cid, 'id'=>array('<'=> $id));
            $next_where = array('cid'=>$prev_next_cid, 'id'=>array('>'=> $id));
        }else{
            $prev_where = array('id'=>array('<'=> $id));
            $next_where = array('id'=>array('>'=> $id));
        }

		// 上1条
        $_show['prev'] = $run->cms_content->list_arr($prev_where, 'id', -1, 0, 1, 1, $extra);
        if($_show['prev']){
            $_show['prev'] = current($_show['prev']);
            $run->cms_content->format($_show['prev'], $mid, $dateformat);
        }

		// 下1条
        $_show['next'] = $run->cms_content->list_arr($next_where, 'id', 1, 0, 1, 1, $extra);
        if($_show['next']){
            $_show['next'] = current($_show['next']);
            $run->cms_content->format($_show['next'], $mid, $dateformat);
        }
	}else{
        $_show['prev'] = $_show['next'] = array();
    }

    // hook block_global_show_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $_show, $life);
    }

	// hook block_global_show_after.php

	return $_show;
}

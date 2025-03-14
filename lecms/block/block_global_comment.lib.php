<?php
defined('ROOT_PATH') || exit;

/**
 * 评论页模块，某内容的所有评论(comment.htm评论页使用)
 * @param int pagenum 每页显示条数
 * @param string dateformat 时间格式
 * @param int humandate 人性化时间显示 默认开启 (开启: 1 关闭: 0)
 * @param int orderway 降序(-1),升序(1)
 * @param int pageoffset 分页显示偏移量
 * @param int pageoffset_mobile 手机端分页显示偏移量
 * @param int showmaxpage 最多显示多少页
 * @param string page_function 分页函数，xiunophp/ext/paginator.class.php
 * @param int life 缓存时间
 * @return array
 */
function block_global_comment($conf) {
	global $run, $_show;

	// hook block_global_comment_before.php

	$pagenum = empty($conf['pagenum']) ? 20 : max(1, (int)$conf['pagenum']);
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$humandate = isset($conf['humandate']) ? ($conf['humandate'] == 1 ? TRUE : FALSE) : TRUE;
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
    $pageoffset = _int($conf, 'pageoffset', 5);
    $pageoffset_mobile = _int($conf, 'pageoffset_mobile', 0);
    if($pageoffset_mobile && is_mobile()){
        $pageoffset = $pageoffset_mobile;
    }
    $showmaxpage = _int($conf, 'showmaxpage', 0);
    $page_function = empty($conf['page_function']) ? 'pages' : $conf['page_function'];
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $extra = array('block_name'=>'block_global_comment');
    $page = max(1, intval(R('page')));

    $mid = &$run->_var['mid'];
    if($mid == 1){
        $id = &$_show['cid'];
    }else{
        $id = &$_show['id'];
    }
    $cache_params = array($mid, $id, $page, $pagenum, $dateformat, $humandate, $orderway, $pageoffset, $pageoffset_mobile, $showmaxpage, $page_function);
    // hook block_global_comment_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('global_comment'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }


	if($mid == 1){
        // 格式化
        $run->category->format($run->_var);
        $_show['comments'] = $run->cms_content_comment->find_count(array('mid'=>1, 'id'=>$run->_var['cid']));
    }else{
        // 格式化
        $run->cms_content->format($_show, $mid, $dateformat);
    }

    // 分页相关
    $total = &$_show['comments'];
    $maxpage = max(1, ceil($total/$pagenum));

    //最大页数控制（超出进入404页面）
    if( $page > $maxpage || ($showmaxpage && $page > $showmaxpage)){core::error404();}

    //只显示最大指定页数
    if($showmaxpage && $maxpage > $showmaxpage){
        $maxpage = $showmaxpage;
    }

    $page = min($maxpage, $page);
    $_show['pages'] = paginator::$page_function($page, $maxpage, $run->cms_content->comment_url($run->_var['cid'], $id, TRUE), $pageoffset);

    // hook block_global_comment_list_arr_before.php

	// 获取评论列表
	$_show['list'] = $run->cms_content_comment->list_arr(array('mid' => $mid, 'id' => $id), 'dateline', $orderway, ($page-1)*$pagenum, $pagenum, $total, $extra);

    // hook block_global_comment_list_arr_after.php

    $xuhao = 1;
    $reply_key = array();
    $floor = ($page - 1)* $pagenum + 1;
	foreach($_show['list'] as &$v) {
		$run->cms_content_comment->format($v, $dateformat, $humandate);
        if($v['reply_commentid']) $reply_key[$v['commentid']] = $v['reply_commentid'];

        $v['floor'] = $floor++;
        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_global_comment_foreach_after.php
	}

    if($reply_key){
        $reply_list_arr = $run->cms_content_comment->mget($reply_key);
        foreach ($reply_key as $commentid=>$reply_commentid){
            if( isset($_show['list']['cms_comment-commentid-'.$commentid]) && isset($reply_list_arr['cms_comment-commentid-'.$reply_commentid]) ){
                //格式化回复的评论信息
                $reply_comment = $reply_list_arr['cms_comment-commentid-'.$reply_commentid];
                $run->cms_content_comment->format($reply_comment, $dateformat, $humandate);

                $_show['list']['cms_comment-commentid-'.$commentid]['reply_comment'] = $reply_comment;
                $_show['list']['cms_comment-commentid-'.$commentid]['reply_comment_content'] = $reply_comment['content'];    //兼容旧版本的~~~
            }
            // hook block_global_comment_reply_foreach_after.php
        }
    }

    // hook block_global_comment_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $_show, $life);
    }

	// hook block_global_comment_after.php

	return $_show;
}

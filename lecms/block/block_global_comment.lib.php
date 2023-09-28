<?php
defined('ROOT_PATH') || exit;

/**
 * 评论页模块，某内容的所有评论(comment.htm评论页使用)
 * @param int pagenum 每页显示条数
 * @param string dateformat 时间格式
 * @param int humandate 人性化时间显示 默认开启 (开启: 1 关闭: 0)
 * @param int orderway 降序(-1),升序(1)
 * @param int pageoffset 分页显示偏移量
 * @param int showmaxpage 最多显示多少页
 * @param string page_function 分页函数，xiunophp/ext/paginator.class.php
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
    $showmaxpage = _int($conf, 'showmaxpage', 0);
    $page_function = empty($conf['page_function']) ? 'pages' : $conf['page_function'];
    $extra = array('block_name'=>'block_global_comment');
    // hook block_global_comment_conf_after.php

	$id = &$_show['id'];
	$mid = &$run->_var['mid'];

	// 排除单页模型
	if($mid == 1) return FALSE;

	// 格式化
	$run->cms_content->format($_show, $mid, $dateformat);

	// 分页相关
	$total = &$_show['comments'];
	$maxpage = max(1, ceil($total/$pagenum));
    if($showmaxpage && $maxpage > $showmaxpage){
        $maxpage = $showmaxpage;
    }
	$page = min($maxpage, max(1, (int) R('page')));
	$_show['pages'] = paginator::$page_function($page, $maxpage, $run->cms_content->comment_url($run->_var['cid'], $id, TRUE), $pageoffset);

    // hook block_global_comment_list_arr_before.php

	// 获取评论列表
	$_show['list'] = $run->cms_content_comment->list_arr(array('mid' => $mid, 'id' => $id), 'dateline', $orderway, ($page-1)*$pagenum, $pagenum, $total, $extra);

    // hook block_global_comment_list_arr_after.php

    $xuhao = 1;
    $reply_key = array();
	foreach($_show['list'] as &$v) {
		$run->cms_content_comment->format($v, $dateformat, $humandate);
        $v['reply_comment_content'] = '';
        if($v['reply_commentid']) $reply_key[$v['commentid']] = $v['reply_commentid'];

        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_global_comment_foreach_after.php
	}

    if($reply_key){
        $reply_list_arr = $run->cms_content_comment->mget($reply_key);
        foreach ($reply_key as $commentid=>$reply_commentid){
            if( isset($_show['list']['cms_comment-commentid-'.$commentid]) && isset($reply_list_arr['cms_comment-commentid-'.$reply_commentid]) ){
                $_show['list']['cms_comment-commentid-'.$commentid]['reply_comment_content'] = $reply_list_arr['cms_comment-commentid-'.$reply_commentid]['content'];
            }
            // hook block_global_comment_reply_foreach_after.php
        }
    }

	// hook block_global_comment_after.php

	return $_show;
}

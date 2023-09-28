<?php
defined('ROOT_PATH') || exit;

/**
 * 评论列表模块 (基本都是在内容页使用、单页分类也可以使用 类似留言板功能)
 * @param int pagenum 每页显示条数
 * @param int firstnum 首次显示条数 (有利于SEO)
 * @param string dateformat 时间格式
 * @param int humandate 人性化时间显示 默认开启 (开启: 1 关闭: 0)
 * @param int orderway 降序(-1),升序(1)
 * @return array
 */
function block_comment($conf) {
	global $run, $_show;

	// hook block_comment_before.php

	$pagenum = empty($conf['pagenum']) ? 20 : max(1, (int)$conf['pagenum']);
	$firstnum = empty($conf['firstnum']) ? $pagenum : max(1, (int)$conf['firstnum']);
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$humandate = isset($conf['humandate']) ? ($conf['humandate'] == 1 ? 1 : 0) : 1;
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
    $extra = array('block_name'=>'block_comment');
    // hook block_comment_conf_after.php

	$cid = &$run->_var['cid'];
    $mid = &$run->_var['mid'];
    if( isset($_show) ){
        if($mid < 2){return false;}
        $id = &$_show['id'];
    }else{
        $id = $cid;
    }

	// 内容页如果无评论则不继续执行
	if(isset($_show['comments']) && empty($_show['comments'])) return FALSE;

	$where = array('mid' => $mid, 'id' => $id);
    // hook block_comment_where_after.php

	// 获取评论列表，这里用时间，而不是id排序，用来兼容评论审核插件
    $list_arr = $run->cms_content_comment->list_arr($where, 'dateline', $orderway, 0, $firstnum, $firstnum, $extra);

	$reply_key = array();
    $xuhao = 1;
	foreach($list_arr as &$v) {
		$run->cms_content_comment->format($v, $dateformat, $humandate);
        $v['reply_comment_content'] = '';
        if($v['reply_commentid']) $reply_key[$v['commentid']] = $v['reply_commentid'];

        $v['xuhao'] = $xuhao;
        $xuhao++;
        // hook block_comment_foreach_after.php
	}

	if($reply_key){
        $reply_list_arr = $run->cms_content_comment->mget($reply_key);
        foreach ($reply_key as $commentid=>$reply_commentid){
            if( isset($list_arr['cms_comment-commentid-'.$commentid]) && isset($reply_list_arr['cms_comment-commentid-'.$reply_commentid]) ){
                $list_arr['cms_comment-commentid-'.$commentid]['reply_comment_content'] = $reply_list_arr['cms_comment-commentid-'.$reply_commentid]['content'];
            }
        }
    }

	$end_arr = end($list_arr);
	$commentid = $end_arr['commentid'];
	$orderway = max(0, $orderway);
	$dateformat = base64_encode($dateformat);
	$next_url = $run->_cfg['weburl']."index.php?comment-json-cid-$cid-id-$id-commentid-$commentid-orderway-$orderway-pagenum-$pagenum-dateformat-".encrypt($dateformat)."-humandate-$humandate-ajax-1";
	$isnext = count($list_arr) < $firstnum ? 0 : 1;

	// hook block_comment_after.php

	return array('list' => $list_arr, 'next_url' => $next_url, 'isnext' => $isnext);
}

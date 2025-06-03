<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台评论相关控制器
 */
defined('ROOT_PATH') or exit;
// hook comment_control_start.php
class comment_control extends base_control{

	public function index() {
		// hook comment_control_index_before.php

		$_GET['cid'] = (int)R('cid');
		$_GET['id'] = (int)R('id');
		$this->_var = $this->category->get_cache($_GET['cid']);

		//分类不存在
		if(empty($this->_var)){
            core::error404();
        }
		//模型ID给到GET，方便block里面自动获取
        $_GET['mid'] = (int)$this->_var['mid'];

		if($this->_var['mid'] == 1){
            // 读取单页分类内容
            $cms_page = $this->cms_page->get($_GET['cid']);
            if( empty($cms_page) ){
                core::error404();
            }
            $_show = $this->_var;
            $_show['title'] = $this->_var['name'];
            $_show['intro'] = utf8::cutstr_cn($cms_page['content'], 200);
            // hook comment_control_index_seo_before.php

            // SEO 相关
            if( empty($_show['seo_title']) ){
                $this->_cfg['titles'] = $_show['name'].'-'.$this->_cfg['webname'];
            }else{
                $this->_cfg['titles'] = $_show['seo_title'].'-'.$this->_cfg['webname'];
            }
            if( empty($_show['seo_keywords']) ){
                $this->_cfg['seo_keywords'] = $_show['name'].','.$this->_cfg['webname'];
            }else{
                $this->_cfg['seo_keywords'] = $_show['seo_keywords'].','.$this->_cfg['webname'];
            }
            $this->_cfg['seo_description'] = empty($_show['seo_description']) ? $this->_cfg['webname'].'：'.$_show['name']: $_show['seo_description'];
        }else{
            // 初始模型表名
            $this->cms_content->table = 'cms_'.$this->_var['table'];

            // 读取内容
            $_show = $this->cms_content->get($_GET['id']);
            if(empty($_show['cid']) || $_show['cid'] != $_GET['cid']) core::error404();

            // hook comment_control_index_seo_before.php

            // SEO 相关
            if( empty($_show['seo_title']) ){
                if( empty($this->_var['seo_title']) ){
                    $this->_cfg['titles'] = $_show['title'].'-'.$this->_var['name'].'-'.$this->_cfg['webname'];
                }else{
                    $this->_cfg['titles'] = $_show['title'].'-'.$this->_var['seo_title'].'-'.$this->_cfg['webname'];
                }
            }else{
                $this->_cfg['titles'] = $_show['seo_title'].'-'.$this->_cfg['webname'];
            }
            if( empty($_show['seo_keywords']) ){
                if( empty($this->_var['seo_keywords']) ){
                    $this->_cfg['seo_keywords'] = $this->_cfg['webname'].','.$this->_var['name'].','.$_show['title'];
                }else{
                    $this->_cfg['seo_keywords'] = $this->_cfg['webname'].','.$this->_var['seo_keywords'].','.$_show['title'];
                }
            }else{
                $this->_cfg['seo_keywords'] = $_show['seo_keywords'].','.$this->_var['name'].','.$this->_cfg['webname'];
            }
            $this->_cfg['seo_description'] = empty($_show['seo_description']) ? $this->_cfg['webname'].'：'.$_show['intro']: $_show['seo_description'];
        }

        $page = (int)R('page','G');
        if( $page > 1 ){
            $this->_cfg['titles']  .= '-'.lang('page_current', array('page'=>$page));
        }

        // hook comment_control_index_seo_after.php

		$this->assign('cfg', $this->_cfg);
		$this->assign('cfg_var', $this->_var);

		$GLOBALS['run'] = &$this;
		$GLOBALS['_show'] = &$_show;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $tpl = 'comment.htm';

		// hook comment_control_index_after.php
		$this->display($tpl);
	}

	// 发表评论
	public function post() {
		// hook comment_control_post_before.php
        if( !empty($_POST) ) {
            $cid = (int)R('cid', 'P');
            $id = (int)R('id', 'P');
            $content = htmlspecialchars(trim(R('content', 'P')));
            $author = htmlspecialchars(trim(R('author', 'P')));
            $ip = ip2long(ip());

            if (empty($cid)) $this->message(0, lang('data_error'));
            empty($author) && $author = $this->_author;
            _strlen($author) > 20 && $this->message(0, lang('comment_author_than_20'));


            empty($content) && $this->message(0, lang('comment_content_no_empty'));
            _strlen($content) > 255 && $this->message(0, lang('comment_content_than_255'));

            // 关闭全站评论
            empty($this->_cfg['open_comment']) && $this->message(0, lang('comments_closed'));

            //未开启游客评论
            if (empty($this->_cfg['open_no_login_comment']) && empty($this->_uid)) {
                $this->message(0, lang('please_login'));
            }

            //开启评论验证码
            if (isset($this->_cfg['open_comment_vcode']) && !empty($this->_cfg['open_comment_vcode'])) {
                $vcode = htmlspecialchars(trim(R('vcode', 'P')));
                empty($vcode) && $this->message(0, lang('vcode_no_empty'));
                strtoupper($vcode) != _SESSION('vcode') && $this->message(0, lang('vcode_error'));
            }

            $cates = $this->category->get_cache($cid);

            if (empty($cates)) {
                $this->message(0, lang('data_error'));
            } elseif ($cates['mid'] == 1) {   //单页
                $id = $cid;
                $cms_data = array();
            } else {  //内容页
                if (empty($id)) $this->message(0, lang('data_error'));

                $this->cms_content->table = 'cms_' . $cates['table'];
                $cms_data = $this->cms_content->get($id);
                if (empty($cms_data)) {
                    $this->message(0, lang('data_no_exists'));
                }

                if (isset($cms_data['iscomment']) && $cms_data['iscomment']) {
                    $this->message(0, lang('content_comments_closed'));
                }
            }

            $comment_data = array(
                'mid' => $cates['mid'],
                'id' => $id,
                'uid' => $this->_uid,
                'author' => $author,
                'content' => $content,
                'ip' => $ip,
                'dateline' => $_ENV['_time'],
                'reply_commentid' => (int)R('reply_commentid', 'P')
            );
            // hook comment_control_post_create_before.php

            $maxid = $this->cms_content_comment->create($comment_data);
            if (!$maxid) {
                $this->message(0, lang('commented_failed'));
            }

            //内容评论，更新评论数和写入评论排序表 （分类评论用的少，不写入评论排序表）
            if ($cms_data) {
                $cms_data['comments'] += 1;

                //更新内容表的评论数
                if (!$this->cms_content->update($cms_data)) {
                    $this->message(0, lang('commented_failed'));
                }

                //更新评论排序表的评论数和最后评论时间
                $ret = $this->cms_content_comment_sort->set(array($cates['mid'], $id), array(
                    'cid' => $cid,
                    'comments' => $cms_data['comments'],
                    'lastdate' => $_ENV['_time'],
                ));
                if (!$ret) {
                    $this->message(0, lang('commented_failed'));
                }
            }

            // hook comment_control_post_after.php

            $this->message(1, lang('commented_successfully'));
        }else{
            exit;
        }
	}

	// 获取评论JSON（内容评论+分类评论【留言板】）
	public function json() {
		$cid = (int)R('cid');
		$id = (int)R('id');
		$commentid = (int)R('commentid');
        $floor = max(1, (int)R('floor'));

		$orderway = isset($_GET['orderway']) && $_GET['orderway'] == 1 ? 1 : -1;
		$pagenum = empty($_GET['pagenum']) ? 20 : max(1, (int)$_GET['pagenum']);
		$dateformat = empty($_GET['dateformat']) ? 'Y-m-d H:i:s' : decrypt($_GET['dateformat']);
		$humandate = isset($_GET['humandate']) ? ($_GET['humandate'] == 1 ? 1 : 0) : 1;

		if(empty($cid) || empty($id) || empty($commentid)) $this->message(0, lang('data_error'));

		$cates = $this->category->get_cache($cid);
        if(empty($cates)){
            $this->message(0, lang('data_error'));
        }

		// 获取评论列表
		$key = $orderway == 1 ? '>' : '<';
		$where = array('mid' => $cates['mid'], 'id' => $id, 'commentid' => array($key => $commentid));
		$ret = array();
		$ret['list_arr'] = $this->cms_content_comment->find_fetch($where, array('commentid' => $orderway), 0, $pagenum);
		foreach($ret['list_arr'] as &$v) {
			$this->cms_content_comment->format($v, $dateformat, $humandate);
            $v['floor'] = $floor++;
		}

		if($ret['list_arr']){
            $end_arr = end($ret['list_arr']);
            $commentid = $end_arr['commentid'];
            $orderway = max(0, $orderway);
            $dateformat = base64_encode($dateformat);
            $ret['next_url'] = $this->_cfg['weburl']."index.php?comment-json-cid-$cid-id-$id-commentid-$commentid-orderway-$orderway-pagenum-$pagenum-dateformat-".encrypt($dateformat)."-humandate-$humandate-floor-{$floor}-ajax-1";
            $ret['isnext'] = count($ret['list_arr']) < $pagenum ? 0 : 1;
        }else{
            $ret['list_arr'] = array();
            $ret['next_url'] = '';
            $ret['isnext'] = 0;
        }
        $ret['comment_url'] = $this->cms_content->comment_url($cid, $id);

		echo json_encode($ret);
		exit;
	}

	//生成验证码
    public function vcode(){
        // hook comment_control_vcode_before.php
        $vcode = new vcode();
        $name = R('name','G');
        $width = isset($_GET['width']) ? (int)$_GET['width'] : 0;
        $height = isset($_GET['height']) ? (int)$_GET['height'] : 0;
        // hook comment_control_vcode_after.php
        return $vcode->get_vcode($name, $width, $height);
    }

	// hook comment_control_after.php
}

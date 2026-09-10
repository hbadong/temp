<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-10-09
 * Time: 15:21
 * Description: 个人中心控制器
 */

defined('ROOT_PATH') or exit;
// hook my_control_start.php
class my_control extends base_control {
    public $_navs = array();	// 个人中心导航

	function __construct() {
        parent::__construct();

        // hook my_control_construct_before.php

        if( !isset($this->_cfg['open_user']) || empty($this->_cfg['open_user']) ){//未开启用户功能
            $this->message(0, lang('open_user_0'), $this->_cfg['weburl']);
        }elseif ( empty($this->_uid) ){
            $this->message(0, lang('please_login'), $this->urls->user_url('login','user'));
        }

        // 检查用户组权限
        $err = $this->check_user_group();
        if(!$err){
            // 初始化导航数组
            $this->init_navigation();

            $this->assign('_navs', $this->_navs);
        }

        // hook my_control_construct_after.php
	}

    // 检查用户组权限
    protected function check_user_group() {
        // hook my_control_check_user_group_before.php
        if($this->_group['groupid'] == 6){
            $this->user->user_token_logout(0);
            $this->message(0, lang('please_waiting_verification'), $this->_cfg['weburl']);
        }elseif ($this->_group['groupid'] == 7){
            $this->user->user_token_logout(0);
            $this->message(0, lang('dis_login'), $this->_cfg['weburl']);
        }
        // hook my_control_check_user_group_after.php
        return '';
    }

    // 初始化导航数组
    public function init_navigation() {
        // hook my_control_init_navigation_before.php

        $this->_navs['my'] = array(
            'title' => lang('my_center'),
            'icon' => 'fa fa-user-circle',
            'href' => $this->urls->user_url('index','my'),
            'target' => '_self',
            'child' =>array(
                array('id'=>'my-index', 'title' => lang('my_index'), 'href' => $this->urls->user_url('index','my'), 'icon' => 'fa fa-user-circle fa-fw', 'target' => '_self', 'class' => ''),
                array('id'=>'my-profile', 'title' => lang('my_profile'), 'href' => $this->urls->user_url('profile','my'), 'icon' => 'fa fa-user-o fa-fw', 'target' => '_self', 'class' => ''),
                array('id'=>'my-password', 'title' => lang('my_password'), 'href' => $this->urls->user_url('password','my'), 'icon' => 'fa fa-key fa-fw', 'target' => '_self', 'class' => ''),
            ),
        );

        // hook my_control_init_navigation_my_after.php

        $this->_navs['content'] = array(
            'title' => lang('contents_mng'),
            'icon' => 'fa fa-list-alt',
            'href' => $this->urls->user_url('contents','my'),
            'target' => '_self',
            'child' =>array(
                array('id'=>'my-contents', 'title' => lang('my_contents'), 'href' => $this->urls->user_url('contents','my'), 'icon' => 'fa fa-newspaper-o fa-fw', 'target' => '_self', 'class' => ''),
                array('id'=>'my-comments', 'title' => lang('my_comments'), 'href' => $this->urls->user_url('comments','my'), 'icon' => 'fa fa-comments fa-fw', 'target' => '_self', 'class' => ''),
            ),
        );

        // hook my_control_init_navigation_content_after.php

        $this->_navs['logout'] = array(
            'title' => lang('logout'),
            'icon' => 'fa fa-sign-out',
            'href' => $this->urls->user_url('logout','my'),
            'target' => '_self',
            'child' => array(),
        );

        // hook my_control_init_navigation_after.php
    }

    //----------------------------------------------------以下是用户中心的一些基本操作

    // 个人中心主页
    public function index(){
        // hook my_control_index_before.php

        $this->_cfg['titles'] = lang('my_center').'_'.$this->_cfg['webname'];
        $this->_var['topcid'] = -1;

        $this->assign('cfg', $this->_cfg);
        $this->assign('cfg_var', $this->_var);

        $GLOBALS['run'] = &$this;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $this->display('user/my_index.htm');
    }

    //编辑资料
    public function profile(){
	    if( empty($_POST) ){
            // hook my_control_profile_before.php

            $this->_cfg['titles'] = lang('my_profile').'_'.$this->_cfg['webname'];
            $this->_var['topcid'] = -1;

            $this->assign('cfg', $this->_cfg);
            $this->assign('cfg_var', $this->_var);

            $GLOBALS['run'] = &$this;
            $_ENV['_theme'] = &$this->_cfg['theme'];

	        $this->display('user/my_profile.htm');
        }else{
            $post = array(
                'uid'=>$this->_uid,
                'author'=>htmlspecialchars(trim(R('author', 'P'))),
                'email'=>htmlspecialchars(trim(R('email', 'P'))),
                'mobile'=>htmlspecialchars(trim(R('mobile', 'P'))),
                'homepage'=>htmlspecialchars(trim(R('homepage', 'P'))),
                'intro'=>htmlspecialchars(trim(R('intro', 'P'))),
            );
            // hook my_control_profile_post_after.php
            if( $this->user->update($post) ){
                // hook my_control_profile_post_success.php
                $this->message(1, lang('edit_successfully'));
            }else{
                $this->message(0, lang('edit_failed'));
            }
        }
    }

    //修改密码
    public function password(){
        if( empty($_POST) ){
            // hook my_control_password_before.php

            $this->_cfg['titles'] = lang('my_password').'_'.$this->_cfg['webname'];
            $this->_var['topcid'] = -1;

            $login_url = $this->urls->user_url('login', 'user');
            $this->assign('login_url', $login_url);

            $this->assign('cfg', $this->_cfg);
            $this->assign('cfg_var', $this->_var);

            $GLOBALS['run'] = &$this;
            $_ENV['_theme'] = &$this->_cfg['theme'];

            $this->display('user/my_password.htm');
        }else{
            $oldpwd = htmlspecialchars(trim(R('oldpwd', 'P')));
            $newpwd = htmlspecialchars(trim(R('newpwd', 'P')));
            $renewpwd = htmlspecialchars(trim(R('renewpwd', 'P')));

            // hook my_control_password_post_after.php

            if( empty($oldpwd) ){
                $this->message(0, lang('old_pwd_no_empty'));
            }elseif ( empty($newpwd) ){
                $this->message(0, lang('new_pwd_no_empty'));
            }elseif ( $newpwd != $renewpwd ){
                $this->message(0, lang('new_pwd_inconsistent'));
            }elseif ( $err = $this->user->check_password($newpwd) ){
                $this->message(0, $err);
            }elseif( !$this->user->verify_password($oldpwd, $this->_user['salt'], $this->_user['password']) ) {
                $this->message(0, lang('old_pwd_error'));
            }

            // hook my_control_password_post_check_after.php

            $data['uid'] = $this->_uid;
            $data['password'] = $this->user->safe_password($newpwd, $this->_user['salt']);

            // hook my_control_password_post_data_after.php

            if( $this->user->update($data) ){
                //清除登录信息，重新登录
                $this->user->user_token_logout(0);

                // hook my_control_password_post_success.php
                $this->message(1, lang('edit_successfully'));
            }else{
                $this->message(0, lang('edit_failed'));
            }
        }
    }

    //我的内容列表
    public function contents(){
        if( empty($_POST) ) {
            // hook my_control_contents_before.php

            $mid = max(2, (int)R('mid'));

            $select = $this->models->get_models_html($mid, 'class="form-control"');

            $table = $this->models->get_table($mid);
            $this->cms_content->table = 'cms_'.$table;

            $where['uid'] = $this->_uid;
            // hook my_control_contents_where_after.php

            // 初始分页
            $pagenum = 10;
            $total = $this->cms_content->find_count($where);
            $maxpage = max(1, ceil($total/$pagenum));
            $page = min($maxpage, max(1, intval(R('page'))));
            $pages = paginator::pages_bootstrap($page, $maxpage, $this->urls->user_url('contents', 'my', true, array('mid'=>$mid))); //这里使用bootstrap风格
            $this->assign('pages', $pages);
            $this->assign('total', $total);

            $cms_arr = $this->cms_content->list_arr($where, 'id', -1, ($page-1)*$pagenum, $pagenum, $total);
            foreach($cms_arr as &$v) {
                $this->cms_content->format($v, $mid);
            }

            // hook my_control_contents_after.php

            $this->assign('cms_arr', $cms_arr);
            $this->assign('midselect', $select);
            $this->assign('mid', $mid);

            $this->_cfg['titles'] = lang('my_contents').'_'.$this->_cfg['webname'];
            $this->_var['topcid'] = -1;

            $this->assign('cfg', $this->_cfg);
            $this->assign('cfg_var', $this->_var);

            $GLOBALS['run'] = &$this;
            $_ENV['_theme'] = &$this->_cfg['theme'];

            $this->display('user/my_contents.htm');
        }else{
            $act = R('act','R');

            if($act == 'del'){
                $id = (int)R('id','P');
                $mid = max(2, (int)R('mid','P'));
                $models = $this->_cfg['table_arr'];
                $table = isset($models[$mid]) ? $models[$mid] : '';

                if( empty($id) || empty($mid) || empty($table) ){
                    $this->message(0, lang('data_error'));
                }else{
                    $this->cms_content->table = 'cms_'.$table;
                    $data = $this->cms_content->get($id);

                    if( empty($data) || $data['uid'] != $this->_uid){
                        $this->message(0, lang('data_error'));
                    }else{
                        $err = $this->cms_content->xdelete($table, $id, $data['cid']);
                        if( $err ){
                            $this->message(0, lang('delete_failed'));
                        }else{
                            // hook my_control_contents_post_del_success.php

                            $this->message(1, lang('delete_successfully'));
                        }
                    }
                }
            }

            // hook my_control_contents_post_after.php
        }
    }

    //我的评论列表
    public function comments(){
        if( empty($_POST) ){
            // hook my_control_comments_before.php

            $models = $this->_cfg['table_arr'];

            $where['uid'] = $this->_uid;
            // hook my_control_comments_where_after.php

            // 初始分页
            $pagenum = 10;
            $total = $this->cms_content_comment->find_count($where);
            $maxpage = max(1, ceil($total/$pagenum));
            $page = min($maxpage, max(1, intval(R('page'))));
            $pages = paginator::pages_bootstrap($page, $maxpage, $this->urls->user_url('comments', 'my', true)); //这里使用bootstrap风格
            $this->assign('pages', $pages);
            $this->assign('total', $total);

            // 获取评论列表和评论内容列表
            $cms_arr = array();
            $comment_arr = $this->cms_content_comment->list_arr($where, 'commentid', -1, ($page-1)*$pagenum, $pagenum, $total);
            foreach($comment_arr as $k=>&$v) {
                $this->cms_content_comment->format($v, 'Y-m-d H:i', 0);

                $id = $v['id'];
                $mid = $v['mid'];
                $table = isset($models[$mid]) ? $models[$mid] : '';
                if( !empty($table)  ){
                    if( !isset($cms_arr[$id]) ){
                        $this->cms_content->table = 'cms_'.$table;
                        $data = $this->cms_content->get($id);
                        if($data){
                            $this->cms_content->format($data, $mid);
                            $cms_arr[$id] = $data;
                        }else{
                            $this->cms_content_comment->delete($v['commentid']);    //内容不存在的，则评论也删除
                        }
                    }
                }else{
                    $this->cms_content_comment->delete($v['commentid']);    //模型不存在的，则评论也删除
                }
            }

            // hook my_control_comments_after.php

            $this->assign('comment_arr', $comment_arr);
            $this->assign('cms_arr', $cms_arr);

            $this->_cfg['titles'] = lang('my_comments').'_'.$this->_cfg['webname'];
            $this->_var['topcid'] = -1;

            $this->assign('cfg', $this->_cfg);
            $this->assign('cfg_var', $this->_var);

            $GLOBALS['run'] = &$this;
            $_ENV['_theme'] = &$this->_cfg['theme'];

            $this->display('user/my_comments.htm');
        }else{
            $act = R('act','R');

            if($act == 'del'){
                $commentid = (int)R('commentid','P');
                if( empty($commentid) ){
                    $this->message(0, lang('data_error'));
                }else{
                    $data = $this->cms_content_comment->get($commentid);
                    if( empty($data) || $data['uid'] != $this->_uid){
                        $this->message(0, lang('data_error'));
                    }else{
                        $mid = $data['mid'];
                        $models = $this->_cfg['table_arr'];
                        $table = isset($models[$mid]) ? $models[$mid] : '';
                        $cms_data = array();
                        if( $table ){
                            $this->cms_content->table = 'cms_'.$table;
                            $cms_data = $this->cms_content->get($data['id']);
                        }
                        if( $this->cms_content_comment->delete($commentid) ){
                            if($cms_data['comments'] > 0){
                                $cms_data['comments']--;
                                $this->cms_content->update($cms_data);
                            }
                            // hook my_control_comments_post_del_success.php

                            $this->message(1, lang('delete_successfully'));
                        }else{
                            $this->message(0, lang('delete_failed'));
                        }
                    }
                }
            }

            // hook my_control_comments_post_after.php
        }
    }

    //上传用户头像
    public function upload_avatar(){
        $cfg = $this->runtime->xget();

        $updir = 'upload/avatar/'.substr(sprintf("%09d", $this->_uid), 0, 3).'/';
        $config = array(
            'maxSize'=>$cfg['up_img_max_size'],
            'allowExt'=>$cfg['up_img_ext'],
            'upDir'=>ROOT_PATH,
        );
        //指定存放路径和文件名
        $extra = array(
            'dir'=>$updir,
            'fileNewName'=>$this->_uid.'.png'
        );

        // hook my_control_upload_avatar_config_after.php

        $up = new upload($config, 'upfile', false, $extra);
        $info = $up->getFileInfo();

        if($info['state'] == 'SUCCESS') {
            // hook my_control_upload_avatar_config_success_before.php

            $path = $info['path'];   //相对路径

            if($cfg['user_avatar_width'] && $cfg['user_avatar_height']){    //等比例缩放裁剪头像
                image::thumb(ROOT_PATH.$path, ROOT_PATH.$path, $cfg['user_avatar_width'], $cfg['user_avatar_height'], 4, 100);
            }

            //更新新的头像文件到数据表
            $this->user->update( array('uid'=>$this->_uid, 'avatar'=>$path) );

            //layui上传图片成功 返回数据
            $data = array(
                'err'=>0,
                'msg'=>lang('upload_successfully'),
                'data'=>array(
                    'src'=> $path ,
                    'path' => $path,
                    'title'=>substr($info['name'],0, -(strlen($info['ext'])+1)), //不含后缀名
                )
            );
            echo json_encode($data);
        }else{
            $res = array('err'=>1, 'msg'=>$info['state']);
            echo json_encode($res);
        }
        exit();
    }

    // 退出登录
    public function logout(){
        // hook my_control_logout_before.php
        $this->user->user_token_logout(0);

        $this->message(1, lang('logout_successfully'), $this->_cfg['weburl']);
    }

	// hook my_control_after.php
}

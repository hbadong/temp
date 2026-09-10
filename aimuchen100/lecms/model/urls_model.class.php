<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Time: 15:21
 * Description: URL生成（不含分类URL 内容URL  标签URL 评论URL）
 */
defined('ROOT_PATH') or exit;
//用户自定义模型， 主键必须是id
class urls extends model {
    private $data = array();		// 防止重复查询

	function __construct() {
		$this->table = '';			// 表名 (用户自定义表)
		$this->pri = array('id');	// 主键
		$this->maxid = 'id';		// 自增字段
	}

    // 暂时用些方法解决获取 cfg 值
    function __get($var) {
        if($var == 'cfg') {
            return $this->cfg = $this->runtime->xget();
        }else{
            return parent::__get($var);
        }
    }

    // 首页分页链接格式化
    public function index_url($mid = 2, $extra = array()) {
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        // hook urls_model_index_url_before.php

        if(empty($_ENV['_config']['lecms_parseurl'])) {
            return $this->cfg['weburl'].'index.php?index-index-mid-'.$mid.'-page-{page}'.$_ENV['_config']['url_suffix'];
        }else{
            // hook urls_model_index_url_lecms_parseurl_before.php
            if($mid > 2){
                return $this->cfg['weburl'].'index_'.$mid.'_{page}'.$_ENV['_config']['url_suffix'];
            }else{
                return $this->cfg['weburl'].'index_{page}'.$_ENV['_config']['url_suffix'];
            }
        }
    }

    //模型页URL链接格式
    public function model_url($table = 'table', $mid = 2, $page = FALSE, $extra = array()){
	    //runtime里面调用这个函数的时候 cfg 还没有
	    if(!isset($this->cfg)){
            $this->cfg = $extra['cfg'];
        }

        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        // hook urls_model_model_url_before.php

        if(empty($_ENV['_config']['lecms_parseurl'])) {
            $s = $page ? '-page-{page}' : '';
            return $this->cfg['weburl'].'index.php?model--mid-'.$mid.$s.$_ENV['_config']['url_suffix'];
        }else{
            // hook urls_model_model_url_lecms_parseurl_before.php
            $s = $page ? '/page_{page}' : '';
            return $this->cfg['weburl'].$table.$s.'/';
        }
    }

    // 搜索结果页链接格式化
    public function search_url($mid = 2, $keyword = '', $page = FALSE, $extra = array()) {
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        // hook urls_model_search_url_before.php

        if(empty($_ENV['_config']['lecms_parseurl'])) {
            $s = $page ? '-page-{page}' : '';
            return $this->cfg['weburl'].'index.php?search--mid-'.$mid.'-keyword-'.urlencode($keyword).$s.$_ENV['_config']['url_suffix'];
        }else{
            // hook urls_model_search_url_lecms_parseurl_before.php
            $s = $page ? '/page_{page}' : '';
            if($mid > 2) {
                return $this->cfg['weburl'].'search/mid_'.$mid.'/'.urlencode($keyword).$s.'/';
            }else{
                return $this->cfg['weburl'].'search/'.urlencode($keyword).$s.'/';
            }
        }
    }

    // 搜索页面链接格式化
    public function so_url($extra = array()) {
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        // hook urls_model_so_url_before.php

        if(empty($_ENV['_config']['lecms_parseurl'])) {
            return $this->cfg['weburl'].'index.php?search-so'.$_ENV['_config']['url_suffix'];
        }else{
            // hook urls_model_so_url_lecms_parseurl_before.php
            return $this->cfg['weburl'].'so'.$_ENV['_config']['url_suffix'];
        }
    }

    //用户模块相关URL，只涉及 user-xxx 或者 my-xxx
    public function user_url($action = 'index', $control = 'user', $page = false, $extra = array()){
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        $allow_control = array('user', 'my');
        // hook urls_model_user_url_before.php

        if(!in_array($control, $allow_control)){
            return '';
        }

        $s = '';
        if($page){
            $s .= $page ? '-page-{page}' : '';
        }
        // 附加参数
        if($extra) {
            foreach ($extra as $k=>$v){
                $s .= '-'.$k.'-'.$v;
            }
        }

        if(empty($_ENV['_config']['lecms_parseurl'])) {
            return $this->cfg['weburl'].'index.php?'.$control.'-'.$action.$s.$_ENV['_config']['url_suffix'];
        }else{
            // hook urls_model_user_url_lecms_parseurl_before.php
            return $this->cfg['weburl'].$control.'-'.$action.$s.$_ENV['_config']['url_suffix'];
        }
    }

    //热门标签URL
    public function hot_tag_url($extra = array()){
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        // hook urls_model_hot_tag_url_before.php

        if(empty($_ENV['_config']['lecms_parseurl'])) {
            return $this->cfg['weburl'].'index.php?tag-top'.$_ENV['_config']['url_suffix'];
        }else{
            // hook urls_model_hot_tag_url_lecms_parseurl_before.php
            return $this->cfg['weburl'].$this->cfg['link_tag_top'];
        }
    }

    // 全部标签URL
    public function tag_all_url($mid = 2, $page = FALSE, $extra = array()) {
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        // hook urls_model_tag_all_url_before.php

        $s = '';
        if(empty($_ENV['_config']['lecms_parseurl'])) {
            $mid > 2 && $s .= '-mid-'.$mid;
            $page && $s .= '-page-{page}';
            $url = $this->cfg['weburl'].'index.php?tag-all'.$s.$_ENV['_config']['url_suffix'];
        }else{
            // hook urls_model_tag_all_url_lecms_parseurl_before.php

            $mid > 2 && $s .= '/'.$mid;
            $page && $s = empty($s) ? '/{page}' : $s.'_{page}';
            $url = $this->cfg['weburl'].'tag_all'.$s.'/';
        }

        // hook urls_model_tag_all_url_after.php
        return $url;
    }

    // 属性内容链接格式化
    public function flag_url($mid = 2, $flag = 1, $page = FALSE, $extra = array()) {
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        // hook urls_model_flag_url_before.php

        if(empty($_ENV['_config']['lecms_parseurl'])) {
            $s = $page ? '-page-{page}' : '';
            return $this->cfg['weburl'].'index.php?flags--mid-'.$mid.'-flag-'.$flag.$s.$_ENV['_config']['url_suffix'];
        }else{
            // hook urls_model_flag_url_lecms_parseurl_before.php
            return $this->cfg['weburl'].'flags/'.($mid > 2 ? $mid.'_'.$flag : $flag).($page ? '/page_{page}' : '').'/';
        }
    }

    // 用户个人主页链接格式化
    public function space_url($uid = 0, $page = FALSE, $extra = array()) {
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        // hook urls_model_space_url_before.php
        if(empty($uid)){return '';}

        if(empty($_ENV['_config']['lecms_parseurl'])) {
            $s = $page ? '-page-{page}' : '';
            return $this->cfg['weburl'].'index.php?space--uid-'.$uid.$s.$_ENV['_config']['url_suffix'];
        }else{
            // hook urls_model_space_url_lecms_parseurl_before.php
            return $this->cfg['weburl'].$this->cfg['link_space_pre'].$uid.($page ? '/page_{page}' : '').$this->cfg['link_space_end'];
        }
    }

    //用户头像，存储方式 upload/avatar/用户文件夹/用户ID.png 或者 数据表avatar存储了路径，注意 可能会有浏览器缓存 到时没法实时显示最新头像
    public function user_avatar($uid = 0, $avatar_file = '', $extra = array()){
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        // hook urls_model_user_avatar_before.php
        if($uid){
            if($avatar_file && substr($avatar_file, 0, 14) != 'upload/avatar/'){
                $avatar = 'upload/avatar/'.$avatar_file;
            }else{
                $avatar = 'upload/avatar/'.substr(sprintf("%09d", $uid), 0, 3).'/'.$uid.'.png';
            }

            if( is_file(ROOT_PATH.$avatar) ){
                return $this->cfg['weburl'].$avatar;
            }else{
                if(isset($extra['user'])){
                    $user = $extra['user'];
                }else{
                    $user = $this->user->get($uid);
                }
                if(isset($user['avatar']) && $user['avatar'] && is_file(ROOT_PATH.$user['avatar'])){
                    return $this->cfg['weburl'].$user['avatar'];
                }
                return $this->cfg['weburl'].'static/img/avatar.png';
            }
        }else{
            return $this->cfg['weburl'].'static/img/avatar.png';
        }
    }

    // 附件下载，用的少，不做伪静态
    public function attach_url($mid = 0, $aid = 0, $extra = array()) {
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        // hook urls_model_attach_url_before.php

        return $this->cfg['weburl'].'index.php?attach--mid-'.$mid.'-id-'.$aid.$_ENV['_config']['url_suffix'];
    }

    // hook urls_model_after.php
}

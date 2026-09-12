<?php
defined('ROOT_PATH') or exit;

class cms_content extends model {
    private $data = array();		// 防止重复查询

    public $flag_arr = array(
        1=>'荐',2=>'热',3=>'头',4=>'精',5=>'幻'
    );
    public $flag_arr_en = array(
        1=>'Recommend',2=>'Hot',3=>'Headline',4=>'Choice',5=>'Slide'
    );

    public $_field_length = array(
        'title'=>200,
        'alias'=>80,
        'tags'=>255,
        'pic'=>255,
        'intro'=>255,
        'author'=>50,
        'source'=>100,
        'seo_title'=>255,
        'seo_keywords'=>255,
        'seo_description'=>255,
        'jumpurl'=>255,
        'show_tpl'=>80,
        // hook cms_content_model_field_length_after.php
    );

	function __construct() {
		$this->table = '';			// 内容表表名 比如 cms_article
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

	//属性多选框 $val值以英文逗号隔开
	public function flag_html($val = '', $lang = 'zh-cn', $split = '', $other = ''){
	    if($lang == 'zh-cn'){
            $flag_arr = $this->flag_arr;
        }else{
            $flag_arr = $this->flag_arr_en;
        }
        // hook cms_content_model_flag_html_before.php

	    return form::layui_loop('checkbox', 'flag', $flag_arr, $val, $split, $other);
    }

	// 格式化内容数组
	public function format(&$v, $mid, $dateformat = 'Y-m-d H:i', $titlenum = 0, $intronum = 0, $field_format = 0, $extra = array()) {
		// hook cms_content_model_format_before.php

		if(empty($v) || !is_array($v)) return FALSE;

		if( isset($v['dateline']) ){
            if($dateformat == 'human_date'){
                $v['date'] = human_date($v['dateline']);
            }else{
                $v['date'] = date($dateformat, $v['dateline']);
            }
        }else{
            !isset($v['date']) && $v['date'] = '';
        }

		$v['subject'] = $titlenum ? utf8::cutstr_cn($v['title'], $titlenum) : $v['title'];
        $intronum && $v['intro'] = utf8::cutstr_cn($v['intro'], $intronum);

        $v['url'] = $this->content_url($v, $mid);
        //$v['absolute_url'] = HTTP.$this->cfg['webdomain'].$v['url'];	//绝对URL地址（完整）

        //绝对URL地址
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){    //使用相对URL
            $v['absolute_url'] = HTTP.$this->cfg['webdomain'].$v['url'];
        }else{
            $v['absolute_url'] = $v['url'];
        }

        //标签
		$v['tags'] = _json_decode($v['tags']);
        $v['tag_arr'] = $v['flag_arr'] = array();
		if($v['tags']) {
			foreach($v['tags'] as $tagid=>$name) {
			    $tag_tmp = array('tagid'=>$tagid, 'name'=>$name);
				$v['tag_arr'][] = array('name'=>$name, 'url'=> $this->tag_url($mid, $tag_tmp), 'tagid'=>$tagid);
			}
		}

		//属性(基本用不上，因此加上参数开关)
        if(isset($extra['flags']) && !empty($extra['flags']) && $v['flags']){
            $flags_arr = explode(',', $v['flags']);
            $lang = APP_NAME == 'admin' ? $_ENV['_config']['admin_lang'] : $_ENV['_config']['lang'];
            if($lang == 'zh-cn'){
                $flag_arr = $this->flag_arr;
            }else{
                $flag_arr = $this->flag_arr_en;
            }
            foreach ($flags_arr as $flag){
                isset($flag_arr[$flag]) AND $v['flag_arr'][$flag] = array('name'=>$flag_arr[$flag], 'url'=>$this->urls->flag_url($mid, $flag));
            }
        }

        //缩略图
        if( empty($v['pic']) ){
            $v['haspic'] = 0;
            $v['pic'] = $v['pic_thumb'] = $this->cfg['webdir'].'static/img/nopic.gif';
            $v['pic_url'] = $this->cfg['weburl'].'static/img/nopic.gif';    //完整的图片地址
        }else{
            $v['haspic'] = 1;
            if( substr($v['pic'], 0, 2) != '//' && substr($v['pic'], 0, 4) != 'http' ){ //不是外链图片
                $v['pic_thumb'] = $this->cfg['webdir'].image::thumb_name($v['pic']);
                $v['pic_url'] = $this->cfg['weburl'].$v['pic'];
                $v['pic'] = $this->cfg['webdir'].$v['pic'];
            }else{
                $v['pic_thumb'] = $v['pic'];
                $v['pic_url'] = $v['pic'];
            }
        }
        //跳转URL
        if($v['jumpurl']){$v['url'] = $v['jumpurl'];}

        //用户主页URL和用户头像
        if($v['uid']){
            $v['user_url'] = $this->urls->space_url($v['uid']);
            $v['avatar'] = $this->urls->user_avatar($v['uid']);
        }

        //格式化主表扩展字段的值
        if($field_format){
			$models_field = $this->get_model_fields_by_mid($mid);
            $models_field && $this->models_field->field_val_format($models_field, $v);
        }

		// hook cms_content_model_format_after.php
	}

	// 获取内容列表
	public function list_arr($where = array(), $orderby = 'id', $orderway = 1, $start = 0, $limit = 0, $total = 0, $extra = array()) {
        // hook cms_content_model_list_arr_before.php

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

        // hook cms_content_model_list_arr_after.php
		return $list_arr;
	}

    //发布或编辑内容时检查数据
    public function check_post($post = array(), $isadd = 1){
	    $content_min_len = isset($this->cfg['content_min_len']) ? (int)$this->cfg['content_min_len'] : 0;
        // hook cms_content_model_check_post_before.php
        if( empty($post['cid']) ){
            return lang('please_select_category');
        }elseif ( empty($post['title']) ){
            return lang('please_fill_title');
        }elseif ( isset($post['content']) && strlen($post['content']) < $content_min_len ){
            return lang('please_fill_content_over_5', array('length'=>$content_min_len));
        }elseif ( $isadd && isset($post['alias'] ) && $post['alias'] && $err_msg = $this->only_alias->check_alias(strtolower($post['alias']), 1) ){
            return $err_msg;
        }elseif ( isset($post['id']) && empty($post['id']) ){
            return lang('id_not_exists');
        }
        // hook cms_content_model_check_post_after.php
        return '';
    }

	//发布内容，增加了写入指定ID。有$id 用set，没有用create
    public function xadd($post = array(), $user = array(), $table = 'article'){
        // hook cms_content_model_xadd_before.php

        //火车头数据过滤
        if(isset($post['alias']) && $post['alias'] == '[db:别名]') $post['alias'] = '';
        if(isset($post['tags']) && $post['tags'] == '[db:标签]' ) $post['tags'] = '';
        if(isset($post['pic']) && $post['pic'] == '[db:缩略图]' ) $post['pic'] = '';
        if(isset($post['flag']) && $post['flag'] == '[db:属性]' ) $post['flag'] = array();
        if(isset($post['intro']) && $post['intro'] == '[db:摘要]' ) $post['intro'] = '';
        if(isset($post['author']) && $post['author'] == '[db:作者]' ) $post['author'] = '';
        if(isset($post['source']) && $post['source'] == '[db:来源]' ) $post['source'] = '';
        if(isset($post['views']) && $post['views'] == '[db:浏览量]' ) $post['views'] = 0;
        if(isset($post['seo_title']) && $post['seo_title'] == '[db:SEO标题]' ) $post['seo_title'] = '';
        if(isset($post['seo_keywords']) && $post['seo_keywords'] == '[db:SEO关键词]' ) $post['seo_keywords'] = '';
        if(isset($post['seo_description']) && $post['seo_description'] == '[db:SEO描述]' ) $post['seo_description'] = '';
        if(isset($post['jumpurl']) && $post['jumpurl'] == '[db:跳转URL]' ) $post['jumpurl'] = '';
        if(isset($post['isremote']) && $post['isremote'] == '[db:远程图片本地化]' ) $post['isremote'] = 0;
        if(isset($post['iscomment']) && $post['iscomment'] == '[db:禁止评论]' ) $post['iscomment'] = 0;
        if(isset($post['content']) && $post['content'] == '[db:内容]' ) $post['content'] = '';
        if(isset($post['show_tpl']) && $post['show_tpl'] == '[db:内容模板]' ) $post['show_tpl'] = '';
		//处理发布时间
        if(isset($post['dateline']) && $post['dateline'] == '[db:发布时间]' ) unset($post['dateline']);
        //可以传递 2024-07-10 这种格式
        if( isset($post['dateline']) && $post['dateline'] && !is_numeric($post['dateline']) ){
            $post['dateline'] = strtotime($post['dateline']);
			if(empty($post['dateline'])){unset($post['dateline']);}
        }

        // hook cms_content_model_xadd_post_after.php

        //有没传递ID过来？
        if( isset($post['id']) ){
            if( empty($post['id']) ){
                unset($post['id']);
                $id = 0;
            }else{
                $id = (int)$post['id'];
            }
        }else{
            $id = 0;
        }

        $err = $this->check_post($post);
        // hook cms_content_model_xadd_check_post_after.php
        if($err){
            return array('err'=>1 ,'msg'=>$err);
        }

        $isremote = isset($post['isremote']) ? (int)$post['isremote'] : 0;
        $cid = isset($post['cid']) ? (int)$post['cid'] : 0;
        //优先使用传递过来的uid
        if(isset($post['uid'])){
            $uid = max(1, (int)$post['uid']);
            $user = array();
        }elseif (isset($user['uid'])){
            $uid = max(1, (int)$user['uid']);
        }else{
            $uid = 1;
        }
        $title = isset($post['title']) ? trim(strip_tags($post['title'])) : '';
        $contentstr = isset($post['content']) ? trim($post['content']) : '';
        $intro = isset($post['intro']) ? trim($post['intro']) : '';
        $tagstr = isset($post['tags']) ? trim($post['tags'], ", \t\n\r\0\x0B") : '';
        $flags = isset($post['flag']) ? (is_array($post['flag']) ? $post['flag'] : explode(',',$post['flag'])) : array();
        $author =isset($post['author']) ? trim($post['author']) : '';
        $alias = isset($post['alias']) ? strtolower(trim($post['alias'])) : '';
        $auto_pic = isset($this->cfg['auto_pic']) ? (int)$this->cfg['auto_pic'] : 0;    //自动提取缩略图
        $open_title_check = isset($this->cfg['open_title_check']) ? (int)$this->cfg['open_title_check'] : 0;    //是否开启标题重复检查

        if($alias && preg_match("/^\d+_\d+$/u",$alias)){    // 数字_数字 会和没有别名的 别名型URL冲突，导致404页面
            return array('err'=>1 ,'msg'=>lang('alias_error_number_and_number'));
        }

        // hook cms_content_model_xadd_info_after.php

        $intro = auto_intro($intro, $contentstr);

        empty($user) AND $user = $this->user->get($uid);
        if( empty($user) ){
            return array('err'=>1 ,'msg'=>lang('user_not_exists'));
        }
        empty($author) AND $author = empty($user['author'] ) ? $user['username'] : $user['author'];
        // hook cms_content_model_xadd_user_after.php

        //分类检查
        $categorys = $this->category->get($cid);
        if(empty($categorys)){
            return array('err'=>1 ,'msg'=>lang('category_not_exists'));
        }
        $mid = (int)$categorys['mid'];
        $models = $this->models->get($mid);
        if(empty($models) || $models['tablename'] != $table){
            return array('err'=>1 ,'msg'=>lang('cid_error'));
        }

        // hook cms_content_model_xadd_category_after.php

        $this->table = 'cms_'.$table;
        $this->cms_content_data->table = 'cms_'.$table.'_data';
        $this->cms_content_views->table = 'cms_'.$table.'_views';

        if($open_title_check && $this->find_fetch(array('title'=>$title), array('id' => 1), 0, 1)){
            return array('err'=>1 ,'msg'=>lang('title_exists'));
        }

        $cms_content = array(
            'cid' => $cid,
            'title' => $title,
            'alias' => $alias,
            'tags' => '',
            'intro' => $intro,
            'pic' => isset($post['pic']) ? trim($post['pic']) : '',
            'uid' => $uid,
            'author' => $author,
            'source' => isset($post['source']) ? trim($post['source']) : '',
            'dateline' => isset($post['dateline']) ? $post['dateline'] : $_ENV['_time'],
            'lasttime' => isset($post['lasttime']) ? $post['lasttime'] : $_ENV['_time'],
            'ip' => isset($post['ip']) ? $post['ip'] : ip2long($_ENV['_ip']),
            'imagenum' => 0,
            'filenum' => 0,
            'iscomment' => isset($post['iscomment']) ? (int)$post['iscomment'] : 0,
            'comments' => 0,
            'flags' => implode(',', $flags),
            'seo_title' => isset($post['seo_title']) ? trim(strip_tags($post['seo_title'])) : '',
            'seo_keywords' => isset($post['seo_keywords']) ? trim(strip_tags($post['seo_keywords'])) : '',
            'seo_description' => isset($post['seo_description']) ? trim(strip_tags($post['seo_description'])) : '',
            'jumpurl' => isset($post['jumpurl']) ? trim($post['jumpurl']) : '',
        );
        //渐进式内容站点隔离：文章表记录站点归属
        if($this->table == 'cms_article'){
            $cms_content['site_id'] = isset($post['site_id']) ? max(1, (int)$post['site_id']) : 1;
        }
        if(isset($post['show_tpl']) && $post['show_tpl']){
            $cms_content['show_tpl'] = $post['show_tpl'];
        }
        // hook cms_content_model_xadd_cms_content_after.php

        $cms_content_data = array(
            'content'=>$contentstr,
        );

        // hook cms_content_model_xadd_cms_content_data_after.php

        $endstr = '';
        $this->cms_content_attach->table = 'cms_'.$table.'_attach';
        if($isremote) {
            // hook cms_content_model_xadd_isremote_before.php
            $endstr .= $this->get_remote_img($table, $cms_content_data['content'], $uid, $cid);
        }

        // 计算图片数，和非图片文件数
        $imagenum = $this->cms_content_attach->find_count(array('id'=>0, 'uid'=>$uid, 'isimage'=>1));
        $filenum = $this->cms_content_attach->find_count(array('id'=>0, 'uid'=>$uid, 'isimage'=>0));
        if($imagenum || $filenum){
            $cms_content['imagenum'] = $imagenum;
            $cms_content['filenum'] = $filenum;
        }

        // 如果缩略图为空，并且附件表有图片，开启自动缩略图，则将第一张图片设置为缩略图
        if(empty($cms_content['pic']) && $imagenum && $auto_pic) {
            $cms_content['pic'] = $this->auto_pic($table, $uid, 0, $models);
        }

        //开启自动缩略图，匹配内容里面的图片（这里不生成缩略图小图，直接是完整的图片网址）
        if(empty($cms_content['pic']) && $auto_pic){
            $pattern="/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/";
            preg_match_all($pattern, $contentstr, $match);
            if( isset($match[1]) && isset($match[1][0]) ){
                $match_pic = $match[1][0];
                if(substr($match_pic, 0, 2) == '..'){
                    $match_pic = substr($match_pic, 2);
                }
                $webdir_len = strlen($this->cfg['webdir']);
                if(substr($match_pic, 0, $webdir_len) == $this->cfg['webdir']){
                    $cms_content['pic'] = substr($match_pic, $webdir_len);
                }else{
                    $cms_content['pic'] = $match[1][0];
                }
            }
        }
        //处理缩略图 end

        //标签处理 start
        $tagdatas = $tags = array();
        if($tagstr){
            $tags_arr = explode(',', $tagstr);
            $tags_arr = array_unique($tags_arr);    //去重
            $tags_arr = array_filter($tags_arr);    //去掉空
            $this->cms_content_tag->table = 'cms_'.$table.'_tag';
            for($i = 0; isset($tags_arr[$i]) && $i < 8; $i++) { //只保留8个标签
                $name = $this->cms_content_tag->_tagformat($tags_arr[$i]);
                if($name){
                    $tagdata = $this->cms_content_tag->find_fetch(array('name'=>$name), array(), 0, 1); //看看是否已经存在
                    if($tagdata) {
                        $tagdata = current($tagdata);
                    }else{
                        $tag_post = array('name'=>$name, 'count'=>0, 'content'=>'');
                        // hook cms_content_model_xadd_tag_post_after.php
                        $tagid = $this->cms_content_tag->create($tag_post);
                        if(!$tagid){
                            DEBUG && log::le_log($tag_post, 'cms_content_xadd_error');
                            return array('err'=>1 ,'msg'=>lang('write_tag_table_failed'));
                        }
                        $tagdata = $this->cms_content_tag->get($tagid);
                    }
                    $tagdata['count']++;
                    $tagdatas[] = $tagdata;

                    if( _strlen(_json_encode($tags)) > 500){    //主表tags长度限制
                        break;
                    }
                    $tags[$tagdata['tagid']] = $tagdata['name'];
                }
            }
        }
        if($tags){
            $cms_content['tags'] = _json_encode($tags);
        }
        //标签处理 end

        //有无指定ID？ 写入主表
        if($id){
            if(!$this->set($id, $cms_content)) {$id = 0;}
        }else{
            $id = $this->create($cms_content);
        }

        if($id) {
            $cms_content['id'] = $id;
            // hook cms_content_model_xadd_cms_content_success.php
        }else{
            // hook cms_content_model_xadd_cms_content_failed.php
            DEBUG && log::le_log($cms_content, 'cms_content_xadd_error');

            return array('err'=>1 ,'msg'=>lang('write_content_table_failed'));
        }

        //附表数据
        if($this->cms_content_data->set_cms_content_data($id, $cms_content_data, $table)) {
            // hook cms_content_model_xadd_cms_content_data_success.php
        }else{
            // hook cms_content_model_xadd_cms_content_data_failed.php
            DEBUG && log::le_log($cms_content_data, 'cms_content_xadd_error');

            $this->delete($id);    //删除主表数据
            return array('err'=>1 ,'msg'=>lang('write_content_data_table_failed'));
        }

        // 写入查看数表
        $cms_content_views = array(
            'cid'=>$cid,
            'views'=>isset($post['views']) ? (int)$post['views'] : 0,
        );
        // hook cms_content_model_xadd_cms_content_views_after.php

        if(!$this->cms_content_views->set_cms_content_views($id, $cms_content_views, $table)) {
            $this->delete($id);    //删除主表数据
            $this->cms_content_data->delete_cms_content_data($id, $table);   //删除附表数据

            DEBUG && log::le_log($cms_content_views, 'cms_content_xadd_error');
            return array('err'=>1 ,'msg'=>lang('write_content_views_table_failed'));
        }

        // 写入全站唯一别名表
        if($alias && !$this->only_alias->create(array('alias'=> $alias ,'mid' => $mid, 'cid' => $cid, 'id' => $id))) {
            $this->update(array('id'=>$id, 'alias'=>''));
        }

        //标签表和标签内容表对应
        if($tagdatas){
            $this->cms_content_tag_data->table = 'cms_'.$table.'_tag_data';
            foreach($tagdatas as $tagdata) {
                $this->cms_content_tag->update($tagdata);
                $this->cms_content_tag_data->set(array($tagdata['tagid'], $id), array('id'=>$id));
            }
        }

        // 写入内容属性标记表,set里面的data要完整，避免在开启缓存的时候写入数据不完整，导致读取不到完整数据
        if($flags){
            $this->cms_content_flag->table = 'cms_'.$table.'_flag';
            foreach($flags as $flag) {
                $this->cms_content_flag->set(array($flag, $id), array('flag'=>$flag,'cid'=>$cid,'id'=>$id));
            }
        }

        // 更新附件归宿 cid 和 id
        if($imagenum || $filenum) {
            $this->cms_content_attach->find_update(array('id'=>0, 'uid'=>$uid), array('cid'=>$cid, 'id'=>$id));
        }

        // 更新用户发布的内容条数
        $user['contents']++;
        $this->user->update($user);

        // 更新分类的内容条数
        $categorys['count']++;
        $this->category->update($categorys);
        $this->category->update_cache($cid);

        //返回数据
        $return_data = array(
            'cid'=>$cid,
            'id'=>$id,
            'alias'=>$alias,
            'mid'=>$mid
        );

        // hook cms_content_model_xadd_after.php

        return array('err'=>0, 'msg'=>lang('fabu_successfully').$endstr, 'data'=>$return_data);
    }

    //编辑内容
    public function xedit($post = array(), $user = array(), $table = 'article'){
        // hook cms_content_model_xedit_before.php
        if( !isset($post['id']) ){
            return array('err'=>1 ,'msg'=>lang('id_not_exists'));
        }
        $isremote = isset($post['isremote']) ? (int)$post['isremote'] : 0;

        $err = $this->check_post($post, 0);
        // hook cms_content_model_xedit_check_post_after.php
        if($err){
            return array('err'=>1 ,'msg'=>$err);
        }

        $this->table = 'cms_'.$table;
        $this->cms_content_data->table = 'cms_'.$table.'_data';

        $id = (int)$post['id'];
        $olddata = $this->cms_content->get($id);
        if(empty($olddata)){
            return array('err'=>1 ,'msg'=>lang('data_no_exists'));
        }

        $cid = isset($post['cid']) ? (int)$post['cid'] : 0;
        //优先使用传递过来的uid
        if(isset($post['uid'])){
            $uid = max(1, (int)$post['uid']);
            $user = array();
        }elseif (isset($user['uid'])){
            $uid = max(1, (int)$user['uid']);
        }else{
            $uid = 1;
        }
        $title = isset($post['title']) ? trim(strip_tags($post['title'])) : '';
        $contentstr = isset($post['content']) ? trim($post['content']) : '';
        $intro = isset($post['intro']) ? trim($post['intro']) : '';
        $tagstr = isset($post['tags']) ? trim($post['tags'], ", \t\n\r\0\x0B") : '';
        $flags = isset($post['flag']) ? (array)$post['flag'] : array();
        $author =isset($post['author']) ? trim($post['author']) : '';
        $alias = isset($post['alias']) ? strtolower(trim($post['alias'])) : '';
        $auto_pic = isset($this->cfg['auto_pic']) ? (int)$this->cfg['auto_pic'] : 0;    //自动提取缩略图
        $open_title_check = isset($this->cfg['open_title_check']) ? (int)$this->cfg['open_title_check'] : 0;    //是否开启标题重复检查

        if($alias && preg_match("/^\d+_\d+$/u",$alias)){    // 数字_数字 会和没有别名的 别名型URL冲突，导致404页面
            return array('err'=>1 ,'msg'=>lang('alias_error_number_and_number'));
        }

        // hook cms_content_model_xedit_info_after.php

        if($open_title_check && $title != $olddata['title'] && $this->find_fetch(array('title'=>$title), array('id' => 1), 0, 1)){
            return array('err'=>1 ,'msg'=>lang('title_exists'));
        }

        $intro = auto_intro($intro, $contentstr);
        
        empty($user) AND $user = $this->user->get($uid);
        if( empty($user) ){
            return array('err'=>1 ,'msg'=>lang('user_not_exists'));
        }
        empty($author) AND $author = empty($user['author'] ) ? $user['username'] : $user['author'];
        // hook cms_content_model_xedit_user_after.php

        //分类检查
        $categorys = $this->category->get($cid);
        if(empty($categorys)){
            return array('err'=>1 ,'msg'=>lang('category_not_exists'));
        }
        $mid = (int)$categorys['mid'];
        $models = $this->models->get($mid);
        if(empty($models) || $models['tablename'] != $table){
            return array('err'=>1 ,'msg'=>lang('cid_error'));
        }

        // hook cms_content_model_xedit_category_after.php

        // 检测别名是否能用
        $alias_old = $olddata['alias'];
        if($alias && $alias != $alias_old && $err_msg = $this->only_alias->check_alias($alias, 1)) {
            return array('err'=>1 ,'msg'=>$err_msg);
        }

        $cms_content = array(
            'id' => $id,
            'cid' => $cid,
            'title' => $title,
            'alias' => $alias,
            'tags' => '',
            'intro' => $intro,
            'pic' => isset($post['pic']) ? trim($post['pic']) : '',
            'uid' => $uid,
            'author' => $author,
            'source' => isset($post['source']) ? trim($post['source']) : '',
            'lasttime' => isset($post['lasttime']) ? $post['lasttime'] : $_ENV['_time'],
            'ip' => isset($post['ip']) ? $post['ip'] : ip2long($_ENV['_ip']),
            'imagenum' => $olddata['imagenum'],
            'filenum' => $olddata['filenum'],
            'iscomment' => isset($post['iscomment']) ? (int)$post['iscomment'] : 0,
            'comments' => (int)$olddata['comments'],
            'flags' => implode(',', $flags),
            'seo_title' => isset($post['seo_title']) ? trim(strip_tags($post['seo_title'])) : '',
            'seo_keywords' => isset($post['seo_keywords']) ? trim(strip_tags($post['seo_keywords'])) : '',
            'seo_description' => isset($post['seo_description']) ? trim(strip_tags($post['seo_description'])) : '',
            'jumpurl' => isset($post['jumpurl']) ? trim($post['jumpurl']) : '',
        );
        //渐进式内容站点隔离：文章表同步站点归属
        if($this->table == 'cms_article'){
            $cms_content['site_id'] = isset($post['site_id']) ? max(1, (int)$post['site_id']) : (int)$olddata['site_id'];
        }
        //发布时间 可以传递 2024-07-10 这种格式
        if( isset($post['dateline']) && $post['dateline'] ){
            if(!is_numeric($post['dateline'])){
                $cms_content['dateline'] = strtotime($post['dateline']);
            }else{
                $cms_content['dateline'] = $post['dateline'];
            }
            if(empty($cms_content['dateline'])){unset($cms_content['dateline']);}
        }
        //内容模板
        if(isset($post['show_tpl'])){
            $cms_content['show_tpl'] = $post['show_tpl'];
        }
        // hook cms_content_model_xedit_cms_content_after.php

        $cms_content_data = array(
            'content'=>$contentstr,
        );
        // hook cms_content_model_xedit_cms_content_data_after.php

        $endstr = '';
        $this->cms_content_attach->table = 'cms_'.$table.'_attach';
        if($isremote) {
            // hook cms_content_model_xedit_isremote_before.php
            $endstr .= $this->get_remote_img($table, $cms_content_data['content'], $uid, $cid, $id);
        }

        // 计算图片数，和非图片文件数
        $imagenum = $this->cms_content_attach->find_count(array('id'=>$id, 'uid'=>$uid, 'isimage'=>1));
        $filenum = $this->cms_content_attach->find_count(array('id'=>$id, 'uid'=>$uid, 'isimage'=>0));
        if($imagenum || $filenum){
            $cms_content['imagenum'] = $imagenum;
            $cms_content['filenum'] = $filenum;
        }

        // 如果缩略图为空，并且附件表有图片，开启自动缩略图，则将第一张图片设置为缩略图
        if(empty($cms_content['pic']) && $imagenum && $auto_pic) {
            $cms_content['pic'] = $this->auto_pic($table, $uid, $id, $models);
        }

        //开启自动缩略图，匹配内容里面的图片（这里不生成缩略图小图，直接是完整的图片网址）
        if(empty($cms_content['pic']) && $auto_pic){
            $pattern="/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/";
            preg_match_all($pattern, $contentstr, $match);
            if( isset($match[1]) && isset($match[1][0]) ){
                $match_pic = $match[1][0];
                if(substr($match_pic, 0, 2) == '..'){
                    $match_pic = substr($match_pic, 2);
                }
                $webdir_len = strlen($this->cfg['webdir']);
                if(substr($match_pic, 0, $webdir_len) == $this->cfg['webdir']){
                    $cms_content['pic'] = substr($match_pic, $webdir_len);
                }else{
                    $cms_content['pic'] = $match[1][0];
                }
            }
        }
        //处理缩略图 end

        // 比较属性变化
        $flags_old = array();
        if($olddata['flags']) {
            $flags_old = explode(',', $olddata['flags']);
            foreach($flags as $flag) {
                $key = array_search($flag, $flags_old);
                if($key !== false) unset($flags_old[$key]);
            }
        }

        $tags_arr = $tags = array();
        $tags_old = (array)_json_decode($olddata['tags']);  //旧标签数组

        // 比较标签变化
        if($tagstr){
            $tags_new = explode(',', $tagstr);
            $tags_new = array_unique($tags_new);    //去重
            $tags_new = array_filter($tags_new);    //去掉空
        }else{
            $tags_new = array();
        }
        foreach($tags_new as $tagname) {
            $tagname = $this->cms_content_tag->_tagformat($tagname);
            if($tagname){
                $key = array_search($tagname, $tags_old);
                if($key === false) {
                    $tags_arr[] = $tagname;
                }else{
                    $tags[$key] = $tagname;
                    unset($tags_old[$key]);
                }
            }
        }

        // 标签预处理，最多支持8个标签
        $this->cms_content_tag->table = 'cms_'.$table.'_tag';
        $tagdatas = array();
        for($i = 0; isset($tags_arr[$i]) && $i < 8; $i++) {
            $name = $this->cms_content_tag->_tagformat($tags_arr[$i]);
            if($name){
                $tagdata = $this->cms_content_tag->find_fetch(array('name'=>$name), array(), 0, 1);
                if($tagdata) {
                    $tagdata = current($tagdata);
                }else{
                    $tag_post = array('name'=>$name, 'count'=>0, 'content'=>'');
                    // hook cms_content_model_xedit_tag_post_after.php
                    $tagid = $this->cms_content_tag->create($tag_post);
                    if(!$tagid){
                        return array('err'=>1 ,'msg'=>lang('write_tag_table_failed'));
                    }
                    $tagdata = $this->cms_content_tag->get($tagid);
                }
                $tagdata['count']++;
                $tagdatas[] = $tagdata;

                if( _strlen(_json_encode($tags)) > 500){    //主表tags长度限制
                    break;
                }
                $tags[$tagdata['tagid']] = $tagdata['name'];
            }
        }
        if($tags){
            $cms_content['tags'] = _json_encode($tags);
        }
        //标签处理 end

        if($this->cms_content->update($cms_content)) {
            // hook cms_content_model_xedit_cms_content_success.php

            // 编辑时，别名有三种情况需要处理
            if($alias && $alias_old && $alias != $alias_old) {
                // 写入新别名
                if(!$this->only_alias->set($alias, array('mid' => $mid, 'cid' => $cid, 'id' => $id))) {
                    $this->update(array('id'=>$id, 'alias'=>''));
                }

                // 删除旧别名
                $this->only_alias->delete($alias_old);
            }elseif($alias && empty($alias_old)) {
                // 写入新别名
                if(!$this->only_alias->set($alias, array('mid' => $mid, 'cid' => $cid, 'id' => $id))) {
                    $this->update(array('id'=>$id, 'alias'=>''));
                }
            }elseif(empty($alias) && $alias_old) {
                // 删除旧别名
                $this->only_alias->delete($alias_old);
            }

        }else{
            return array('err'=>1 ,'msg'=>lang('write_content_table_failed'));
        }

        //附表数据
        if($this->cms_content_data->set_cms_content_data($id, $cms_content_data, $table)) {
            // hook cms_content_model_xedit_cms_content_data_success.php
        }else{
            return array('err'=>1 ,'msg'=>lang('write_content_data_table_failed'));
        }

        // 写入内容查看数表
        $this->cms_content_views->table = 'cms_'.$table.'_views';
        $cms_content_views = array(
            'cid'=>$cid,
            'views'=>isset($post['views']) ? (int)$post['views'] : 0,
        );
        // hook cms_content_model_xedit_cms_content_views_after.php

        if($this->cms_content_views->set_cms_content_views($id, $cms_content_views, $table)) {
            // hook cms_content_model_xedit_cms_content_views_success.php
        }else{
            return array('err'=>1 ,'msg'=>lang('write_content_views_table_failed'));
        }

        // 写入内容属性标记表
        $this->cms_content_flag->table = 'cms_'.$table.'_flag';
        foreach($flags as $flag) {
            $this->cms_content_flag->set(array($flag, $id), array('cid'=>$cid));
        }
        // 删除去掉的属性
        foreach($flags_old as $flag) {
            $flag = intval($flag);
            if($flag) $this->cms_content_flag->delete($flag, $id);
        }

        // 写入内容标签表
        $this->cms_content_tag_data->table = 'cms_'.$table.'_tag_data';
        foreach($tagdatas as $tagdata) {
            $this->cms_content_tag->update($tagdata);
            $this->cms_content_tag_data->set(array($tagdata['tagid'], $id), array('id'=>$id));
        }
        // 删除不用的标签
        foreach($tags_old as $tagid => $tagname) {
            $tagdata = $this->cms_content_tag->get($tagid);
            $tagdata['count']--;
            $this->cms_content_tag->update($tagdata);
            $this->cms_content_tag_data->delete($tagid, $id);
        }

        //改变分类了
        if($cid != $olddata['cid']){
            // 旧的分类内容数减1
            $categorys_old = $this->category->get($olddata['cid']);
            $categorys_old['count'] = max(0, $categorys_old['count']-1);
            $this->category->update($categorys_old);

            // 新的分类内容数加1
            $categorys['count']++;
            $this->category->update($categorys);

            //删除两个分类缓存数据
            $this->category->delete_cache_one($cid);
            $this->category->delete_cache_one($olddata['cid']);

            if($alias_old == $alias){   //这里只处理别名未变动情况下的cid更新，别名有变动的话 会在上面的三种情况下更新数据
                if(!$this->only_alias->set($alias, array('mid' => $mid, 'cid' => $cid, 'id' => $id))) {
                    $this->update(array('id'=>$id, 'alias'=>''));
                }
            }

            //更新附件表的cid
            $this->cms_content_attach->find_update(array('id'=>$id), array('cid'=>$cid));

            //更新评论排序表的cid
            if($olddata['comments']){
                $this->cms_content_comment_sort->find_update(array('mid'=>$mid, 'id'=>$id), array('cid'=>$cid));
            }

            // hook cms_content_model_xedit_cid_change_after.php
        }

        //返回数据
        $return_data = array(
            'cid'=>$cid,
            'id'=>$id,
            'alias'=>$alias,
            'mid'=>$mid
        );

        // hook cms_content_model_xedit_after.php

        return array('err'=>0, 'msg'=>lang('edit_successfully').$endstr, 'data'=>$return_data);
    }

	// 内容关联删除
	public function xdelete($table = 'article', $id = 0, $cid = 0) {
		// hook cms_content_model_xdelete_before.php

		$this->table = 'cms_'.$table;
		$this->cms_content_data->table = 'cms_'.$table.'_data';
		$this->cms_content_attach->table = 'cms_'.$table.'_attach';
		$this->cms_content_flag->table = 'cms_'.$table.'_flag';
		$this->cms_content_tag->table = 'cms_'.$table.'_tag';
		$this->cms_content_tag_data->table = 'cms_'.$table.'_tag_data';
		$this->cms_content_views->table = 'cms_'.$table.'_views';

		//内容读取
		$data = $this->get($id);
		if(empty($data)) return lang('data_no_exists');

		//删除附件表里面的附件
		$attach_arr = $this->cms_content_attach->find_fetch(array('id'=>$id));
		foreach($attach_arr as $v) {
            if($v['isimage']){
                $updir = ROOT_PATH.'upload/'.$table.'/';
            }else{
                $updir = ROOT_PATH.'upload/attach/';
            }
			$file = $updir.$v['filepath'];
			if($v['isimage']){
                $thumb = image::thumb_name($file);
            }else{
                $thumb = '';
            }
			try{
				is_file($file) && unlink($file);
				if($thumb && is_file($thumb)) unlink($thumb);
			}catch(Exception $e) {}
			$this->cms_content_attach->delete($v['aid']);
		}

		//更新标签表
		if(isset($data['tags']) && !empty($data['tags'])) {
			$tags_arr = _json_decode($data['tags']);
			foreach($tags_arr as $tagid => $name) {
				$this->cms_content_tag_data->delete($tagid, $id);
				$tagdata = $this->cms_content_tag->get($tagid);
				$tagdata['count']--;
				if($tagdata['count'] > 0) {
                    $this->cms_content_tag->update($tagdata);
                }else{
                    $this->cms_content_tag->delete($tagid);
                }
			}
		}

		//更新分类表
		$catedata = $this->category->get($cid);
		if(empty($catedata)) return lang('category_not_exists');
		if($catedata['count'] > 0) {
			$catedata['count']--;
			if(!$this->category->update($catedata)) return lang('write_content_table_failed');
			$this->category->update_cache($cid);
		}

		//更新用户内容数
        if(isset($data['uid']) && $data['uid']){
            $user = $this->user->get($data['uid']);
            if($user && $user['contents'] > 0) {
                $user['contents']--;
                $this->user->update($user);
            }
        }

		//删除内容
		$this->cms_content_data->delete_cms_content_data($id, $table);
        //删除浏览量
		$this->cms_content_views->delete_cms_content_views($id, $table);
		//删除属性
		$this->cms_content_flag->find_delete(array('id'=>$id));
		//删除别名
        (isset($data['alias']) && !empty($data['alias'])) && $this->only_alias->delete($data['alias']);

        //删除评论和评论排序
        if(isset($data['comments']) && $data['comments']){
            $where = array('mid'=>$catedata['mid'], 'id'=>$id);
            $this->cms_content_comment->find_delete($where);
            $this->cms_content_comment_sort->find_delete($where);
        }

        //删除基础数据
		$ret = $this->delete($id);

        // hook cms_content_model_xdelete_after.php
		return $ret ? '' : lang('delete_failed');
	}

	//移动内容
    public function xmove($table = 'article', $id = 0, $old_cid = 0, $cid = 0){
        // hook cms_content_model_xmove_before.php
        if(empty($id) || empty($old_cid) || empty($cid) || $old_cid == $cid){
            return lang('data_error');
        }

        $this->table = 'cms_'.$table;
        $this->cms_content_attach->table = 'cms_'.$table.'_attach';
        $this->cms_content_flag->table = 'cms_'.$table.'_flag';
        $this->cms_content_views->table = 'cms_'.$table.'_views';

        //内容读取
        $data = $this->get($id);
        if(empty($data)) return lang('data_no_exists');

        $old_cate = $this->category->get($old_cid);
        $new_cate = $this->category->get($cid);
        if(empty($old_cate) || empty($new_cate)){
            return lang('data_error');
        }

        $old_mid = $old_cate['mid'];
        $mid = $new_cate['mid'];
        if($old_mid != $mid || $mid < 2){
            return lang('data_error');
        }

        //更新主表
        $data['cid'] = $cid;
        if( !$this->update($data) ){
            return lang('opt_failed');
        }

        //更新别名表
        if($data['alias']){
            $this->only_alias->find_update(array('alias'=>$data['alias']), array('cid'=>$cid));
        }

        //更新属性内容表
        if($data['flags']) {
            $this->cms_content_flag->find_update(array('id'=>$id), array('cid'=>$cid));
        }

        //更新附件表
        $this->cms_content_attach->find_update(array('id'=>$id), array('cid'=>$cid));

        //更新浏览量表
        $this->cms_content_views->find_update(array('id'=>$id), array('cid'=>$cid));

        //更新评论排序表
        $this->cms_content_comment_sort->find_update(array('mid'=>$mid,'id'=>$id), array('cid'=>$cid));

        //更新旧的分类
        $old_cate['count'] = max(0, $old_cate['count']-1);
        $this->category->update($old_cate);
        $this->category->delete_cache_one($old_cid);

        //更新新的分类
        $new_cate['count']++;
        $this->category->update($new_cate);
        $this->category->delete_cache_one($cid);

        // hook cms_content_model_xmove_after.php
        return '';
    }

	// 标签链接格式化
	public function tag_url($mid = 2, $tags = array(), $page = FALSE, $extra = array()) {
        $link_tag_type = isset($this->cfg['link_tag_type']) ? (int)$this->cfg['link_tag_type'] : 0;
        $name = isset($tags['name']) ? $tags['name'] : '';
        $tagid = isset($tags['tagid']) ? (int)$tags['tagid'] : 0;
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        // hook cms_content_model_tag_url_before.php

        if(empty($name) || empty($tagid)){
            return '';
        }

        $name = $this->cms_content_tag->_tagformat($name, 0);

		if(empty($_ENV['_config']['lecms_parseurl'])) {
			$s = $page ? '-page-{page}' : '';
			switch ($link_tag_type){
                case 0:
                    return $this->cfg['weburl'].'index.php?tag-'.($mid > 2 ? '-mid-'.$mid : '').'-name-'.urlencode($name).$s.$_ENV['_config']['url_suffix'];
                    break;
                case 1:
                    return $this->cfg['weburl'].'index.php?tag-'.($mid > 2 ? '-mid-'.$mid : '').'-tagid-'.$tagid.$s.$_ENV['_config']['url_suffix'];
                    break;
                case 2:
                    $encrypt = encrypt($mid.'_'.$tagid);
                    return $this->cfg['weburl'].'index.php?tag--encrypt-'.$encrypt.$s.$_ENV['_config']['url_suffix'];
                    break;
                case 3:
                    $encrypt = hashids_encrypt($mid,$tagid);
                    return $this->cfg['weburl'].'index.php?tag--encrypt-'.$encrypt.$s.$_ENV['_config']['url_suffix'];
                    break;
            }
		}else{
            // hook cms_content_model_tag_url_lecms_parseurl_before.php
            $s = $page ? '/page_{page}' : '';
            switch ($link_tag_type){
                case 0:
                    return $this->cfg['weburl'].$this->cfg['link_tag_pre'].($mid > 2 ? $mid.'_' : '').urlencode($name).$s.$this->cfg['link_tag_end'];
                    break;
                case 1:
                    return $this->cfg['weburl'].$this->cfg['link_tag_pre'].($mid > 2 ? $mid.'_' : '').$tagid.$s.$this->cfg['link_tag_end'];
                    break;
                case 2:
                    $encrypt = encrypt($mid.'_'.$tagid);
                    return $this->cfg['weburl'] . $this->cfg['link_tag_pre'] . $encrypt.$s.$this->cfg['link_tag_end'];
                    break;
                case 3:
                    $encrypt = hashids_encrypt($mid,$tagid);
                    return $this->cfg['weburl'] . $this->cfg['link_tag_pre'] . $encrypt.$s.$this->cfg['link_tag_end'];
                    break;
            }
		}
        // hook cms_content_model_tag_url_after.php
	}

	// 评论链接格式化
	public function comment_url($cid = 0, $id = 0, $page = FALSE) {
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
		// hook cms_content_model_comment_url_before.php

		if(empty($_ENV['_config']['lecms_parseurl'])) {
			$s = $page ? '-page-{page}' : '';
			return $this->cfg['weburl'].'index.php?comment--cid-'.$cid.'-id-'.$id.$s.$_ENV['_config']['url_suffix'];
		}else{
            // hook cms_content_model_comment_url_lecms_parseurl_before.php
			return $this->cfg['weburl'].$this->cfg['link_comment_pre'].$cid.'_'.$id.($page ? '_{page}' : '').$_ENV['_config']['url_suffix'];
		}
	}

    // 内容链接格式化
    public function content_url(&$content, $mid = 2, $page = FALSE, $extra = array()) {
        $url = '';
        $link_show_end = isset($this->cfg['link_show_end']) ? $this->cfg['link_show_end'] : $_ENV['_config']['url_suffix'];

        $id = isset($content['id']) ? (int)$content['id'] : 0;
        $cid = isset($content['cid']) ? (int)$content['cid'] : 0;
        $alias = isset($content['alias']) ? $content['alias'] : '';
        empty($alias) && $alias = $cid.'_'.$id;
        $dateline = isset($content['dateline']) ? (int)$content['dateline'] : 0;
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        // hook cms_content_model_content_url_before.php

        if(empty($id) || empty($cid)){
            return '';
        }

        if(empty($_ENV['_config']['lecms_parseurl'])) {
            $url .= $this->cfg['weburl'].'index.php?show--cid-'.$cid.'-id-'.$id.($page ? '-page-{page}' : '').$_ENV['_config']['url_suffix'];
        }else{
            // hook cms_content_model_content_url_lecms_parseurl_before.php

            switch($this->cfg['link_show_type']) {
                case 1: //数字型
                    $url .= $this->cfg['weburl'].$cid.'/'.$id.$link_show_end;
                    break;
                case 2: //推荐型
					if(!isset($this->cfg['cate_arr'][$cid])){return '';}
                    $url .= $this->cfg['weburl'].$this->cfg['cate_arr'][$cid].'/'.$id.$link_show_end;
                    break;
                case 3: //别名型
                    $url .= $this->cfg['weburl'].$alias.$link_show_end;
                    break;
                case 4: //加密型
                    $url .= $this->cfg['weburl'].encrypt($cid.'_'.$id).$link_show_end;
                    break;
                case 8: //HashId，放前面 提高命中率
                    $url .= $this->cfg['weburl'].hashids_encrypt($cid,$id).$link_show_end;
                    break;
                case 5: //模型ID_数字型
                    if($mid > 2){
                        $url .= $this->cfg['weburl'].$mid.'_'.$id.$link_show_end;
                    }else{
                        $url .= $this->cfg['weburl'].$id.$link_show_end;
                    }
                    break;
                case 6: //分类别名+内容别名型
					if(!isset($this->cfg['cate_arr'][$cid])){return '';}
                    $url .= $this->cfg['weburl'].$this->cfg['cate_arr'][$cid].'/'.$alias.$link_show_end;
                    break;
                case 7: //灵活型
                    $url .= $this->cfg['weburl'].strtr($this->cfg['link_show'], array(
                            '{cid}' => $cid,
                            '{mid}' => $mid,
                            '{id}' => $id,
                            '{alias}' => $alias,
                            '{cate_alias}' => isset($this->cfg['cate_arr'][$cid]) ? $this->cfg['cate_arr'][$cid] : '',
                            '{password}' => encrypt($cid.'_'.$id),
                            '{ymd}' => date('Ymd', $dateline),
                            '{y}' => date('Y', $dateline),
                            '{m}' => date('m', $dateline),
                            '{d}' => date('d', $dateline),
                            '{auth_key}' => substr(md5($_ENV['_config']['auth_key']), 0, 6),
                            '{hashids}' => hashids_encrypt($cid,$id)
                        ));
                    break;
            }

            if($page){
                $url .= '&page={page}';
            }

            // hook cms_content_model_content_url_parseurl_after.php
        }
        // hook cms_content_model_content_url_after.php
        return $url;
    }

    //用户模块相关URL，只涉及 user-xxx 或者 my-xxx（为了兼容旧版本的插件使用该函数， 新的开发请使用 urls_model里面的该函数）
    public function user_url($action = 'index', $control = 'user', $page = false, $extra = array()){
        //使用相对URL
        if(isset($this->cfg['url_path']) && !empty($this->cfg['url_path'])){
            $this->cfg['weburl'] = $this->cfg['webdir'];
        }
        $allow_control = array('user', 'my');
        // hook cms_content_model_user_url_before.php

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
            // hook cms_content_model_user_url_lecms_parseurl_before.php
            return $this->cfg['weburl'].$control.'-'.$action.$s.$_ENV['_config']['url_suffix'];
        }
    }
	
	//获取模型的自定义字段(不执行格式化)
	public function get_model_fields_by_mid($mid = 2){
		// hook cms_content_model_get_model_fields_by_mid_before.php
		if(!plugin_is_enable('models_filed')){
			return array();
		}
		if(isset($this->data['models_field'.$mid]) && !empty($this->data['models_field'.$mid])) {
			$models_field = $this->data['models_field'.$mid];
		}else{
			$models_field = $this->models_field->user_defined_field($mid);
			$this->data['models_field'.$mid] = $models_field;
		}
		// hook cms_content_model_get_model_fields_by_mid_after.php
		return $models_field;
	}

    // 自动生成缩略图
    public function auto_pic($table = 'article', $uid = 1, $id = 0, $models = array()) {
        // hook cms_content_model_auto_pic_before.php

        $this->cms_content_attach->table = 'cms_'.$table.'_attach';
        $pic_arr = $this->cms_content_attach->find_fetch(array('id'=>$id, 'uid'=>$uid, 'isimage'=>1), array(), 0, 1);
        if($pic_arr){
            $pic_arr = current($pic_arr);

            $path = 'upload/'.$table.'/'.$pic_arr['filepath'];
            $pic = image::thumb_name($path);
            $src_file = ROOT_PATH.$path;
            $dst_file = ROOT_PATH.$pic;
            if( !is_file($dst_file) && $models ) {
                image::thumb($src_file, $dst_file, $models['width'], $models['height'], $this->cfg['thumb_type'], $this->cfg['thumb_quality']);
                return $path;
            }else{
                return $path;
            }
        }else{
            return '';
        }
    }

    /**
     * 获取远程图片
     * @param $table 表名
     * @param $content 内容
     * @param int $uid 用户ID
     * @param int $cid 分类ID
     * @param int $id 内容ID
     * @param int $write_db 是否写入附件表
     * @return string
     */
    public function get_remote_img($table = 'article', &$content = '', $uid = 1, $cid = 0, $id = 0, $write_db = 1) {
        if(empty($content)){
            return '';
        }
        function_exists('set_time_limit') && set_time_limit(0);

        $updir = 'upload/'.$table.'/';
        $_ENV['_prc_err'] = 0;
        $_ENV['_prc_arg'] = array(
            'hosts'=>array('127.0.0.1', 'localhost', $_SERVER['HTTP_HOST'], $this->cfg['webdomain']),
            'uid'=>$uid,
            'cid'=>$cid,
            'id'=>$id,
            'maxSize'=>10000,
            'upDir'=>ROOT_PATH.$updir,
            'preUri'=>$this->cfg['webdir'].$updir,  //相对图片地址，绝对图片地址用 weburl
            'cfg'=>$this->cfg,
            'write_db'=>$write_db
        );

        $this->cms_content_attach->table = 'cms_'.$table.'_attach';
        $content = preg_replace_callback('#\<img [^\>]*src=["\']((?:http|https|ftp)\://[^"\']+)["\'][^\>]*\>#iU', array($this, 'img_replace'), $content);
        unset($_ENV['_prc_arg']);
        return $_ENV['_prc_err'] ? lang('isremote_failed_tip_1').$_ENV['_prc_err'].lang('isremote_failed_tip_2') : '';
    }

    // 远程图片处理 (如果抓取失败则不替换)
    // $conf 用到4个参数 hosts preUri cfg upDir
    private function img_replace($mat) {
        static $uris = array();
        $uri = $mat[1];
        $conf = &$_ENV['_prc_arg'];
        if( !isset($conf['write_db']) ){
            $conf['write_db'] = 1;
        }

        // 排除重复保存相同URL图片
        if(isset($uris[$uri])) return str_replace($uri, $uris[$uri], $mat[0]);

        // 根据域名排除本站图片
        $urls = parse_url($uri);
        if(in_array($urls['host'], $conf['hosts'])) return $mat[0];

        $file = $this->cms_content_attach->remote_down($uri, $conf, (int)$conf['write_db']);
        if($file) {
            $uris[$uri] = $conf['preUri'].$file;
            $cfg = $conf['cfg'];

            // 是否添加水印
            if(!empty($cfg['watermark_pos'])) {
                image::watermark($conf['upDir'].$file, ROOT_PATH.'static/img/watermark.png', null, $cfg['watermark_pos'], $cfg['watermark_pct']);
            }

            return str_replace($uri, $uris[$uri], $mat[0]);
        }else{
            $_ENV['_prc_err']++;
            return $mat[0];
        }
    }

    // hook cms_content_model_after.php
}

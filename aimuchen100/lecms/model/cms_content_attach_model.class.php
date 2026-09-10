<?php
defined('ROOT_PATH') or exit;

class cms_content_attach extends model {
    
    public $_field_length = array(
        'filename'=>200,
        'filetype'=>10,
        'filepath'=>200,
        // hook cms_content_attach_model_field_length_after.php
    );

	function __construct() {
		$this->table = '';			// 表名
		$this->pri = array('aid');	// 主键
		$this->maxid = 'aid';		// 自增字段
	}

    //获取模型附件下拉框
    public function get_attachhtml_mid($mid = 2, $str = ''){
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
                    $s .= '<option value="'.$v['mid'].'"'.($v['mid'] == $mid ? ' selected="selected"' : '').'>'.$modelname.lang('attach').'</option>';
                }
            }
        }
        $s .= '</select>';
        // hook cms_content_attach_model_get_attachhtml_mid_after.php
        return $s;
    }

    //获取某内容的附件
    public function attach_find_by_id($table = 'article', $id = 0, $where = array(), $limit = 2000, $extra = array()){
        $attachlist = $imagelist = $filelist = array();

	    $this->table = 'cms_'.$table.'_attach';
        empty($where) AND $where = array('id'=>$id);

        $orderby = 'aid';
        $orderway = 1;
        // hook cms_content_attach_model_attach_find_by_id_before.php
        $attachlist = $this->list_arr($where , $orderby, $orderway, 0, $limit, $limit, $extra);
        if($attachlist) {
            foreach ($attachlist as $attach) {
                $this->format($attach);
                $attach['isimage'] ? ($imagelist[] = $attach) : ($filelist[] = $attach);
            }
        }
        // hook cms_content_attach_model_attach_find_by_id_after.php
        return array($attachlist, $imagelist, $filelist);
    }

    //格式化附件到前台调用显示
    public function file_list_html($filelist, $mid = 2, $include_delete = FALSE){
        if(empty($filelist)) return '';

        // hook cms_content_attach_model_file_list_html_before.php

        $s = '<fieldset class="layui-elem-field attachlist"><legend>'.lang('attach_list').'</legend><div class="layui-field-box"><ul>';
        foreach ($filelist as &$attach) {
            // hook cms_content_attach_model_file_list_html_foreach_before.php
            $s .= '<li aid="'.$attach['aid'].'" mid="'.$mid.'"><a href="'.$this->urls->attach_url($mid, $attach['aid']).'" target="_blank">';
            $s .= '<i class="icon filetype '.$attach['filetype'].'"></i>'.$attach['filename'].'</a>';

            // hook cms_content_attach_model_file_list_html_delete_before.php
            $include_delete AND $s .= '		<a href="javascript:void(0)" class="attach_delete"><i class="icon-remove"></i> '.lang('delete').'</a>';
            // hook cms_content_attach_model_file_list_html_delete_after.php

            $s .= '</li>';
        };
        $s .= '</ul></div></fieldset>';

        // hook cms_content_attach_model_file_list_html_after.php
        return $s;
    }

    //格式化附件
    public function format(&$attach, $dateformat = 'Y-m-d H:i:s'){
        if(empty($attach)) return;
        // hook cms_content_attach_model_attach_format_before.php
        $attach['date'] = date($dateformat, $attach['dateline']);
        // hook cms_content_attach_model_attach_format_after.php
    }

    // 获取内容列表
    public function list_arr($where = array(), $orderby = 'aid', $orderway = 1, $start = 0, $limit = 0, $total = 0, $extra = array()) {
        // hook cms_content_attach_model_list_arr_before.php

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

        // hook cms_content_attach_model_list_arr_after.php
        return $list_arr;
    }

	// 上传并记录到数据库
	public function uploads($config = array(), $uid = 0, $cid = 0, $id = 0, $file = 'upfile') {
        // hook cms_content_attach_model_uploads_before.php
		$up = new upload($config, $file);
		$info = $up->getFileInfo();

		if($info['state'] == 'SUCCESS') {
			$data = array(
				'cid' => $cid,
				'uid' => $uid,
				'id' => $id,
				'filename' => $info['name'],
				'filetype' => $info['ext'],
				'filesize' => $info['size'],
				'filepath' => $info['path'],
				'dateline' => $_ENV['_time'],
				'downloads' => 0,
				'isimage' => $info['isimage'],
			);
            // hook cms_content_attach_model_uploads_data_after.php

			$info['maxid'] = $this->create($data);
			if($info['maxid']) {
                // hook cms_content_attach_model_uploads_success.php
			}else{
                $info['state'] = lang('write_attach_failed');
            }
		}

        // hook cms_content_attach_model_uploads_after.php
		return $info;
	}

    // 远程图片下载并记录到数据库 ($conf 用到5个参数 maxSize upDir cid uid id，如果不记录到数据库则不需要cid uid id)
    public function remote_down($uri, &$conf, $db = 1){
        // 创建图片目录
        $dir = date('Ymd/');
        $updir = $conf['upDir'] . $dir;

        // hook cms_content_attach_model_remote_down_before.php
        if (!is_dir($updir) && !mkdir($updir, 0755, true)) {
            return FALSE;
        }

        //这样子获取url的图片后缀，避免 https://pics0.baidu.com/feed/b17eca8065380cd7462f5de366a7ac3f588281fe.jpeg@f_auto?token=f2592e1c731343a8c2a3963a6743f691 这种用pathinfo获取不对扩展名
        $fileExt = '';
        $allow_ext = array('.gif', '.jpg', '.jpeg', '.png', '.bmp');
        foreach ($allow_ext as $ext){
            $find_index = stripos($uri, $ext);
            if( $find_index ){
                $uri = substr($uri,0, $find_index).$ext;    //去掉后缀名后面杂七杂八的参数
                $fileExt = substr($ext, 1);
                break;
            }
        }
        if( empty($fileExt) ){
            return false;
        }
        $fileExt == 'jpeg' && $fileExt = 'jpg';

        $network = Network::Create();
        if (!$network) {
            return false;
        }
        $network->open('GET', $uri);
        $network->enableGzip();
        $network->setTimeOuts(60, 60, 0, 0);
        $network->setRequestHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:95.0) Gecko/20100101 Firefox/95.0');
        $network->send();

        if ($network->status == 200){
            $img = $network->responseText ;

            $filepath = $dir.date('His').uniqid().random(6, 3).'.'.$fileExt;
            // hook cms_content_attach_model_remote_down_filepath_after.php
            if(!file_put_contents($conf['upDir'].$filepath, $img)) {
                return FALSE;
            }

            if($db){
                // 记录到数据库
                $data = array(
                    'cid' => $conf['cid'],
                    'uid' => $conf['uid'],
                    'id' => $conf['id'],
                    'filename' => basename($uri),
                    'filetype' => $fileExt,
                    'filesize' => filesize($conf['upDir'].$filepath),
                    'filepath' => $filepath,
                    'dateline' => $_ENV['_time'],
                    'downloads' => 0,
                    'isimage' => 1,
                );
                // hook cms_content_attach_model_remote_down_data_after.php
                $createaid = $this->create($data);
                if(!$createaid) return FALSE;
            }
            // hook cms_content_attach_model_remote_down_after.php
            return $filepath;
        }else{
            return false;
        }
    }

	// 删除单个附件
	public function xdelete($aid) {
		$data = $this->get($aid);

		if( empty($data) ){
		    return lang('data_no_exists');
        }
		$table = substr($this->table, 4, -7);   // cms_article_attach，只要 article

        if( substr($data['filepath'], 0, 2) != '//' && substr($data['filepath'], 0, 4) != 'http' ) { //不是外链
            if($data['isimage']){
                $updir = 'upload/'.$table.'/';
            }else{
                $updir = 'upload/attach/';
            }
            $file = $updir.$data['filepath'];
            $filepath = ROOT_PATH.$file;
        }else{
            $cfg = $this->runtime->xget();
            $updir = str_replace($cfg['weburl'],'',$data['filepath']);
            $file = $updir;
            $filepath = ROOT_PATH.$file;
        }

        //删除文件
		try{
			is_file($filepath) && unlink($filepath);
			if($data['isimage']){
                $thumb = image::thumb_name($filepath);
                is_file($thumb) && unlink($thumb);
            }
		}catch(Exception $e) {}

        $ret = $this->delete($aid);

        if($ret && $data['id']){
            // 初始模型表名
            $this->cms_content->table = 'cms_'.$table;
            $this->cms_content_data->table = 'cms_'.$table.'_data';
            $cms_content = $this->cms_content->get($data['id']);

            //更新内容表
            if($cms_content){
                $update_cms_content = 0;
                if($cms_content['pic'] && strpos($cms_content['pic'], $data['filepath']) !== false ){    //清空缩略图
                    $update_cms_content = 1;
                    $cms_content['pic'] = '';
                }

                if($data['isimage']){
                    if($cms_content['imagenum']){
                        $update_cms_content = 1;
                        $cms_content['imagenum'] = $cms_content['imagenum']-1;
                    }
                }else{
                    if($cms_content['filenum']){
                        $update_cms_content = 1;
                        $cms_content['filenum'] = $cms_content['filenum']-1;
                    }
                }

                if($update_cms_content){
                    // hook cms_content_attach_model_xdelete_cms_content_after.php
                    $this->cms_content->update($cms_content);
                }
            }

            //删除内容数据表的图片
            if($data['isimage']){
                $cms_content_data = $this->cms_content_data->get_cms_content_data($data['id'], $table);
                if($cms_content_data){
                    //清空内容字段里面的图
                    $contentstr = $cms_content_data['content'];
                    $pattern="/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/";
                    preg_match_all($pattern, $contentstr,$match);

                    $replacr_arr = array();
                    if( isset($match[1]) ){
                        foreach ($match[1] as $k=>$imgsrc){
                            if( strpos($imgsrc, $file) !== false ){
                                $replacr_arr[] = $match[0][$k];
                            }
                        }
                    }

                    if( $replacr_arr ){
                        $contentstr = str_replace($replacr_arr, '', $contentstr);
                        $cms_content_data['content'] = $contentstr;
                    }
                    // hook cms_content_attach_model_xdelete_cms_content_data_after.php

                    unset($cms_content_data['id']);
                    $this->cms_content_data->set_cms_content_data($data['id'], $cms_content_data, $table);
                }
            }
        }
        // hook cms_content_attach_model_xdelete_after.php
        return $ret ? '' : lang('delete_failed');
	}

    // hook cms_content_attach_model_after.php
}

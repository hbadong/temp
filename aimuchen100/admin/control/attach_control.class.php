<?php
defined('ROOT_PATH') or exit;

class attach_control extends admin_control {

    //layui 上传 pic，不存储到附件表，layedit富文本上传图片返回格式不一样 必须回传 code
    public function upload_pic(){
        // hook admin_attach_control_upload_pic_before.php
        $mid = (int)R('mid');
        $layedit = (int)R('layedit');   //layedit 编辑器上传图片？
        $table = isset($_GET['table']) ? trim($_GET['table']) : 'other';
        if($mid && $table == 'other'){
            $table = $this->models->get_table($mid);
        }
        $cfg = $this->runtime->xget();

        $updir = 'upload/'.$table.'/';
        $config = array(
            'maxSize'=>$cfg['up_img_max_size'],
            'allowExt'=>$cfg['up_img_ext'],
            'upDir'=>ROOT_PATH.$updir,
        );
        $up = new upload($config, 'upfile');
        $info = $up->getFileInfo();
        if($info['state'] == 'SUCCESS') {
            $path = $updir . $info['path'];   //相对路径
            $src_file = ROOT_PATH . $path;    //绝对路径

            // 是否添加水印
            if( !empty($cfg['watermark_pos']) && is_file(ROOT_PATH.'static/img/watermark.png') ) {
                image::watermark($src_file, ROOT_PATH.'static/img/watermark.png', null, $cfg['watermark_pos'], $cfg['watermark_pct']);
            }

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
            if($layedit){
                $data['code'] = 0;
                $data['data']['src'] = $cfg['weburl'].$path;
                $data['data']['path'] = $cfg['weburl'].$path;
            }
            echo json_encode($data);
        }else{
            $res = array('err'=>1, 'msg'=>$info['state']);
            if($layedit){
                $res['code'] = 1;
            }
            echo json_encode($res);
        }
        exit();
    }

	// 上传图集和缩略图 只能是内容模型使用，需要存储到内容模型附件数据表
	public function upload_image() {
		// hook admin_attach_control_upload_image_before.php

		$type = R('type');
		$mid = max(2, (int)R('mid','R'));
		$cid = (int)R('cid');
		$id = (int)R('id');

        $models = $this->models->get($mid);
        if(empty($models)){
            $res = array('err'=>1, 'msg'=>lang('data_error'));
            echo json_encode($res);
            exit();
        }
        $table = $models['tablename'];

		$cfg = $this->runtime->xget();

		$updir = 'upload/'.$table.'/';
		$config = array(
			'maxSize'=>$cfg['up_img_max_size'],
			'allowExt'=>$cfg['up_img_ext'],
			'upDir'=>ROOT_PATH.$updir,
		);
		$this->cms_content_attach->table = 'cms_'.$table.'_attach';
		$info = $this->cms_content_attach->uploads($config, $this->_user['uid'], $cid, $id);
        // hook admin_attach_control_upload_image_uploads_after.php

		if($info['state'] == 'SUCCESS') {
			$path = $updir.$info['path'];
            $src_file = ROOT_PATH.$path;

            $thumb = '';
			if($type == 'pic'){ //生成缩略图
                $thumb = image::thumb_name($path);
                image::thumb($src_file, ROOT_PATH.$thumb, $models['width'], $models['height'], $cfg['thumb_type'], $cfg['thumb_quality']);
            }elseif ($type == 'img'){
                $path = $cfg['weburl'].$path;
            }

			// hook admin_attach_control_upload_image_success_after.php

			// 是否添加水印
			if(!empty($cfg['watermark_pos'])) {
				image::watermark($src_file, ROOT_PATH.'static/img/watermark.png', null, $cfg['watermark_pos'], $cfg['watermark_pct']);
			}

            //layui上传图片 返回数据
            $data = array(
                'err'=>0,
                'msg'=>lang('upload_successfully'),
                'data'=>array(
                    'aid'=>$info['maxid'],
                    'src'=> empty($thumb) ? $path : $thumb ,
                    'path' => $path,
                    'title'=>substr($info['name'],0, -(strlen($info['ext'])+1)), //不含后缀名
                )
            );

            if ($type == 'img'){    //layedit富文本上传图片 要用下面的这种格式
                $data = array(
                    'code'=>0,
                    'msg'=>'',
                    'data'=>array(
                        'aid'=>$info['maxid'],
                        'src'=> empty($thumb) ? $path : $thumb ,
                        'title'=>substr($info['name'],0, -(strlen($info['ext'])+1)), //不含后缀名
                    )
                );
            }

            echo json_encode($data);
		}else{
            $res = array('err'=>1, 'msg'=>$info['state']);
            echo json_encode($res);
		}
		exit;
	}

    //上传附件 用的不多 attach下面不在分模型文件夹
    public function upload_files(){
        // hook admin_attach_control_upload_files_before.php

        $mid = max(2, (int)R('mid','R'));
        $cid = (int)R('cid');
        $id = (int)R('id');

        $models = $this->models->get($mid);
        if(empty($models)){
            $res = array('err'=>1, 'msg'=>lang('data_error'));
            echo json_encode($res);
            exit();
        }
        $table = $models['tablename'];

        $cfg = $this->runtime->xget();
        $updir = 'upload/attach/';
        $config = array(
            'maxSize'=>$cfg['up_file_max_size'],
            'allowExt'=>$cfg['up_file_ext'],
            'upDir'=>ROOT_PATH.$updir,
        );
        $this->cms_content_attach->table = 'cms_'.$table.'_attach';
        $info = $this->cms_content_attach->uploads($config, $this->_user['uid'], $cid, $id);

        // hook admin_attach_control_upload_files_uploads_after.php
        if($info['state'] == 'SUCCESS') {
            $path = $updir.$info['path'];

            $data = array(
                'err'=>0,
                'msg'=>lang('upload_successfully'),
                'data'=>array(
                    'aid'=>$info['maxid'],
                    'src'=> $path,
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

	//根据附件表ID 删除附件
    public function del_attach(){
        // hook admin_attach_control_del_attach_before.php
        $aid = (int)R('aid','P');
        if($aid){
            $mid = max(2, (int)R('mid','R'));
            $models = $this->models->get($mid);
            if( empty($models) ){
                E(1, lang('data_error'));
            }
            $table = $models['tablename'];
            $this->cms_content_attach->table = 'cms_'.$table.'_attach';

            //删除附件文件
            $data = $this->cms_content_attach->get($aid);
            if( empty($data) ){
                E(1, lang('data_no_exists'));
            }

            if($data['isimage']){
                $file = ROOT_PATH.'upload/'.$table.'/'.$data['filepath'];
                $thumb = image::thumb_name($file);
            }else{
                $file = ROOT_PATH.'upload/attach/'.$data['filepath'];
                $thumb = '';
            }

            try{
                is_file($file) && unlink($file);
                !empty($thumb) && is_file($thumb) && unlink($thumb);
            }catch(Exception $e) {}

            if($this->cms_content_attach->delete($aid)){
                // hook admin_attach_control_del_attach_success.php
                E(0, lang('delete_successfully'));
            }else{
                E(1, lang('delete_failed'));
            }
        }else{
            E(1, lang('data_error'));
        }
    }

	// hook admin_attach_control_after.php
}

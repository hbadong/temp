<?php
defined('ROOT_PATH') or exit;
// hook attach_control_start.php
class attach_control extends base_control{

    //附件下载
    public function index(){
        // hook attach_control_index_before.php
        $mid = (int)R('mid','G');
        $aid = (int)R('id','G');
        $res = $this->download_pre($mid, $aid);
        if( $res['err'] ){
            $this->message(0 , $res['msg']);
        }else{
            $attach = isset($res['attach']) ? $res['attach'] : array();
            empty($attach) && $this->message(0 , lang('attach_is_delete'));

            // hook attach_index_readfile_before.php

            //下载的文件名不含文件类型的后缀
            $filetype = $attach['filetype'];
            $len = strlen($filetype);
            if(substr($attach['filename'], -$len) != $filetype){
                $attach['filename'] .= '.'.$filetype;
            }

            if(stripos($_SERVER["HTTP_USER_AGENT"], 'MSIE') !== FALSE || stripos($_SERVER["HTTP_USER_AGENT"], 'Edge') !== FALSE || stripos($_SERVER["HTTP_USER_AGENT"], 'Trident') !== FALSE) {
                $attach['filename'] = urlencode($attach['filename']);
                $attach['filename'] = str_replace("+", "%20", $attach['filename']);
            }
            // hook attach_control_index_filename_after.php

            $timefmt = date('D, d M Y H:i:s', $_ENV['_time']).' GMT';
            header('Date: '.$timefmt);
            header('Last-Modified: '.$timefmt);
            header('Expires: '.$timefmt);
            header('Cache-control: max-age=86400');
            header('Content-Transfer-Encoding: binary');
            header("Pragma: public");
            header('Content-Disposition: attachment; filename="'.$attach['filename'].'"');
            header('Content-Type: application/octet-stream');
            // hook attach_control_index_header_after.php

            readfile($attach['attachpath']);
            // hook attach_control_index_after.php
            exit;
        }
    }

    //下载判断 主要是判断是否是附件 内容和文件还在不在
    private function download_pre($mid, $aid){
        // hook attach_control_download_pre_before.php
        $res = array(
            'err'=> 1,
            'msg' => lang('data_error'),
            'attach' => array(),
        );
        if ($mid < 2 || empty($aid) || !isset($this->_cfg['table_arr'][$mid])){
            $res['msg'] = lang('data_error');
            return $res;
        }

        $table = $this->_cfg['table_arr'][$mid];
        $this->cms_content_attach->table = 'cms_'.$table.'_attach';

        $attach = $this->cms_content_attach->get($aid);
        // hook attach_control_download_pre_attach.php

        if( empty($attach) || !empty($attach['isimage']) ){
            $res['msg'] = lang('attach_is_delete');
            return $res;
        }else{
            //附件
            $attachpath = ROOT_PATH.'upload/attach/'.$attach['filepath'];
            if( !is_file($attachpath) ){
                // hook attach_control_download_pre_failed.php

                $res['msg'] = lang('attach_is_delete');
                return $res;
            }else{
                // hook attach_control_download_pre_success.php

                //更新下载数
                $attach['downloads'] += 1;
                $this->cms_content_attach->update($attach);

                $res['err'] = 0;
                $attach['attachpath'] = $attachpath;
                $res['attach'] = $attach;
                return $res;
            }
        }
        return $res;
    }

    // hook attach_control_after.php
}
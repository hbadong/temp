<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:前台更新内容浏览量控制器 已废弃
 */
defined('ROOT_PATH') or exit;

class views_control extends control{

    //AJAX异步更新内容浏览量，这里不判断后台是否关闭浏览量统计
    public function index() {
        $id = (int)R('id');
        $cid = (int)R('cid');
        $n = 1;
        // hook views_control_index_before.php

        if(empty($cid) || empty($id)){
            echo 'var views="0";';
            exit;
        }

        $_var = $this->category->get_cache($cid);
        if(empty($_var) || $_var['mid'] == 1){
            echo 'var views="0";';
            exit;
        }

        $this->cms_content_views->table = 'cms_'.$_var['table'].'_views';
        $data = $this->cms_content_views->get($id);

        if(!$data){
            echo 'var views="0";';
            exit;
        }

        $data['views'] += $n;
        echo 'var views='.$data['views'].';';
        $this->cms_content_views->update_views($id, $n);

        // hook views_control_index_after.php
        exit;
    }
    // hook views_control_after.php
}

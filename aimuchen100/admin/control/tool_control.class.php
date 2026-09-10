<?php
defined('ROOT_PATH') or exit;
class tool_control extends admin_control{

    // 清除缓存
    public function index() {
        // hook admin_tool_control_index_before.php
        if(!empty($_POST)) {
            // hook admin_tool_control_index_post_before.php

            !empty($_POST['dbcache']) && $this->runtime->truncate();
            !empty($_POST['filecache']) && $this->un_filecache();

            // hook admin_tool_control_index_post_after.php

            E(0, lang('clear_success'));
        }
        $this->assign_value('dbcache_count',$this->runtime->count());
        $this->display();
    }

    // 重新统计
    public function rebuild() {
        // hook admin_tool_control_rebuild_before.php
        if(!empty($_POST)) {
            // hook admin_tool_control_rebuild_post_before.php
            // 重新统计分类的内容数量
            if(!empty(R('re_cate', 'P'))) {
                $tables = $this->models->get_table_arr();
                $cids = $this->category->get_category_db();

                foreach($cids as $row) {
                    if($row['mid'] == 1) continue;

                    $this->cms_content->table = 'cms_'.(isset($tables[$row['mid']]) ? $tables[$row['mid']] : 'article');
                    $count = $this->cms_content->find_count(array('cid'=>$row['cid']));

                    $this->category->update(array('cid'=>$row['cid'], 'count'=>$count));
                }
            }

            // 清空数据表的 count max 值，让其重新统计
            if(!empty(R('re_table', 'P'))) {
                $this->db->truncate('framework_count');
                $this->db->truncate('framework_maxid');
            }

            // 重新统计用户的内容数量
            if(!empty(R('re_user_content', 'P'))) {
                //分批次处理
                $pagenum = 500;
                $user_total = $this->user->count();

                //所有内容模型的表名
                $table_arr = $this->models->get_table_arr();

                $maxpage = max(1, ceil($user_total/$pagenum));
                for($i = 1; $i <= $maxpage; $i++){
                    $user_arr = $this->user->list_arr(array(), 'uid', 1, ($i-1)*$pagenum, $pagenum, $user_total);
                    foreach ($user_arr as $user){
                        $this->user->update_user_contents($user, $table_arr);
                    }
                }
            }

            // hook admin_tool_control_rebuild_post_after.php

            E(0, lang('rebuild_success'));
        }

        $this->display();
    }

    // 清除日志
    public function log() {
        $php_error_file = LOG_PATH.'php_error.php';
        $php_error404_file = LOG_PATH.'php_error404.php';
        $login_log_file = LOG_PATH.'login_log.php';
        // hook admin_tool_control_log_before.php

        if(!empty($_POST)) {
            // hook admin_tool_control_log_post_before.php
            if(!empty($_POST['log_error']) && is_file($php_error_file)){
                unlink($php_error_file);
            }
            if(!empty($_POST['log404']) && is_file($php_error404_file)){
                unlink($php_error404_file);
            }
            if(!empty($_POST['log_login']) && is_file($login_log_file)){
                unlink($login_log_file);
            }
            // hook admin_tool_control_log_post_after.php
            E(0, lang('clear_success'));
        }else{
            $php_error_file_byte = is_file($php_error_file) ? get_byte(filesize($php_error_file)) : lang('file_not_exist');
            $php_error404_file_byte = is_file($php_error404_file) ? get_byte(filesize($php_error404_file)) : lang('file_not_exist');
            $login_log_file_byte = is_file($login_log_file) ? get_byte(filesize($login_log_file)) : lang('file_not_exist');

            $this->assign('php_error_file_byte', $php_error_file_byte);
            $this->assign('php_error404_file_byte', $php_error404_file_byte);
            $this->assign('login_log_file_byte', $login_log_file_byte);

            // hook admin_tool_control_log_after.php
            $this->display();
        }
    }

    // 删除文件缓存
    private function un_filecache() {
        // hook admin_tool_control_un_filecache_before.php

        $this->runtime->clear_filecache();

        // hook admin_tool_control_un_filecache_after.php
        return TRUE;
    }
    // hook admin_tool_control_after.php
}
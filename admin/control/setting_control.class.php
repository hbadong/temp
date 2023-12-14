<?php
defined('ROOT_PATH') or exit;
class setting_control extends admin_control{
    //基本设置
    public function index(){
        // hook admin_setting_control_index_before.php
        if(empty($_POST)) {
            $cfg = $this->kv->xget('cfg');
            $input = array();
            $input['webname'] = form::get_text('webname', $cfg['webname'], '', 'required="required" lay-verify="required"');
            $input['webdomain'] = form::get_text('webdomain', $cfg['webdomain'], '', 'required="required" lay-verify="required"');
            $input['webdir'] = form::get_text('webdir', $cfg['webdir'], '', 'required="required" lay-verify="required" maxlength="50"');
            $input['webmail'] = form::get_text('webmail', $cfg['webmail']);
            $input['webqq'] = form::get_text('webqq', $cfg['webqq']);
            $input['webweixin'] = form::get_text('webweixin', $cfg['webweixin']);
            $input['webtel'] = form::get_text('webtel', $cfg['webtel']);
            $input['tongji'] = form::get_textarea('tongji', $cfg['tongji']);
            $input['beian'] = form::get_text('beian', $cfg['beian']);
            $input['copyright'] = form::get_textarea('copyright', $cfg['copyright']);

            $e_arr = array('1'=>lang('enable'),'0'=>lang('disable'));
            $input['open_mobile_view'] = form::layui_loop('radio', 'open_mobile_view', $e_arr, $cfg['open_mobile_view']);
            $input['mobile_view'] = form::get_text('mobile_view', $cfg['mobile_view']);

            // hook admin_setting_control_index_after.php

            $this->assign('input', $input);
            $this->display();
        }else{
            _trim($_POST);

            //安全过滤 ？ https://xz.aliyun.com/t/10871
            $webdir = R('webdir', 'P');
            $webdir = str_replace(array('<','>','"'), '', $webdir);

            $this->kv->xset('webname', R('webname', 'P'), 'cfg');
            $this->kv->xset('webdomain', R('webdomain', 'P'), 'cfg');
            $this->kv->xset('webdir', $webdir, 'cfg');
            $this->kv->xset('webmail', R('webmail', 'P'), 'cfg');
            $this->kv->xset('webqq', R('webqq', 'P'), 'cfg');
            $this->kv->xset('webweixin', R('webweixin', 'P'), 'cfg');
            $this->kv->xset('webtel', R('webtel', 'P'), 'cfg');
            $this->kv->xset('tongji', R('tongji', 'P'), 'cfg');
            $this->kv->xset('beian', R('beian', 'P'), 'cfg');
            $this->kv->xset('copyright', R('copyright', 'P'), 'cfg');
            $this->kv->xset('open_mobile_view', (int)R('open_mobile_view', 'P'), 'cfg');
            $this->kv->xset('mobile_view', R('mobile_view', 'P'), 'cfg');

            // hook admin_setting_control_index_post_after.php

            $this->kv->save_changed();
            $this->runtime->delete('cfg');

            E(0, lang('edit_successfully'));
        }
    }

    // SEO设置
    public function seo() {
        // hook admin_setting_control_seo_before.php
        if(empty($_POST)) {
            $cfg = $this->kv->xget('cfg');
            $input = array();
            $input['seo_title'] = form::get_text('seo_title', $cfg['seo_title']);
            $input['seo_keywords'] = form::get_text('seo_keywords', $cfg['seo_keywords']);
            $input['seo_description'] = form::get_textarea('seo_description', $cfg['seo_description']);
            $input['show_seo_title_rule'] = form::get_text('show_seo_title_rule', $cfg['show_seo_title_rule'], '', 'required="required" lay-verify="required"');
            $input['show_seo_keywords_rule'] = form::get_text('show_seo_keywords_rule', $cfg['show_seo_keywords_rule'], '', 'required="required" lay-verify="required"');
            $input['show_seo_description_rule'] = form::get_text('show_seo_description_rule', $cfg['show_seo_description_rule'], '', 'required="required" lay-verify="required"');

            // hook admin_setting_control_seo_after.php

            $this->assign('input', $input);
            $this->display();
        }else{
            _trim($_POST);
            $this->kv->xset('seo_title', R('seo_title', 'P'), 'cfg');
            $this->kv->xset('seo_keywords', R('seo_keywords', 'P'), 'cfg');
            $this->kv->xset('seo_description', R('seo_description', 'P'), 'cfg');

            // hook admin_setting_control_seo_post_after.php

            $this->kv->save_changed();
            $this->runtime->delete('cfg');

            E(0, lang('edit_successfully'));
        }
    }

    //内容页SEO规则
    public function seo_rule(){
        if($_POST){
            $this->kv->xset('show_seo_title_rule', R('show_seo_title_rule', 'P'), 'cfg');
            $this->kv->xset('show_seo_keywords_rule', R('show_seo_keywords_rule', 'P'), 'cfg');
            $this->kv->xset('show_seo_description_rule', R('show_seo_description_rule', 'P'), 'cfg');

            // hook admin_setting_control_seo_rule_post_after.php

            $this->kv->save_changed();
            $this->runtime->delete('cfg');

            E(0, lang('edit_successfully'));
        }
    }

    // 链接设置
    public function link() {
        if(empty($_POST)) {
            $software = R('SERVER_SOFTWARE', 'S');
            $this->assign('software', $software);

            $parseurl = $_ENV['_config']['lecms_parseurl'];
            $cfg = $this->kv->xget('cfg');
            $this->assign('cfg', $cfg);
            $mk = R('mk');
            $del = R('del');
            $do = (int) R('do');
            $this->assign('do', $do);

            // 伪静态规则
            //$nginx = '#禁止访问指定的后缀名文件'."\n";
            $nginx = 'location ~ \.(zip|rar|7z|gz|ini|htm)$ {deny all;}'."\n";
            //$nginx .= '#禁止访问指定目录的后缀名文件(保护模板用)'."\n";
            $nginx .= 'location ~ /(view|lecms|admin)/.*\.(htm|ini)?$ {deny all;}'."\n";
            //$nginx .= '#禁止访问指定目录的后缀名文件'."\n";
            $nginx .= 'location ~ ^/(static|log|runcache|upload)/.*.(php|php3|php4|php5|cgi|asp|aspx|jsp|shtml|shtm|pl|cfm|sql|mdb|dll|exe|com|inc|sh)$ {deny all;}'."\n";
            //$nginx .= '#系统伪静态规则'."\n";
            $nginx .= 'if ($request_uri ~ "//") {'."\n";
            $nginx .= "\t".'return 404;'."\n";
            $nginx .= "}\n";
            $nginx .= 'if (!-e $request_filename) {'."\n";
            $nginx .= "\t".'rewrite ^'.$cfg['webdir'].'(.+) '.$cfg['webdir'].'index.php?rewrite=$1 last;'."\n";
            $nginx .= '}';
            $this->assign('nginx', $nginx);

            $apache = '<IfModule mod_rewrite.c>'."\r\n";
            $apache .= 'RewriteEngine On'."\r\n";
            $apache .= 'RewriteBase '.$cfg['webdir']."\r\n";
            $apache .= 'RewriteCond %{THE_REQUEST} \s//+'."\r\n";
            $apache .= 'RewriteRule ^ / [R=301,L]'."\r\n";
            $apache .= 'RewriteCond %{REQUEST_FILENAME} !-f'."\r\n";
            $apache .= 'RewriteCond %{REQUEST_FILENAME} !-d'."\r\n";
            $apache .= 'RewriteRule (.+) index.php?rewrite=$1 [L]'."\r\n";
            $apache .= '</IfModule>'."\r\n";
            $apache .= '<FilesMatch .(?i:htm|ini)$>'."\r\n";
            $apache .= 'Order allow,deny'."\r\n";
            $apache .= 'Deny from all'."\r\n";
            $apache .= '</FilesMatch>';
            $this->assign('apache', $apache);

            // 创建.htaccess
            $file_apache = ROOT_PATH.'.htaccess';
            $is_file_apache = is_file($file_apache);
            $this->assign('is_file_apache', $is_file_apache);
            if($mk == 'htaccess') {
                $f = @fopen($file_apache, 'w');
                if (!$f) {
                    E(1, lang('no_write_permission'));
                } else {
                    $bytes = fwrite($f, $apache);
                    fclose($f);
                    if($bytes > 0) {
                        E(0, lang('create').' .htaccess '.lang('successfully'));
                    }else{
                        E(1, lang('create').' .htaccess '.lang('failed'));
                    }
                }
            }

            // 删除.htaccess
            if($del == 'htaccess') {
                $ret = FALSE;
                try{ $is_file_apache && $ret = unlink($file_apache); }catch(Exception $e) {}
                if($ret) {
                    E(0, lang('delete').' .htaccess '.lang('successfully'));
                }else{
                    E(1, lang('delete').' .htaccess '.lang('failed'));
                }
            }

            $iis = '<?xml version="1.0" encoding="UTF-8"?>'."\r\n";
            $iis .= '<configuration>'."\r\n";
            $iis .= "\t".'<system.webServer>'."\r\n";
            $iis .= "\t\t".'<rewrite>'."\r\n";
            $iis .= "\t\t\t".'<rules>'."\r\n";
            $iis .= "\t\t\t\t".'<rule name="Lecms Rule '.$cfg['webdir'].'" stopProcessing="true">'."\r\n";
            $iis .= "\t\t\t\t\t".'<match url="(.+)" ignoreCase="false" />'."\r\n";
            $iis .= "\t\t\t\t\t".'<conditions logicalGrouping="MatchAll">'."\r\n";
            $iis .= "\t\t\t\t\t\t".'<add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />'."\r\n";
            $iis .= "\t\t\t\t\t\t".'<add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />'."\r\n";
            $iis .= "\t\t\t\t\t".'</conditions>'."\r\n";
            $iis .= "\t\t\t\t\t".'<action type="Rewrite" url="index.php?rewrite={R:1}" />'."\r\n";
            $iis .= "\t\t\t\t".'</rule>'."\r\n";
            $iis .= "\t\t\t".'</rules>'."\r\n";
            $iis .= "\t\t".'</rewrite>'."\r\n";
            $iis .= "\t".'</system.webServer>'."\r\n";
            $iis .= '</configuration>';
            $this->assign('iis', $iis);

            // 创建web.config
            $file_iis = ROOT_PATH.'web.config';
            $is_file_iis = is_file($file_iis);
            $this->assign('is_file_iis', $is_file_iis);
            if($mk == 'web_config') {
                $f = @fopen($file_iis, 'w');
                if (!$f) {
                    E(1, lang('no_write_permission'));
                } else {
                    $bytes = fwrite($f, $iis);
                    fclose($f);
                    if($bytes > 0) {
                        E(0, lang('create').'web.config '.lang('successfully'));
                    }else{
                        E(1, lang('create').' web.config '.lang('failed'));
                    }
                }
            }

            // 删除web.config
            if($del == 'web_config') {
                $ret = FALSE;
                try{ $is_file_iis && $ret = unlink($file_iis); }catch(Exception $e) {}
                if($ret) {
                    E(0, lang('delete').' web.config '.lang('successfully'));
                }else{
                    E(1, lang('delete').' web.config '.lang('failed'));
                }
            }

            // IIS6
            $path_file = $path_dir = '';
            $dh = opendir(ROOT_PATH);
            while($file = readdir($dh)) {
                if(preg_match('#^[\w]+$#', $file) && is_dir(ROOT_PATH.$file)) {
                    $path_dir .= $file.'|';
                }elseif(preg_match('#^\w[\w\.]+$#', $file) && is_file(ROOT_PATH.$file)) {
                    $path_file .= preg_quote($file).'|';
                }
            }

            $webdir = preg_quote($cfg['webdir']);
            $iis6 = '[ISAPI_Rewrite]'."\r\n\r\n";
            $iis6 .= 'RewriteRule '.$webdir.'('.trim($path_file, '|').') '.$webdir.'$1 [L]'."\r\n";
            $iis6 .= 'RewriteRule '.$webdir.'('.trim($path_dir, '|').')/(.*) '.$webdir.'$1/$2 [L]'."\r\n";
            $iis6 .= 'RewriteRule '.$webdir.'(.+) '.$webdir.'index\.php\?rewrite=$1 [L]';
            $this->assign('iis6', $iis6);

            // 创建httpd.ini
            $file_iis6 = ROOT_PATH.'httpd.ini';
            $is_file_iis6 = is_file($file_iis6);
            $this->assign('is_file_iis6', $is_file_iis6);
            if($mk == 'httpd_ini') {
                $f = @fopen($file_iis6, 'w');
                if (!$f) {
                    E(1, lang('no_write_permission'));
                } else {
                    $bytes = fwrite($f, $iis6);
                    fclose($f);
                    if($bytes > 0) {
                        E(0, lang('create').' httpd.ini '.lang('successfully'));
                    }else{
                        E(1, lang('create').' httpd.ini '.lang('failed'));
                    }
                }
            }

            // 删除httpd.ini
            if($del == 'httpd_ini') {
                $ret = FALSE;
                try{ $is_file_iis6 && $ret = unlink($file_iis6); }catch(Exception $e) {}
                if($ret) {
                    E(0, lang('delete').' httpd.ini '.lang('successfully'));
                }else{
                    E(1, lang('delete').' httpd.ini '.lang('failed'));
                }
            }

            $input = array();
            $input['parseurl'] = form::layui_loop('radio', 'parseurl', array('0'=>lang('url_option_0'), '1'=>lang('url_option_1')), $parseurl, '','lay-filter="parseurl"');
            $input['link_show'] = form::get_text('link_show', $cfg['link_show'], '', 'required="required" lay-verify="required"');
            $input['link_cate_end'] = form::get_text('link_cate_end', $cfg['link_cate_end'], '', 'required="required" lay-verify="required"');
            $input['link_cate_page_pre'] = form::get_text('link_cate_page_pre', $cfg['link_cate_page_pre'], '', 'required="required" lay-verify="required"');
            $input['link_cate_page_end'] = form::get_text('link_cate_page_end', $cfg['link_cate_page_end'], '', 'required="required" lay-verify="required"');
            $input['link_tag_pre'] = form::get_text('link_tag_pre', $cfg['link_tag_pre'], '', 'required="required" lay-verify="required"');
            $input['link_tag_end'] = form::get_text('link_tag_end', $cfg['link_tag_end'], '', 'required="required" lay-verify="required"');
            $input['link_tag_top'] = form::get_text('link_tag_top', $cfg['link_tag_top'], '', 'required="required" lay-verify="required"');
            $input['link_comment_pre'] = form::get_text('link_comment_pre', $cfg['link_comment_pre'], '', 'required="required" lay-verify="required"');
            $input['link_space_pre'] = form::get_text('link_space_pre', $cfg['link_space_pre'], '', 'required="required" lay-verify="required"');
            $input['link_space_end'] = form::get_text('link_space_end', $cfg['link_space_end'], '', 'required="required" lay-verify="required"');

            $arr = array('0'=>lang('tag_name'), '1'=>lang('tag_id'), 2=>lang('content_url_option_3'), 3=>'HashID');
            $input['link_tag_type'] = form::layui_loop('radio', 'link_tag_type', $arr, $cfg['link_tag_type']);

            // hook admin_setting_control_link_after.php
            $this->assign('input', $input);

            $this->display();
        }else{
            _trim($_POST);
            // 伪静态开关
            $parseurl = (int)R('parseurl', 'P');
            $file = APP_PATH.'config/config.inc.php';
            if(!_is_writable($file)) E(1, lang('config_inc_not_write'));
            $s = file_get_contents($file);
            $s = preg_replace("#'lecms_parseurl'\s*=>\s*\d,#", "'lecms_parseurl' => {$parseurl},", $s);
            if(!file_put_contents($file, $s)) E(1, lang('config_inc_write_failed'));

            // 关闭伪静态时，不需要更改伪静态参数
            if($parseurl == 0) {
                $this->runtime->truncate();
                E(0, lang('edit_successfully'));
            }

            // 智能生成内容链接参数
            $link_show = R('link_show', 'P');
            if(substr($link_show, 0, 10) == '{cid}/{id}' && strpos($link_show, '{', 10) === FALSE) {
                $link_show_type = 1;
                $link_show_end = (string)substr($link_show, 10);
            }elseif(substr($link_show, 0, 17) == '{cate_alias}/{id}' && strpos($link_show, '{', 17) === FALSE) {
                $link_show_type = 2;
                $link_show_end = (string)substr($link_show, 17);
            }elseif(substr($link_show, 0, 7) == '{alias}' && strpos($link_show, '{', 7) === FALSE) {
                $link_show_type = 3;
                $link_show_end = (string)substr($link_show, 7);
            }elseif(substr($link_show, 0, 10) == '{password}' && strpos($link_show, '{', 10) === FALSE) {
                $link_show_type = 4;
                $link_show_end = (string)substr($link_show, 10);
            }elseif(substr($link_show, 0, 4) == '{id}' && strpos($link_show, '{', 4) === FALSE) {
                $link_show_type = 5;
                $link_show_end = (string)substr($link_show, 4);
            }elseif(substr($link_show, 0, 20) == '{cate_alias}/{alias}' && strpos($link_show, '{', 20) === FALSE) {
                $link_show_type = 6;
                $link_show_end = (string)substr($link_show, 20);
            }elseif(substr($link_show, 0, 9) == '{hashids}' && strpos($link_show, '{', 9) === FALSE) {
                $link_show_type = 8;
                $link_show_end = (string)substr($link_show, 9);
            }else{
                $link_show_type = 7;
                $link_show_end = '';
            }
            // hook admin_setting_control_link_post_link_show_after.php

            $link_cate_page_pre = R('link_cate_page_pre', 'P');
            $link_cate_page_end = R('link_cate_page_end', 'P');
            $link_cate_end = R('link_cate_end', 'P');
            $link_tag_pre = R('link_tag_pre', 'P');
            $link_tag_end = R('link_tag_end', 'P');
            $link_tag_top = R('link_tag_top', 'P');
            $link_comment_pre = R('link_comment_pre', 'P');
            $link_space_pre = R('link_space_pre', 'P');
            $link_space_end = R('link_space_end', 'P');
            // hook admin_setting_control_link_post_data_after.php

            // 暂时不考虑过滤 标签URL前缀 和 评论URL后缀 重复问题
            if(empty($link_cate_page_pre)) E(1, lang('link_cate_page_pre_not_empty'));
            if(empty($link_cate_page_end)) E(1, lang('link_cate_page_end_not_empty'));
            if(empty($link_cate_end)) E(1, lang('link_cate_end_not_empty'));
            if(empty($link_tag_pre)) E(1, lang('link_tag_pre_not_empty'));
            if(empty($link_tag_end)) E(1, lang('link_tag_end_not_empty'));
            if(empty($link_tag_top)) E(1, lang('link_tag_top_not_empty'));
            if(empty($link_comment_pre)) E(1, lang('link_comment_pre_not_empty'));
            if(empty($link_space_pre)) E(1, lang('link_space_pre_not_empty'));
            if(empty($link_space_end)) E(1, lang('link_space_end_not_empty'));

            //不能重复的信息
            $compare_arr = array($link_cate_page_pre, $link_tag_pre, $link_tag_top, $link_comment_pre, $link_space_pre);
            // hook admin_setting_control_link_check_post_after.php

            if(count($compare_arr) != count(array_unique($compare_arr))){
                E(1, lang('duplicate_prefix'));
            }

            $this->kv->xset('link_show', $link_show, 'cfg');
            $this->kv->xset('link_show_type', $link_show_type, 'cfg');
            $this->kv->xset('link_show_end', $link_show_end, 'cfg');
            $this->kv->xset('link_cate_page_pre', $link_cate_page_pre, 'cfg');
            $this->kv->xset('link_cate_page_end', $link_cate_page_end, 'cfg');
            $this->kv->xset('link_cate_end', $link_cate_end, 'cfg');
            $this->kv->xset('link_tag_pre', $link_tag_pre, 'cfg');
            $this->kv->xset('link_tag_end', $link_tag_end, 'cfg');
            $this->kv->xset('link_tag_top', $link_tag_top, 'cfg');
            $this->kv->xset('link_comment_pre', $link_comment_pre, 'cfg');
            $this->kv->xset('link_space_pre', $link_space_pre, 'cfg');
            $this->kv->xset('link_space_end', $link_space_end, 'cfg');
            $this->kv->xset('link_tag_type', (int)R('link_tag_type', 'P'), 'cfg');

            // hook admin_setting_control_link_post_after.php

            $this->kv->save_changed();
            $this->runtime->truncate();

            E(0, lang('edit_successfully'));

        }
    }

    //用户设置
    public function user(){
        // hook admin_setting_control_user_before.php
        if(empty($_POST)) {
            $cfg = $this->kv->xget('cfg');
            $input = array();

            $arr = array(1=>lang('open'), 0=>lang('close'));

            $input['open_user'] = form::layui_loop('radio', 'open_user', $arr, $cfg['open_user']);
            $input['open_user_register'] = form::layui_loop('radio', 'open_user_register', $arr, $cfg['open_user_register']);
            $input['open_user_register_vcode'] = form::layui_loop('radio', 'open_user_register_vcode', $arr, $cfg['open_user_register_vcode']);
            $input['open_user_login'] = form::layui_loop('radio', 'open_user_login', $arr, $cfg['open_user_login']);
            $input['open_user_login_vcode'] = form::layui_loop('radio', 'open_user_login_vcode', $arr, $cfg['open_user_login_vcode']);
            $input['open_user_reset_password'] = form::layui_loop('radio', 'open_user_reset_password', $arr, $cfg['open_user_reset_password']);

            $input['user_avatar_width'] = form::get_number('user_avatar_width', $cfg['user_avatar_width'], '', 'required="required" lay-verify="required"');
            $input['user_avatar_height'] = form::get_number('user_avatar_height', $cfg['user_avatar_height'], '', 'required="required" lay-verify="required"');

            // hook admin_setting_control_user_after.php

            $this->assign('input', $input);
            $this->display();
        }else{
            _trim($_POST);
            $this->kv->xset('open_user', (int)R('open_user', 'P'), 'cfg');
            $this->kv->xset('open_user_register', (int)R('open_user_register', 'P'), 'cfg');
            $this->kv->xset('open_user_register_vcode', (int)R('open_user_register_vcode', 'P'), 'cfg');
            $this->kv->xset('open_user_login', (int)R('open_user_login', 'P'), 'cfg');
            $this->kv->xset('open_user_login_vcode', (int)R('open_user_login_vcode', 'P'), 'cfg');
            $this->kv->xset('open_user_reset_password', (int)R('open_user_reset_password', 'P'), 'cfg');
            $this->kv->xset('user_avatar_width', (int)R('user_avatar_width', 'P'), 'cfg');
            $this->kv->xset('user_avatar_height', (int)R('user_avatar_height', 'P'), 'cfg');

            // hook admin_setting_control_user_post_after.php

            $this->kv->save_changed();
            $this->runtime->delete('cfg');

            E(0, lang('edit_successfully'));
        }
    }

    // 上传设置
    public function attach() {
        // hook admin_setting_control_attach_before.php
        if(empty($_POST)) {
            $cfg = $this->kv->xget('cfg');
            $input = array();
            $input['up_img_ext'] = form::get_text('up_img_ext', $cfg['up_img_ext'], '', 'required="required" lay-verify="required"');
            $input['up_img_max_size'] = form::get_number('up_img_max_size', $cfg['up_img_max_size'], '', 'required="required" lay-verify="required"');
            $input['up_file_ext'] = form::get_text('up_file_ext', $cfg['up_file_ext'], '', 'required="required" lay-verify="required"');
            $input['up_file_max_size'] = form::get_number('up_file_max_size', $cfg['up_file_max_size'], '', 'required="required" lay-verify="required"');

            // hook admin_setting_control_attach_after.php

            $this->assign('input', $input);
            $this->display();
        }else{
            _trim($_POST);
            $this->kv->xset('up_img_ext', R('up_img_ext', 'P'), 'cfg');
            $this->kv->xset('up_img_max_size', R('up_img_max_size', 'P'), 'cfg');
            $this->kv->xset('up_file_ext', R('up_file_ext', 'P'), 'cfg');
            $this->kv->xset('up_file_max_size', R('up_file_max_size', 'P'), 'cfg');

            // hook admin_setting_control_attach_post_after.php

            $this->kv->save_changed();
            $this->runtime->delete('cfg');

            E(0, lang('edit_successfully'));
        }
    }

    // 图片设置
    public function image() {
        // hook admin_setting_control_image_before.php
        if(empty($_POST)) {
            $cfg = $this->kv->xget('cfg');
            $input = array();
            $input['thumb_type'] = form::layui_loop('radio', 'thumb_type', array('1'=>lang('thumb_type_1'), '2'=>lang('thumb_type_2'), '3'=>lang('thumb_type_3')), $cfg['thumb_type']);
            $input['thumb_quality'] = form::get_number('thumb_quality', $cfg['thumb_quality'], '', 'required="required" lay-verify="required"');

            $cfg['watermark_pos'] = isset($cfg['watermark_pos']) ? (int)$cfg['watermark_pos'] : 0;
            $input['watermark_pct'] = form::get_number('watermark_pct', $cfg['watermark_pct'], '', 'required="required" lay-verify="required"');

            // hook admin_setting_control_image_after.php

            $this->assign('input', $input);
            $this->assign('cfg', $cfg);
            $this->display();
        }else{
            $this->kv->xset('thumb_type', (int) R('thumb_type', 'P'), 'cfg');
            $this->kv->xset('thumb_quality', (int) R('thumb_quality', 'P'), 'cfg');
            $this->kv->xset('watermark_pos', (int) R('watermark_pos', 'P'), 'cfg');
            $this->kv->xset('watermark_pct', (int) R('watermark_pct', 'P'), 'cfg');

            // hook admin_setting_control_image_post_after.php

            $this->kv->save_changed();
            $this->runtime->delete('cfg');

            E(0, lang('edit_successfully'));
        }
    }

    //评论设置
    public function comment(){
        // hook admin_setting_control_comment_before.php
        if(empty($_POST)) {
            $cfg = $this->kv->xget('cfg');
            $input = array();
            $arr = array('1'=>lang('open'),'0'=>lang('close'));
            $input['open_comment'] = form::layui_loop('radio', 'open_comment', $arr, $cfg['open_comment']);
            $input['open_comment_vcode'] = form::layui_loop('radio', 'open_comment_vcode', $arr, $cfg['open_comment_vcode']);
            $input['open_no_login_comment'] = form::layui_loop('radio', 'open_no_login_comment', $arr, $cfg['open_no_login_comment']);
            $input['comment_default_author'] = form::get_text('comment_default_author', $cfg['comment_default_author'], '', 'required="required" lay-verify="required"');
            // hook admin_setting_control_comment_after.php

            $this->assign('input', $input);
            $this->display();
        }else{
            _trim($_POST);
            $this->kv->xset('open_comment', (int)R('open_comment', 'P'), 'cfg');
            $this->kv->xset('open_comment_vcode', (int)R('open_comment_vcode', 'P'), 'cfg');
            $this->kv->xset('open_no_login_comment', (int)R('open_no_login_comment', 'P'), 'cfg');
            $this->kv->xset('comment_default_author', R('comment_default_author', 'P'), 'cfg');
            // hook admin_setting_control_comment_post_after.php

            $this->kv->save_changed();
            $this->runtime->delete('cfg');

            E(0, lang('edit_successfully'));
        }
    }

    //邮件设置
    public function email(){
        // hook admin_setting_control_email_before.php
        if(empty($_POST)) {
            version_compare(PHP_VERSION, '5.5.0', '>') || $this->message(0, lang('php_version_5_5'), '', 1000);

            $cfg = $this->kv->xget('cfg');
            $input = array();
            $arr = array(1=>lang('open'), 0=>lang('close'));
            $input['open_email'] = form::get_radio_layui('open_email', $arr, $cfg['open_email']);
            $input['email_smtp'] = form::get_text('email_smtp', $cfg['email_smtp'], '', 'required="required" lay-verify="required"');
            $input['email_port'] = form::get_number('email_port', $cfg['email_port'], '', 'required="required" lay-verify="required"');
            $input['email_account'] = form::get_text('email_account', $cfg['email_account'], '', 'required="required" lay-verify="required"');
            $input['email_account_name'] = form::get_text('email_account_name', $cfg['email_account_name']);
            $input['email_password'] = form::get_text('email_password', $cfg['email_password'], '', 'required="required" lay-verify="required"');

            // hook admin_setting_control_email_after.php

            $this->assign('input', $input);
            $this->assign('cfg', $cfg);
            $this->display();
        }else{
            $this->kv->xset('open_email', (int) R('open_email', 'P'), 'cfg');
            $this->kv->xset('email_smtp', R('email_smtp', 'P'), 'cfg');
            $this->kv->xset('email_port', R('email_port', 'P'), 'cfg');
            $this->kv->xset('email_account', R('email_account', 'P'), 'cfg');
            $this->kv->xset('email_account_name', R('email_account_name', 'P'), 'cfg');
            $this->kv->xset('email_password', R('email_password', 'P'), 'cfg');

            // hook admin_setting_control_email_post_after.php

            $this->kv->save_changed();
            $this->runtime->delete('cfg');

            E(0, lang('edit_successfully'));
        }
    }

    //测试发邮件
    public function testemail(){
        if($_POST){
            set_time_limit(60);
            $toemail = trim( R('toemail','P') );
            empty($toemail) && E(1, lang('receive_email_not_empty'));
            $cfg = $this->kv->xget('cfg');

            empty($cfg['open_email']) && E(1, lang('open_email_0'));
            empty($cfg['email_smtp']) && E(1, lang('email_smtp_not_empty'));
            empty($cfg['email_port']) && E(1, lang('email_port_not_empty'));
            empty($cfg['email_account']) && E(1, lang('email_account_not_empty'));
            empty($cfg['email_password']) && E(1, lang('send_mail_password_not_empty'));

            //配置
            $config = array(
                'debug' => 0,
                'smtp' => $cfg['email_smtp'],
                'port' => $cfg['email_port'],
                'account' => $cfg['email_account'],
                'account_name' => isset($cfg['email_account_name']) ? $cfg['email_account_name'] : $cfg['email_account'],
                'password' => $cfg['email_password'],
                'to' => $toemail ,    //收件人
                'title' => lang('email_test_title'),  //邮件标题
                'body' => lang('email_test_body'),  //邮件内容
            );

            $emailObj = new email();

            $result = $emailObj->sendemail($config);
            if($result){
                E(0, lang('email_send_successfully'));
            }else{
                E(1, lang('email_send_failed'));
            }
        }
    }

    //安全设置
    public function security(){
        // hook admin_setting_control_security_before.php
        if(empty($_POST)) {
            $input = array();
            $cfg = $this->kv->xget('cfg');

            $arr = array(1=>lang('open'), 0=>lang('close'));

            $input['admin_vcode'] = form::layui_loop('radio', 'admin_vcode', $arr, $cfg['admin_vcode']);
            $input['admin_safe_entrance'] = form::layui_loop('radio', 'admin_safe_entrance', $arr, $cfg['admin_safe_entrance']);

            $admin_safe_auth = empty($cfg['admin_safe_auth']) ? random(6, 2) : $cfg['admin_safe_auth'];
            $input['admin_safe_auth'] = form::get_text('admin_safe_auth', $admin_safe_auth, '', 'required="required" lay-verify="required"');

            //安全提示
            $_show_tips = 1;
            $debug = isset($_ENV['_config']['debug']) ? (int)$_ENV['_config']['debug'] : 0;
            $debug_admin = isset($_ENV['_config']['debug_admin']) ? (int)$_ENV['_config']['debug_admin'] : 0;
            if($debug || $debug_admin){
                $debug_tips = lang('debug_tips');
            }else{
                $debug_tips = '';
            }

            $install_dir = ROOT_PATH.'install';
            if(is_dir($install_dir)){
                $install_tips = lang('del_install_tips');
            }else{
                $install_tips = '';
            }

            $default_admin_dir = ROOT_PATH.'admin';
            if(is_dir($default_admin_dir)){
                $default_admin_dir_tips = lang('default_admin_dir_tips');
            }else{
                $default_admin_dir_tips = '';
            }

            $url_rewrite_tips = '';
            if(empty($_ENV['_config']['lecms_parseurl'])){
                $url_rewrite_tips = lang('url_rewrite_tips');
            }

            $this->assign('show_tips', $_show_tips);
            $this->assign('debug_tips', $debug_tips);
            $this->assign('install_tips', $install_tips);
            $this->assign('default_admin_dir_tips', $default_admin_dir_tips);
            $this->assign('url_rewrite_tips', $url_rewrite_tips);

            // hook admin_setting_control_security_after.php
            $this->assign('input', $input);
            $this->display();
        }else{
            $this->kv->xset('admin_vcode', (int) R('admin_vcode', 'P'), 'cfg');
            $this->kv->xset('admin_safe_entrance', (int) R('admin_safe_entrance', 'P'), 'cfg');
            $this->kv->xset('admin_safe_auth', R('admin_safe_auth', 'P'), 'cfg');

            // hook admin_setting_control_security_post_after.php

            $this->kv->save_changed();
            $this->runtime->delete('cfg');

            E(0, lang('edit_successfully'));
        }
    }

    //重置安全密钥
    public function security_auth(){
        if($_POST){
            $admin_safe_auth = random(6, 2);
            $this->kv->xset('admin_safe_auth', $admin_safe_auth, 'cfg');
            $this->kv->save_changed();
            $this->runtime->delete('cfg');

            $r = array('err'=>0, 'msg'=>lang('opt_successfully'), 'data'=>$admin_safe_auth);
            echo json_encode($r);
            exit();
        }
    }

    //获取安全登录入口
    public function security_safe_url(){
        if($_POST){
            $safe_auth = R('safe_auth', 'P');
            $cfg = $this->kv->xget('cfg');
            if($cfg['admin_safe_auth'] != $safe_auth){
                $this->kv->xset('admin_safe_auth', $safe_auth, 'cfg');
                $this->kv->save_changed();
                $this->runtime->delete('cfg');
            }

            $current_url = http().$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            $replace = 'index-login-auth-'.$safe_auth;
            $admin_safe_entrance_url = str_replace('setting-security_safe_url-ajax-1', $replace, $current_url);

            E(0, $admin_safe_entrance_url);
        }
    }

    //其他设置
    public function other(){
        // hook admin_setting_control_other_before.php
        if(empty($_POST)) {
            $input = array();

            $debug = isset($_ENV['_config']['debug']) ? (int)$_ENV['_config']['debug'] : 0;
            $debug_admin = isset($_ENV['_config']['debug_admin']) ? (int)$_ENV['_config']['debug_admin'] : 0;

            $lang = isset($_ENV['_config']['lang']) ? $_ENV['_config']['lang'] : 'zh-cn';
            $lang_admin = isset($_ENV['_config']['admin_lang']) ? $_ENV['_config']['admin_lang'] : 'zh-cn';

            $php_error = isset($_ENV['_config']['php_error']) ? (int)$_ENV['_config']['php_error'] : 0;
            $php_error404 = isset($_ENV['_config']['php_error404']) ? (int)$_ENV['_config']['php_error404'] : 0;

            $arr = array('2'=>lang('open'),'0'=>lang('close'));
            $input['debug'] = form::layui_loop('radio', 'debug', $arr, $debug);
            $input['debug_admin'] = form::layui_loop('radio', 'debug_admin', $arr, $debug_admin);

            $arr = array('1'=>lang('enable'),'0'=>lang('disable'));
            $input['php_error'] = form::layui_loop('radio', 'php_error', $arr, $php_error);
            $input['php_error404'] = form::layui_loop('radio', 'php_error404', $arr, $php_error404);

            $arr1 = array('zh-cn'=>lang('zh-cn'),'en'=>lang('en'));
            $input['lang'] = form::layui_loop('select', 'lang', $arr1, $lang);
            $input['lang_admin'] = form::layui_loop('select', 'lang_admin', $arr1, $lang_admin);

            $cfg = $this->kv->xget('cfg');
            $arr = array('1'=>lang('yes'),'0'=>lang('no'));
            $close_website = isset($cfg['close_website']) ? (int)$cfg['close_website'] : 0;
            $close_search = isset($cfg['close_search']) ? (int)$cfg['close_search'] : 0;
            $close_views = isset($cfg['close_views']) ? (int)$cfg['close_views'] : 0;
            $auto_pic = isset($cfg['auto_pic']) ? (int)$cfg['auto_pic'] : 1;
            $input['close_website'] = form::layui_loop('radio', 'close_website', $arr, $close_website);
            $input['close_search'] = form::layui_loop('radio', 'close_search', $arr, $close_search);
            $input['close_views'] = form::layui_loop('radio', 'close_views', $arr, $close_views);
            $input['auto_pic'] = form::layui_loop('radio', 'auto_pic', $arr, $auto_pic);

            $arr = array('0'=>lang('left_menu'),'1'=>lang('left_and_top_menu'));
            $input['admin_layout'] = form::layui_loop('radio', 'admin_layout', $arr, $cfg['admin_layout']);

            $arr = array('0'=>lang('url_absolute_path'),'1'=>lang('url_relative_path'));
            $input['url_path'] = form::layui_loop('radio', 'url_path', $arr, $cfg['url_path']);

            // hook admin_setting_control_other_after.php

            $this->assign('input', $input);
            $this->display();
        }else{
            _trim($_POST);

            $this->kv->xset('close_website', (int)R('close_website', 'P'), 'cfg');
            $this->kv->xset('close_search', (int)R('close_search', 'P'), 'cfg');
            $this->kv->xset('close_views', (int)R('close_views', 'P'), 'cfg');
            $this->kv->xset('auto_pic', (int)R('auto_pic', 'P'), 'cfg');
            $this->kv->xset('admin_layout', (int)R('admin_layout', 'P'), 'cfg');
            $this->kv->xset('url_path', (int)R('url_path', 'P'), 'cfg');

            $debug = (int)R('debug','P');
            $debug_admin = (int)R('debug_admin','P');

            $php_error = (int)R('php_error','P');
            $php_error404 = (int)R('php_error404','P');

            $lang = R('lang','P');
            $lang_admin = R('lang_admin','P');

            //$url_suffix = R('url_suffix','P');

            $file = CONFIG_PATH.'config.inc.php';
            $s = file_get_contents($file);

            $s = preg_replace("#'php_error' => '\d*',#", "'php_error' => '".$php_error."',", $s);
            $s = preg_replace("#'php_error404' => '\d*',#", "'php_error404' => '".$php_error404."',", $s);
            $s = preg_replace("#'debug' => '\d*',#", "'debug' => '".$debug."',", $s);
            $s = preg_replace("#'debug_admin' => '\d*',#", "'debug_admin' => '".$debug_admin."',", $s);
            $s = preg_replace("#'lang' => '[\w-]*',#", "'lang' => '".addslashes($lang)."',", $s);
            $s = preg_replace("#'admin_lang' => '[\w-]*',#", "'admin_lang' => '".addslashes($lang_admin)."',", $s);
            //$s = preg_replace("#'url_suffix' => '(.)*',#", "'url_suffix' => '".addslashes($url_suffix)."',", $s);

            // hook admin_setting_control_other_post_after.php

            $this->kv->save_changed();
            $this->runtime->delete('cfg');
            $ret = file_put_contents($file, $s);
            if($ret){
                //删除后台菜单缓存
                $this->runtime->delete('admin_navigation');

                E(0, lang('edit_successfully'));
            }else{
                E(1, lang('edit_failed'));
            }
        }
    }

    // hook admin_setting_control_after.php
}
<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2023-08-15
 * Time: 16:50
 * Description:伪静态路由解析控制器，只要开启伪静态，路由会全部进入到这里
 */
defined('ROOT_PATH') or exit;
// hook parseurl_control_start.php
class parseurl_control extends control{
    private $data = array();
    private $integer_pattern = '/^[1-9]\d*$/';   //正整数正则

    public function index() { // hook parseurl_control_index_before.php

        //访问根路径 / 时（rewrite 为空），直接进入前台首页
        if(isset($_GET['rewrite']) && $_GET['rewrite'] === '' && count($_GET) === 1) {
            $_GET['control'] = 'index';
            $_GET['action'] = 'index';
            return;
        }

        if(empty($_GET)) {
            //访问根路径 / 时，直接进入前台首页
            $_GET['control'] = 'index';
            $_GET['action'] = 'index';
            return;
        }

        if(!empty($_ENV['_config']['lecms_parseurl']) && isset($_GET['rewrite']) && !empty($_GET['rewrite'])) {
            if( isset($this->data['cfg']) ) {
                $cfg = $this->data['cfg'];
            } else { $cfg = $this->runtime->xget(); $this->data['cfg'] = $cfg;
            }

            $uri = $_GET['rewrite'];
            unset($_GET['rewrite']);

            //访问 域名/index.php?rewrite=xx ， 直接 301 重定向到 域名/xx
            $request_uri = isset($_SERVER['REQUEST_URI']) ? strtolower($_SERVER['REQUEST_URI']) : '';
            if( $request_uri && strpos($request_uri, '?rewrite=') !== false ){
                http_location($cfg['weburl'].$uri, '301');
            }

            $url_suffix = $_ENV['_config']['url_suffix'];
            $url_suffix_len = strlen($url_suffix);

            // hook parseurl_control_index_rewrite_before.php

            //站点地图
            $sitemap_uri = array('sitemap.xml', 'sitemap.html', 'sitemap.txt');
            if(in_array($uri, $sitemap_uri)){
                $u_arr = explode('.', $uri);
                $_GET['control'] = 'sitemap';
                $_GET['action'] = $u_arr[1];
                return;
            }

            // hook parseurl_control_index_link_cate_before.php
            $r = $this->category_url($cfg, $uri);
            if($r){return;}
            // hook parseurl_control_index_link_cate_after.php

            // hook parseurl_control_index_link_show_before.php
            $r = $this->content_url($cfg, $uri);
            if($r){return;}
            // hook parseurl_control_index_link_show_after.php

            // hook parseurl_control_index_tag_before.php
            $r = $this->tag_url($cfg, $uri);
            if($r){return;}
            // hook parseurl_control_index_tag_after.php

            // hook parseurl_control_index_search_before.php
            $r = $this->search_url($cfg, $uri);
            if($r){return;}
            // hook parseurl_control_index_search_after.php

            // hook parseurl_control_index_comment_before.php
            $r = $this->comment_url($cfg, $uri);
            if($r){return;}
            // hook parseurl_control_index_comment_after.php

            // hook parseurl_control_index_index_page_before.php
            $r = $this->index_page_url($cfg, $uri);
            if($r){return;}
            // hook parseurl_control_index_index_page_after.php

            // hook parseurl_control_index_tag_like_before.php
            $r = $this->tag_like_url($cfg, $uri);
            if($r){return;}
            // hook parseurl_control_index_tag_like_after.php

            // hook parseurl_control_index_user_before.php
            $r = $this->user_url($cfg, $uri);
            if($r){return;}
            // hook parseurl_control_index_user_after.php

            // hook parseurl_control_index_model_before.php
            $r = $this->model_url($cfg, $uri);
            if($r){return;}
            // hook parseurl_control_index_model_after.php

            // hook parseurl_control_index_flags_before.php
            $r = $this->flags_url($cfg, $uri);
            if($r){return;}
            // hook parseurl_control_index_flags_after.php

            // hook parseurl_control_index_space_before.php
            $r = $this->space_url($cfg, $uri);
            if($r){return;}
            // hook parseurl_control_index_space_after.php

            // hook parseurl_control_index_rewrite_after.php
        }
        //伪静态结束------------------------------------------------------------------------------------------------------

        // 伪静态时，如果 $uri 有值，但没有解析到相关 $_GET 时，就提示404
        if(empty($_GET) && isset($uri) && !empty($uri)) {
            core::error404();
        }

        if( !isset($_GET['control']) ) {
            // hook parseurl_control_index_other_before.php
            $r = $this->other_url();
            if($r){return;}
            // hook parseurl_control_index_other_after.php
        }

        // hook parseurl_control_index_after.php
    }

    //---------------------------------------------------------------------- 以下是各模块URL解析的具体函数实现

    //模型页URL解析
    protected function model_url($cfg = array(), $uri = ''){
        // hook parseurl_control_model_url_before.php

        //模型信息 table=>mid
        $model_arr = array_flip($cfg['table_arr']);

        // 模型URL未设置后缀/的情况，301重定向到已设置后缀的URL
        if( isset($model_arr[$uri]) ) {
            http_location($cfg['weburl'].$uri.'/', '301');
        }

        $_GET['control'] = 'model';
        $_GET['action'] = 'index';

        if(substr($uri, -1) == '/'){
            $newurl = substr($uri, 0, -1);
        } else { $newurl = $uri;
        }

        //模型首页URL
        if( isset($model_arr[$newurl]) ) {
            $_GET['mid'] = (int)$model_arr[$newurl];
            return true;
        }

        //模型分页URL
        $u_arr = explode('/', $newurl);
        if( isset($model_arr[$u_arr[0]]) ) {
            $_GET['mid'] = (int)$model_arr[$u_arr[0]];
            //分页
            if( isset($u_arr[1]) ){
                if($page = $this->page_check($u_arr[1])){
                    $_GET['page'] = $page;
                } else { core::error404();
                }
            }
            return true;
        }

        // hook parseurl_control_model_url_after.php

        unset($_GET['control']);
        unset($_GET['action']);
        return false;
    }

    //分类URL解析
    protected function category_url($cfg = array(), $uri = ''){
        // hook parseurl_control_category_url_before.php

        //分类信息 alias=>cid
        $cate_arr = array_flip($cfg['cate_arr']);

        // 分类URL未设置后缀的情况，301重定向到已设置后缀的URL
        if( isset($cate_arr[$uri]) ) {
            http_location($cfg['weburl'].$uri.$cfg['link_cate_end'], '301');
        }

        $_GET['control'] = 'cate';
        $_GET['action'] = 'index';

        $len = strlen($cfg['link_cate_end']);
        //分页首页URL
        if(substr($uri, -$len) == $cfg['link_cate_end']) {
            $newurl = substr($uri, 0, -$len);
            if( isset($cate_arr[$newurl]) ) {
                $_GET['cid'] = (int)$cate_arr[$newurl];
                return true;
            }
        }

        //分类URL分页的情况
        if(strpos($uri, $cfg['link_cate_page_pre']) !== FALSE) {
            $len = strlen($cfg['link_cate_page_end']);
            if(substr($uri, -$len) == $cfg['link_cate_page_end']) {
                $newurl = substr($uri, 0, -$len);
                $u_arr = explode($cfg['link_cate_page_pre'], $newurl);
                if( isset($cate_arr[$u_arr[0]]) ) {
                    $_GET['cid'] = (int)$cate_arr[$u_arr[0]];
                    //分页
                    if( isset($u_arr[1]) ){
                        if($this->integer_check($u_arr[1])){
                            $_GET['page'] = $u_arr[1];
                        } else { core::error404();
                        }
                    }
                    return true;
                }
            }
        }

        // hook parseurl_control_category_url_after.php

        unset($_GET['control']);
        unset($_GET['action']);
        return false;
    }

    //内容URL解析
    protected function content_url($cfg = array(), $uri = ''){
        // hook parseurl_control_content_url_before.php

        $link_show_end = $cfg['link_show_end'];
        $link_show_end_len = strlen($link_show_end);
        $cate_arr = array_flip($cfg['cate_arr']);
        $newurl = $link_show_end_len ? substr($uri, 0, -$link_show_end_len) : $uri;

        $_GET['control'] = 'show';
        $_GET['action'] = 'index';
		// hook parseurl_control_content_url_switch_before.php
        switch ($cfg['link_show_type']){
            case 1: //数字型
                // hook parseurl_control_content_url_switch_1_before.php
                preg_match("/^(\d+)\/(\d+)$/i", $newurl, $mat);
                if( isset($mat[2]) ){
                    $_GET['cid'] = $mat[1];
                    $_GET['id'] = $mat[2];
                    return true;
                }
                // hook parseurl_control_content_url_switch_1_after.php
                break;
            case 2: //推荐型
                // hook parseurl_control_content_url_switch_2_before.php
                preg_match("/^(\w+)\/(\d+)$/i", $newurl, $mat);
                if( isset($mat[2]) && isset($cate_arr[$mat[1]]) ){
                    $_GET['cid'] = $cate_arr[$mat[1]];
                    $_GET['id'] = $mat[2];
                    return true;
                }
                // hook parseurl_control_content_url_switch_2_after.php
                break;
            case 3: //别名型
                // hook parseurl_control_content_url_switch_3_before.php
                preg_match("/^(\d+)\_(\d+)$/i", $newurl, $mat); //没有设置别名，将用 cid_id 组合
                if( isset($mat[2]) ) {
                    $_GET['cid'] = $mat[1];
                    $_GET['id'] = $mat[2];
                    return true;
                }elseif( preg_match('/^[a-zA-Z0-9-_]+$/i', $newurl) ) {
                    $row = $this->only_alias->get($newurl);
                    if( !empty($row) ) {
                        $_GET['cid'] = $row['cid'];
                        $_GET['id'] = $row['id'];
                        return true;
                    }
                }
                // hook parseurl_control_content_url_switch_3_after.php
                break;
            case 4: //加密型
                // hook parseurl_control_content_url_switch_4_before.php
                $newurl = decrypt($newurl);//解密得到 cid_id
                preg_match("/^(\d+)\_(\d+)$/i", $newurl, $mat);
                if( isset($mat[2]) ) {
                    $_GET['cid'] = $mat[1];
                    $_GET['id'] = $mat[2];
                    return true;
                }
                // hook parseurl_control_content_url_switch_4_after.php
                break;
            case 5: //ID型
                // hook parseurl_control_content_url_switch_5_before.php
                if($this->integer_check($newurl)){
                    $_GET['mid'] = 2;
                    $_GET['id'] = $newurl;
                    return true;
                }
                preg_match("/^(\d+)\_(\d+)$/i", $newurl, $mat);
                if( isset($mat[2]) ) {
                    if( !$this->mid_check($mat[1], $cfg) ){core::error404();}
                    $_GET['mid'] = $mat[1];
                    $_GET['id'] = $mat[2];
                    return true;
                }
                // hook parseurl_control_content_url_switch_5_after.php
                break;
            case 6: //别名组合型
                // hook parseurl_control_content_url_switch_6_before.php
                $u_arr = explode('/', $newurl);
                if( isset($u_arr[1]) && isset($cate_arr[$u_arr[0]]) ){
                    $cid = (int)$cate_arr[$u_arr[0]];
                    // 如果没有设置别名，将用 cid_id 组合
                    preg_match("/^(\d+)\_(\d+)$/i", $u_arr[1], $mat);
                    if(isset($mat[2]) && $mat[1] == $cid) {
                        $_GET['cid'] = $mat[1];
                        $_GET['id'] = $mat[2];
                        return true;
                    }elseif(preg_match('/^[a-zA-Z0-9-_]+$/i', $u_arr[1])) {
                        $row = $this->only_alias->get($u_arr[1]);
                        if(!empty($row) && $row['cid'] == $cid) {
                            $_GET['cid'] = $row['cid'];
                            $_GET['id'] = $row['id'];
                            return true;
                        }
                    }
                }
                // hook parseurl_control_content_url_switch_6_after.php
                break;
            case 7: //灵活型
                // hook parseurl_control_content_url_switch_7_before.php
                $quote = preg_quote($cfg['link_show'], '#');
                $quote = strtr($quote, array(
                    '\{cid\}' => '(?<cid>\d+)',
                    '\{mid\}' => '(?<mid>\d+)',
                    '\{id\}' => '(?<id>\d+)',
                    '\{alias\}' => '(?<alias>\w+)',
                    '\{cate_alias\}' => '(?<cate_alias>\w+)',
                    '\{password\}' => '(?<password>\w+)',
                    '\{ymd\}' => '(?<ymd>\d{8})',
                    '\{y\}' => '(?<y>\d{4})',
                    '\{m\}' => '(?<m>\d{2})',
                    '\{d\}' => '(?<d>\d{2})',
                    '\{auth_key\}' => '(?<auth_key>\w+)',
                    '\{hashids\}' => '(?<hashids>\w+)'
                ));
                // hook parseurl_control_content_url_switch_7_quote_after.php
                preg_match('#'.$quote.'#', $uri, $mat);
                if($mat){
                    //用于control验证日期
                    isset($mat['ymd']) AND $_GET['date_ymd'] = $mat['ymd'];
                    isset($mat['y']) AND $_GET['date_y'] = $mat['y'];
                    isset($mat['m']) AND $_GET['date_m'] = $mat['m'];
                    isset($mat['d']) AND $_GET['date_d'] = $mat['d'];

                    $auth_key = $_ENV['_config']['auth_key'];
                    if( isset($mat['auth_key']) && $mat['auth_key'] != substr(md5($auth_key), 0, 6) ){
                        core::error404();
                    }

                    if( isset($mat['cid']) && isset($mat['id']) ) { // {cid} {id} 合组
                        $_GET['cid'] = $mat['cid'];
                        $_GET['id'] = $mat['id'];
                        return true;
                    }elseif( isset($mat['mid']) && isset($mat['id']) && $this->mid_check($mat['mid'], $cfg) ) { // {mid} {id} 合组
                        $_GET['mid'] = $mat['mid'];
                        $_GET['id'] = $mat['id'];
                        return true;
                    }elseif( isset($mat['cate_alias']) && isset($mat['id']) ) { // {cate_alias} {id} 合组
                        $_GET['cid'] = isset($cate_arr[$mat['cate_alias']]) ? $cate_arr[$mat['cate_alias']] : 0;
                        empty($_GET['cid']) && core::error404();

                        $_GET['id'] = $mat['id'];
                        return true;
                    }elseif( isset($mat['password']) ) { // {password}
                        $newurl = decrypt($mat['password']);//解密得到 cid_id
                        preg_match("/^(\d+)\_(\d+)$/i", $newurl, $mat);
                        if( isset($mat[2]) ) {
                            $_GET['cid'] = $mat[1];
                            $_GET['id'] = $mat[2];
                            return true;
                        }
                    }elseif( isset($mat['alias']) ) { // {alias}
                        preg_match("/^(\d+)\_(\d+)$/i", $mat['alias'], $mat2);  //没有设置别名，将用 cid_id 组合
                        if( isset($mat2[2]) ) {
                            $_GET['cid'] = $mat2[1];
                            $_GET['id'] = $mat2[2];
                            return true;
                        }
                        $row = $this->only_alias->get($mat['alias']);
                        if(!empty($row)) {
                            $_GET['cid'] = $row['cid'];
                            $_GET['id'] = $row['id'];
                            return true;
                        }
                    }elseif( isset($mat['hashids']) ) { // {hashids}
                        $newurl = hashids_decrypt($mat['hashids']);//解密得到 cid id 数组
                        if(is_array($newurl) && isset($newurl[1])){
                            $_GET['cid'] = $newurl[0];
                            $_GET['id'] = $newurl[1];
                            return true;
                        }
                    }
                    // hook parseurl_control_content_url_switch_7_mat_after.php

                    // 比如article/id.html，只能一个文章模型（多模型的不行，没法区分id属于那个模型的），因此丢到最后
                    if ( isset($mat['id']) && $this->integer_check($mat['id']) ){
                        $u_arr = explode('/', $uri);
                        if( substr($cfg['link_show'], 0, strlen($u_arr[0])) == $u_arr[0] ){
                            $_GET['mid'] = 2;
                            $_GET['id'] = $mat['id'];
                            return true;
                        }
                    }
                }
                // hook parseurl_control_content_url_switch_7_after.php
                break;

            case 8: //HashIDS
                // hook parseurl_control_content_url_switch_8_before.php
                $newurl = hashids_decrypt($newurl);//解密得到 cid id 数组
                if(is_array($newurl) && isset($newurl[1])){
                    $_GET['cid'] = $newurl[0];
                    $_GET['id'] = $newurl[1];
                    return true;
                }
                // hook parseurl_control_content_url_switch_8_after.php
                break;
            // hook parseurl_control_content_url_switch_end.php
        }

        // hook parseurl_control_content_url_after.php

        unset($_GET['control']);
        unset($_GET['action']);
        return false;
    }

    //标签URL解析
    protected function tag_url($cfg = array(), $uri = ''){
        // hook parseurl_control_tag_url_before.php
        $len = strlen($cfg['link_tag_pre']);
        if(substr($uri, 0, $len) == $cfg['link_tag_pre']) {
            $len2 = strlen($cfg['link_tag_end']);

            if(substr($uri, -$len2) == $cfg['link_tag_end']) {
                $_GET['control'] = 'tag';
                $_GET['action'] = 'index';

                $newurl = substr($uri, $len, -$len2);
                $u_arr = explode('/', $newurl);
                $u_arr_count = count($u_arr);
                if($u_arr_count > 2){ core::error404(); }

                //分页
                if( isset($u_arr[1]) ){
                    $page = $this->page_check($u_arr[1]);
                    if($page){
                        $_GET['page'] = $page;
                    } else { core::error404();
                    }
                }

                switch ($cfg['link_tag_type']){
                    case 0:
                        // hook parseurl_control_tag_url_switch_0_before.php
                        preg_match('/^(\d+)\_(.+)$/i', $u_arr[0], $mat);
                        if( isset($mat[2]) ) {
                            if( !$this->mid_check($mat[1], $cfg) ){core::error404();}
                            $_GET['mid'] = $mat[1];
                            $_GET['name'] = $mat[2];
                            return true;
                        } else { $_GET['mid'] = 2;
                            $_GET['name'] = $u_arr[0];
                            return true;
                        }
                        // hook parseurl_control_tag_url_switch_0_after.php
                        break;
                    case 1:
                        // hook parseurl_control_tag_url_switch_1_before.php
                        preg_match("/^(\d+)\_(\d+)$/i", $u_arr[0], $mat);
                        if( isset($mat[2]) ) {
                            if( !$this->mid_check($mat[1], $cfg) ){core::error404();}
                            $_GET['mid'] = $mat[1];
                            $_GET['tagid'] = $mat[2];
                            return true;
                        }elseif( $this->integer_check($u_arr[0]) ){
                            $_GET['mid'] = 2;
                            $_GET['tagid'] = $u_arr[0];
                            return true;
                        }
                        // hook parseurl_control_tag_url_switch_1_after.php
                        break;
                    case 2:
                        // hook parseurl_control_tag_url_switch_2_before.php
                        $newurl = decrypt($u_arr[0]);//解密得到 mid_tagid
                        preg_match("/^(\d+)\_(\d+)$/i", $newurl, $mat);
                        if( isset($mat[2]) ){
                            if( !$this->mid_check($mat[1], $cfg) ){core::error404();}
                            $_GET['mid'] = (int)$mat[1];
                            $_GET['tagid'] = (int)$mat[2];
                            return true;
                        }
                        // hook parseurl_control_tag_url_switch_2_after.php
                        break;
                    case 3:
                        // hook parseurl_control_tag_url_switch_3_before.php
                        $newurl = hashids_decrypt($u_arr[0]);//解密得到 mid tagid 数组
                        if(is_array($newurl) && isset($newurl[1])){
                            $_GET['mid'] = (int)$newurl[0];
                            $_GET['tagid'] = (int)$newurl[1];
                            return true;
                        }
                        // hook parseurl_control_tag_url_switch_3_after.php
                        break;
                    // hook parseurl_control_tag_url_switch_end.php
                }
            } else { //尝试301跳转到带后缀的链接试试看~
                http_location($cfg['weburl'].$uri.$cfg['link_tag_end'], '301');
            }
        }

        // hook parseurl_control_tag_url_after.php
        unset($_GET['control']);
        unset($_GET['action']);
        return false;
    }

    //搜索URL解析
    protected function search_url($cfg = array(), $uri = ''){
        // hook parseurl_control_search_url_before.php
        if(substr($uri, 0, 7) == 'search/') {
            if(substr($uri, -1) != '/'){$uri .= '/';}
            $newurl = substr($uri, 7, -1);
            $uarr = explode('/', $newurl);

            //模型ID
            if(isset($uarr[0]) && substr($uarr[0], 0 ,4) == 'mid_'){
                $mid = substr($uarr[0], 4);
                if($this->mid_check($mid, $cfg)){
                    $_GET['mid'] = $mid;
                    array_shift($uarr);
                } else { core::error404();
                }
            } else { $_GET['mid'] = 2;
            }

            //排除多余的参数
            if(count($uarr) > 2){core::error404();}

            //关键词
            $_GET['keyword'] = $uarr[0];

            //分页
            if( isset($uarr[1]) ){
                $page = $this->page_check($uarr[1]);
                if($page){
                    $_GET['page'] = $page;
                } else { core::error404();
                }
            }

            $_GET['control'] = 'search';
            $_GET['action'] = 'index';
            return true;
        }

        //搜索页面链接解析
        $url_suffix = isset($_ENV['_config']['url_suffix']) ? $_ENV['_config']['url_suffix'] : '.html';
        $url_suffix_len = strlen($url_suffix);
        if(substr($uri, -$url_suffix_len) == $url_suffix && substr($uri, 0, -$url_suffix_len) == 'so') {
            $_GET['control'] = 'search';
            $_GET['action'] = 'so';
            return true;
        }

        // hook parseurl_control_search_url_after.php
        return false;
    }

    //评论URL解析
    protected function comment_url($cfg = array(), $uri = ''){
        // hook parseurl_control_comment_url_before.php
        $len = strlen($cfg['link_comment_pre']);
        if(substr($uri, 0, $len) == $cfg['link_comment_pre']) {
            $url_suffix = isset($_ENV['_config']['url_suffix']) ? $_ENV['_config']['url_suffix'] : '.html';
            $url_suffix_len = strlen($url_suffix);
            if(substr($uri, -$url_suffix_len) == $url_suffix) {
                $newurl = substr($uri, $len, -$url_suffix_len);
                $u_arr = explode('_', $newurl);
                if(count($u_arr) > 1) {
                    $_GET['control'] = 'comment';
                    $_GET['action'] = 'index';
                    $_GET['cid'] = $u_arr[0];
                    $_GET['id'] = $u_arr[1];
                    //分页
                    if(isset($u_arr[2])){
                        if($this->integer_check($u_arr[2])){
                            $_GET['page'] = $u_arr[2];
                        } else { core::error404();
                        }
                    }
                    return true;
                }
            }
        }
        // hook parseurl_control_comment_url_after.php
        return false;
    }

    //首页分页URL解析
    protected function index_page_url($cfg = array(), $uri = ''){
        // hook parseurl_control_index_page_url_before.php
        $url_suffix = isset($_ENV['_config']['url_suffix']) ? $_ENV['_config']['url_suffix'] : '.html';
        $url_suffix_len = strlen($url_suffix);
        if(substr($uri, 0, 6) == 'index_' && substr($uri, -$url_suffix_len) == $url_suffix) {
            $newurl = substr($uri, 0, -$url_suffix_len);
            preg_match("/^index_(\d+)$/i", $newurl, $mat);
            if( isset($mat[1]) ){
                if(!$this->integer_check($mat[1])){core::error404();}
                $_GET['control'] = 'index';
                $_GET['action'] = 'index';
                $_GET['mid'] = 2;
                $_GET['page'] = $mat[1];
                return true;
            }
            preg_match("/^index_(\d+)_(\d+)$/i", $newurl, $mat);
            if( isset($mat[2]) ){
                if(!$this->mid_check($mat[1], $cfg)){core::error404();}
                if(!$this->integer_check($mat[2])){core::error404();}

                $_GET['control'] = 'index';
                $_GET['action'] = 'index';
                $_GET['mid'] = $mat[1];
                $_GET['page'] = $mat[2];
                return true;
            }
        }
        // hook parseurl_control_index_page_url_after.php
        return false;
    }

    //热门标签 全部标签 URL解析
    protected function tag_like_url($cfg = array(), $uri = ''){
        // hook parseurl_control_tag_like_url_before.php
        // 热门标签
        if($uri == $cfg['link_tag_top'] || $uri == $cfg['link_tag_top'].'/') {
            if($uri == $cfg['link_tag_top']){
                http_location($cfg['weburl'].$uri.'/', '301');
            }
            $_GET['control'] = 'tag';
            $_GET['action'] = 'top';
            return true;
        }
        //全部标签
        if(substr($uri, 0, 8) == 'tag_all/' || substr($uri, 0, 7) == 'tag_all'){
            if(substr($uri, -1) != '/'){
                http_location($cfg['weburl'].$uri.'/', '301');
            }

            $u_arr = explode('/', $uri);
            if($u_arr[0] != 'tag_all'){
                core::error404();
            } else { unset($u_arr);
            }

            $_GET['control'] = 'tag';
            $_GET['action'] = 'all';
            $newurl = substr($uri, 8, -1);
            if($newurl){
                if(is_numeric($newurl) && $newurl > 0){
                    $_GET['mid'] = 2;
                    $_GET['page'] = $newurl;
                } else { $u_arr = explode('_', $newurl);
                    if(count($u_arr) > 2){core::error404();}
                    if(!$this->mid_check($u_arr[0], $cfg)){core::error404();}

                    $_GET['mid'] = $u_arr[0];
                    if(is_numeric($u_arr[1]) && $u_arr[1] > 0){
                        $_GET['page'] = $u_arr[1];
                    } else { core::error404();
                    }
                }
            }
            return true;
        }
        // hook parseurl_control_tag_like_url_after.php
        return false;
    }

    //用户中心URL解析
    protected function user_url($cfg = array(), $uri = ''){
        // hook parseurl_control_user_url_before.php
        $url_suffix = isset($_ENV['_config']['url_suffix']) ? $_ENV['_config']['url_suffix'] : '.html';
        $url_suffix_len = strlen($url_suffix);
        $newurl = substr($uri, 0, -$url_suffix_len);
        if( preg_match('/^user-[a-z0-9-]+$/i', $newurl) || preg_match('/^my-[a-z0-9-]+$/i', $newurl) ){
            $u_arr = explode('-', $newurl);
            if(count($u_arr) > 1) {
                $_GET['control'] = $u_arr[0];
                array_shift($u_arr);
                $_GET['action'] = $u_arr[0];
                array_shift($u_arr);
                $num = count($u_arr);
                for($i=0; $i<$num; $i+=2){
                    isset($u_arr[$i+1]) && $_GET[$u_arr[$i]] = $u_arr[$i+1];
                }
                return true;
            }
        }
        // hook parseurl_control_user_url_after.php
        return false;
    }

    //属性内容URL解析
    protected function flags_url($cfg = array(), $uri = ''){
        // hook parseurl_control_flags_url_before.php
        if(substr($uri, 0, 6) == 'flags/'){
            if(substr($uri, -1) == '/'){$uri = substr($uri, 0,-1);}
            $u_arr = explode('/', $uri);
            if( isset($u_arr[1]) ){
                $_GET['control'] = 'flags';
                $_GET['action'] = 'index';

                $u_arr_1 = explode('_', $u_arr[1]);
                if(isset($u_arr_1[1])){
                    $_GET['mid'] = $u_arr_1[0];
                    if(!$this->mid_check($_GET['mid'], $cfg)){core::error404();}
                    $_GET['flag'] = $u_arr_1[1];
                } else { $_GET['mid'] = 2;
                    $_GET['flag'] = $u_arr[1];
                }

                if(!isset($this->cms_content->flag_arr[$_GET['flag']])){core::error404();}

                //分页
                if( isset($u_arr[2]) ){
                    $page = $this->page_check($u_arr[2]);
                    if($page){
                        $_GET['page'] = $page;
                    } else { core::error404();
                    }
                }
                return true;
            }
        }
        // hook parseurl_control_flags_url_after.php
        return false;
    }

    //个人空间URL解析
    protected function space_url($cfg = array(), $uri = ''){
        // hook parseurl_control_space_url_before.php
        $len = strlen($cfg['link_space_pre']);
        if(substr($uri, 0, $len) == $cfg['link_space_pre']) {
            $len2 = strlen($cfg['link_space_end']);
            if(substr($uri, -$len2) == $cfg['link_space_end']) {
                $newurl = substr($uri, $len, -$len2);
                $u_arr = explode('/', $newurl);
                if( $this->integer_check($u_arr[0]) ){
                    $_GET['control'] = 'space';
                    $_GET['action'] = 'index';
                    $_GET['uid'] = $u_arr[0];
                    //分页
                    if( isset($u_arr[1]) ){
                        $page = $this->page_check($u_arr[1]);
                        if($page){
                            $_GET['page'] = $page;
                        } else { core::error404();
                        }
                    }
                }
            }
        }
        // hook parseurl_control_space_url_after.php
        return false;
    }

    //动态URL解析
    protected function other_url(){
        // hook parseurl_control_other_url_before.php
        if(isset($_GET['u'])) {
            $u = $_GET['u'];
            unset($_GET['u']);
        }elseif(!empty($_SERVER['PATH_INFO'])) {
            $u = R('PATH_INFO', 'S');
        } else { $_GET = array();
            $u = R('QUERY_STRING', 'S');
        }

        //清除URL后缀
        $url_suffix = C('url_suffix');
        if($url_suffix) {
            $suf_len = strlen($url_suffix);
            if(substr($u, -($suf_len)) == $url_suffix) $u = substr($u, 0, -($suf_len));
        }

        $uarr = explode('&', $u);
        $u = $uarr[0];
        if(count($uarr) > 1){
            array_shift($uarr);
            foreach ($uarr as $v){
                $varr = explode('=', $v);
                $_GET[$varr[0]] = isset($varr[1]) ? urldecode($varr[1]) : '';
            }
        }
        unset($uarr);

        $uarr = explode('-', $u);
        if(count($uarr) < 2) {core::error404();}

        //控制器
        if(isset($uarr[0])) {
            $_GET['control'] = empty($uarr[0]) ? 'index': strtolower($uarr[0]);
            array_shift($uarr);
        }

        //方法
        if(isset($uarr[0])) {
            $_GET['action'] = empty($uarr[0]) ? 'index': strtolower($uarr[0]);
            array_shift($uarr);
        }

        //伪静态下 访问动态首页、内容页URL、分类URL、标签URL 则进入404页面
        $dis_control = array('index', 'show', 'cate', 'tag');
        if( in_array($_GET['control'], $dis_control) && $_GET['action'] == 'index'){
            core::error404();
        }

        //参数
        $num = count($uarr);
        for($i=0; $i<$num; $i+=2){
            isset($uarr[$i+1]) && $_GET[$uarr[$i]] = $uarr[$i+1];
        }

        // hook parseurl_control_other_url_after.php
        return false;
    }

    //分页参数验证
    private function page_check($param){
        if(empty($param)){
            return false;
        } else { preg_match('/^page_([1-9]\d*)$/', $param, $mat);
            if(isset($mat[1])){
                return $mat[1];
            } else { return false;
            }
        }
    }

    //正整数参数验证
    private function integer_check($param){
        // hook parseurl_control_integer_check_before.php
        if(empty($param)){
            return false;
        }elseif( preg_match($this->integer_pattern, $param) ){
            return true;
        } else { return false;
        }
    }

    //模型ID验证（不含单页）
    private function mid_check($mid, $cfg){
        if($mid > 1 && isset($cfg['table_arr'][$mid])){
            return true;
        } else { return false;
        }
    }

    // hook parseurl_control_after.php
}
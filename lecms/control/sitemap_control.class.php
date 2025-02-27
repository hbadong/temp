<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-09-24
 * Time: 9:05
 * Description:站点地图路由
 */
defined('ROOT_PATH') or exit;
// hook sitemap_control_start.php
class sitemap_control extends control{
    public $_cfg = array();	// 全站参数
    public $_var = array();	// 各个模块页参数
    public $_action = array('xml', 'html', 'txt');
    public $_sitemap_setting = array();
    public $webroot = '';

    function __construct(){
        $action = R('action', 'G');
        // hook sitemap_control_construct_before.php

        if(!in_array($action, $this->_action)){
            core::error404();
        }

        $this->_var['topcid'] = -1;

        $this->_cfg = $this->runtime->xget();
        //使用相对URL
        if(isset($this->_cfg['url_path']) && !empty($this->_cfg['url_path'])){
            $this->webroot = $this->_cfg['webroot'];
        }else{
            $this->webroot = '';
        }
        $this->_sitemap_setting = $this->kv->xget('sitemap');
        // hook sitemap_control_construct_after.php
    }

    //XML地图
    public function xml(){
        // hook sitemap_control_xml_before.php
        $today = date('c');
        $sitemapObj = new baidusitemap();

        //生成百度地图头部　－第一条 首页
        $baidu_changefreq_index = isset($this->_sitemap_setting['baidu_changefreq_index']) ? $this->_sitemap_setting['baidu_changefreq_index'] : 'daily';
        $baidu_priority_index = isset($this->_sitemap_setting['baidu_priority_index']) ? $this->_sitemap_setting['baidu_priority_index'] : '1';
        $sitemapObj->baiduxml_item($this->_cfg['weburl'], $today, $baidu_changefreq_index, $baidu_priority_index);

        // hook sitemap_control_xml_home_after.php

        //分类
        $baidu_changefreq_category = isset($this->_sitemap_setting['baidu_changefreq_category']) ? $this->_sitemap_setting['baidu_changefreq_category'] : 'daily';
        $baidu_priority_category = isset($this->_sitemap_setting['baidu_priority_category']) ? $this->_sitemap_setting['baidu_priority_category'] : '0.9';
        $category = $this->category->find_fetch(array('type'=>0), array('cid'=>1));
        foreach ($category as $cate){
            $url = $this->webroot.$this->category->category_url($cate);
            $sitemapObj->baiduxml_item($url, $today, $baidu_changefreq_category, $baidu_priority_category);
            // hook sitemap_control_xml_category_url_foreach_after.php
        }

        // hook sitemap_control_xml_category_after.php

        //内容URL 和 标签URL
        foreach ($this->_cfg['table_arr'] as $mid=>$table) {
            if($mid > 1){
                $content_count = isset($this->_sitemap_setting['content_count_'.$mid]) ? (int)$this->_sitemap_setting['content_count_'.$mid] : 0;
                $changefreq = isset($this->_sitemap_setting['baidu_changefreq_content_'.$mid]) ? $this->_sitemap_setting['baidu_changefreq_content_'.$mid] : 'daily';
                $priority = isset($this->_sitemap_setting['baidu_priority_content_'.$mid]) ? $this->_sitemap_setting['baidu_priority_content_'.$mid] : '0.8';

                $tag_count = isset($this->_sitemap_setting['tag_count_'.$mid]) ? (int)$this->_sitemap_setting['tag_count_'.$mid] : 0;
                $tag_changefreq = isset($this->_sitemap_setting['baidu_changefreq_tag_'.$mid]) ? $this->_sitemap_setting['baidu_changefreq_tag_'.$mid] : 'daily';
                $tag_priority = isset($this->_sitemap_setting['baidu_priority_tag_'.$mid]) ? $this->_sitemap_setting['baidu_priority_tag_'.$mid] : '0.7';

                if ($content_count) {
                    $this->cms_content->table = 'cms_'.$table;
                    $list_arr = $this->cms_content->find_fetch(array(), array('id' => -1), 0, $content_count);
                    foreach ($list_arr as $v){
                        $url = $this->webroot.$this->cms_content->content_url($v, $mid);
                        $updatetime = date('c',$v['lasttime']);
                        $sitemapObj->baiduxml_item($url, $updatetime, $changefreq, $priority);
                        // hook sitemap_control_xml_content_url_foreach_after.php
                    }
                }
                // hook sitemap_control_xml_content_after.php

                if ($tag_count) {
                    $this->cms_content_tag->table = 'cms_'.$table.'_tag';
                    $list_arr = $this->cms_content_tag->find_fetch(array(), array('tagid' => -1), 0, $tag_count);
                    foreach ($list_arr as $v){
                        $url = $this->webroot.$this->cms_content->tag_url($mid, $v);
                        $sitemapObj->baiduxml_item($url, '', $tag_changefreq, $tag_priority);
                        // hook sitemap_control_xml_tag_url_foreach_after.php
                    }
                }
                // hook sitemap_control_xml_tag_after.php
            }
        }

        // hook sitemap_control_xml_after.php

        header('content-type:text/xml');
        echo '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;
        foreach ($sitemapObj->baidu_items as $v){
            echo '<url>'.PHP_EOL.'<loc>'.$v['loc'].'</loc>';
            if(isset($v['lastmod']) && $v['lastmod']){
                echo '<lastmod>'.$v['lastmod'].'</lastmod>';
            }
            if(isset($v['changefreq']) && $v['changefreq']){
                echo '<changefreq>'.$v['changefreq'].'</changefreq>';
            }
            echo '<priority>'.$v['priority'].'</priority>'.PHP_EOL.'</url>'.PHP_EOL;
            // hook sitemap_control_xml_url_foreach_after.php
        }
        // hook sitemap_control_xml_urlset_after.php
        echo '</urlset>';
        // hook sitemap_control_xml_urlset_end.php
        exit();
    }

    //HTML地图
    public function html(){
        // hook sitemap_control_html_before.php
        $_ENV['_theme'] = $this->_cfg['theme'];    //这里不能用引用，并且下面在调用display前还要赋值一次，要不然在assign时会变成默认
        $tpl = 'sitemap.htm';
        if(!view_tpl_exists($tpl)){
            core::error404();
        }

        $Htmlx = '';
        // hook sitemap_control_html_home_after.php

        //分类
        $category = $this->category->find_fetch(array('type'=>0), array('cid'=>1));
        foreach ($category as $cate){
            $url = $this->webroot.$this->category->category_url($cate);
            $Htmlx .= '<li><a href="' . $url . '" title="' . $cate['name'] . '" target="_blank">' . $cate['name'] . '</a></li>';
            // hook sitemap_control_html_category_url_foreach_after.php
        }

        // hook sitemap_control_html_category_after.php

        //内容URL 和 标签URL
        foreach ($this->_cfg['table_arr'] as $mid=>$table) {
            if($mid > 1){
                $content_count = isset($this->_sitemap_setting['content_count_'.$mid]) ? (int)$this->_sitemap_setting['content_count_'.$mid] : 0;
                $tag_count = isset($this->_sitemap_setting['tag_count_'.$mid]) ? (int)$this->_sitemap_setting['tag_count_'.$mid] : 0;

                if ($content_count) {
                    $this->cms_content->table = 'cms_'.$table;
                    $list_arr = $this->cms_content->find_fetch(array(), array('id' => -1), 0, $content_count);
                    foreach ($list_arr as $v){
                        $url = $this->webroot.$this->cms_content->content_url($v, $mid);
                        $Htmlx .= '<li><a href="' . $url . '" title="' . $v['title'] . '" target="_blank">' . $v['title'] . '</a></li>';
                        // hook sitemap_control_html_content_url_foreach_after.php
                    }
                }
                // hook sitemap_control_html_content_after.php

                if ($tag_count) {
                    $this->cms_content_tag->table = 'cms_'.$table.'_tag';
                    $list_arr = $this->cms_content_tag->find_fetch(array(), array('tagid' => -1), 0, $tag_count);
                    foreach ($list_arr as $v){
                        $url = $this->webroot.$this->cms_content->tag_url($mid, $v);
                        $Htmlx .= '<li><a href="' . $url . '" title="' . $v['name'] . '" target="_blank">' . $v['name'] . '</a></li>';
                        // hook sitemap_control_html_tag_url_foreach_after.php
                    }
                }
                // hook sitemap_control_tag_content_after.php
            }
        }

        // hook sitemap_control_html_after.php

        $this->assign('htmlx', $Htmlx);

        $lastTime = date('Y-m-d H:i:s');
        $this->assign('lastTime', $lastTime);

        $this->assign('cfg', $this->_cfg);
        $this->assign('cfg_var', $this->_var);

        $GLOBALS['run'] = &$this;
        $_ENV['_theme'] = &$this->_cfg['theme'];
        $this->display($tpl);
    }

    //TXT地图
    public function txt(){
        // hook sitemap_control_txt_before.php
        $txtx = $this->_cfg['weburl'] . PHP_EOL;
        // hook sitemap_control_txt_home_after.php

        $category = $this->category->find_fetch(array('type'=>0), array('cid'=>1));
        foreach ($category as $cate){
            $url = $this->webroot.$this->category->category_url($cate);
            $txtx .= $url . PHP_EOL;
            // hook sitemap_control_txt_category_url_foreach_after.php
        }
        // hook sitemap_control_txt_category_after.php

        //内容URL 和 标签URL
        foreach ($this->_cfg['table_arr'] as $mid=>$table) {
            if($mid > 1){
                $content_count = isset($this->_sitemap_setting['content_count_'.$mid]) ? (int)$this->_sitemap_setting['content_count_'.$mid] : 0;
                $tag_count = isset($this->_sitemap_setting['tag_count_'.$mid]) ? (int)$this->_sitemap_setting['tag_count_'.$mid] : 0;

                if ($content_count) {
                    $this->cms_content->table = 'cms_'.$table;
                    $list_arr = $this->cms_content->find_fetch(array(), array('id' => -1), 0, $content_count);
                    foreach ($list_arr as $v){
                        $url = $this->webroot.$this->cms_content->content_url($v, $mid);
                        $txtx .= $url . PHP_EOL;
                        // hook sitemap_control_txt_content_url_foreach_after.php
                    }
                }
                // hook sitemap_control_txt_content_after.php

                if ($tag_count) {
                    $this->cms_content_tag->table = 'cms_'.$table.'_tag';
                    $list_arr = $this->cms_content_tag->find_fetch(array(), array('tagid' => -1), 0, $tag_count);
                    foreach ($list_arr as $v){
                        $url = $this->webroot.$this->cms_content->tag_url($mid, $v);
                        $txtx .= $url . PHP_EOL;
                        // hook sitemap_control_txt_tag_url_foreach_after.php
                    }
                }
                // hook sitemap_control_txt_tag_after.php
            }
        }

        // hook sitemap_control_txt_after.php

        header('content-type:text/plain');
        echo $txtx;
        exit();
    }

    // hook sitemap_control_after.php
}

class baidusitemap{
    public $baidu_items;

    function __construct()
    {
        $this->baidu_items = array();   //baidu 元素
    }

    /**
     * @param $loc  网址
     * @param string $lastmod 文件上次修改日期
     * @param string $changefreq 页面可能发生更改的频率 always、hourly、daily、weekly、monthly、yearly、never
     * @param string $priority 网页的优先级。有效值范围从 0.0 到 1.0 (选填项) 。0.0优先级最低、1.0最高。
     * @return array
     */
    function baiduxml_item($loc = '', $lastmod = '', $changefreq = 'weekly', $priority = '1.0')
    {
        $data = array();
        $data['loc'] = $loc;
        $data['lastmod'] = empty($lastmod) ? '' : $lastmod;
        $data['changefreq'] = $changefreq;
        $data['priority'] = $priority;
        $this->baidu_items[] = $data;
        return $data;
    }

    function baiduxml_build($file_name = null, $encoding = 'UTF-8')
    {
        $map = "<?xml version=\"1.0\" encoding=\"$encoding\" ?>\n";
        $map .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:mobile=\"http://www.baidu.com/schemas/sitemap-mobile/1/\">\n";
        foreach ($this->baidu_items as $item) {
            $map .= "\t\t<url>\n\t\t\t<loc>$item[loc]</loc>\n";
            $map .= "\t\t\t<mobile:mobile type=\"pc,mobile\"/>\n";
            $map .= "\t\t\t<lastmod>$item[lastmod]</lastmod>\n";
            $map .= "\t\t\t<changefreq>$item[changefreq]</changefreq>\n";
            $map .= "\t\t\t<priority>$item[priority]</priority>\n";
            $map .= "\t\t</url>\n";
        }
        $map .= "</urlset>\n";
        return FW($file_name, $map);
    }

    function baiduxml_build_sitemapindex($file_name = null, $encoding = 'UTF-8')
    {
        $map = "<?xml version=\"1.0\" encoding=\"$encoding\" ?>\n";
        $map .= "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:mobile=\"http://www.baidu.com/schemas/sitemap-mobile/1/\">\n";
        foreach ($this->baidu_items as $item) {
            $map .= "\t\t<sitemap>\n\t\t\t<loc>$item[loc]</loc>\n";
            $map .= "\t\t\t<lastmod>$item[lastmod]</lastmod>\n";
            $map .= "\t\t</sitemap>\n\n";
        }
        $map .= "</sitemapindex>\n";
        return FW($file_name, $map);
    }
}

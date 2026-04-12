<?php
// +----------------------------------------------------------------------
// | Site:  [ http://www.yzmcms.com]
// +----------------------------------------------------------------------
// | Copyright: 袁志蒙工作室，并保留所有权利
// +----------------------------------------------------------------------
// | Author: YuanZhiMeng <214243830@qq.com>
// +---------------------------------------------------------------------- 
// | Explain: 这不是一个自由软件,您只能在不用于商业目的的前提下对程序代码进行修改和使用，不允许对程序代码以任何形式任何目的的再发布！
// +----------------------------------------------------------------------
 
defined('IN_YZMPHP') or exit('Access Denied'); 
yzm_base::load_controller('common', 'admin', 0);
yzm_base::load_sys_class('page','',0);

class search_log extends common {

    
    /**
     * 列表
     */
    public function init() {
        $of = input('get.of');
        $or = input('get.or');
        $of = in_array($of, array('id','siteid','count','keyword','inputtime','updatetime')) ? $of : 'updatetime';
        $or = in_array($or, array('ASC','DESC')) ? $or : 'DESC';
        $where = array('siteid'=>self::$siteid);
        if(isset($_GET['dosubmit'])){
            if(isset($_GET['count']) && $_GET['count']){
                $where['count>'] = intval($_GET['count']);
            }
            if(isset($_GET['keyword']) && $_GET['keyword']){
                $where['keyword'] = '%'.$_GET['keyword'].'%';
            }
            if(isset($_GET['start']) && isset($_GET['end']) && $_GET['start']) {
                $where['updatetime>='] = strtotime($_GET['start']);
                $where['updatetime<='] = strtotime($_GET['end']);
            }           
        }
        $search_log = D('search_log');
        $total = $search_log->where($where)->total();
        $page = new page($total, 15);
        $data = $search_log->where($where)->order("$of $or")->limit($page->limit())->select();        
        include $this->admin_tpl('search_log_list');
    }


    /**
     * 删除
     */
    public function del() {
        if($_POST && is_array($_POST['id'])){
            $search_log = D('search_log');
            foreach($_POST['id'] as $val){
                $search_log->delete(array('id' => $val));
            }
            return_message(L('operation_success'));
        }
    }


    /**
     * 删除指定次数以下的记录
     */
    public function del_count() {
        $count = input('post.count', 0, 'intval');
        if($count < 0){
            return_message('搜索次数不能小于0', 0);
        }
        $res = D('search_log')->delete(array('siteid'=>self::$siteid, 'count<'=>$count));
        return_json(array('status'=>1, 'message'=>'共删除 '.$res.' 条记录！'));
    }
}
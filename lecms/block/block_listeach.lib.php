<?php
defined('ROOT_PATH') || exit;

/**
 * 遍历内容列表模块
 * @param int cid 频道分类ID 如果不填：自动识别
 * @param int mid 模型ID (当cid为0时，设置mid才能生效，否则程序自动识别)
 * @param string dateformat 时间格式
 * @param int titlenum 标题长度
 * @param int intronum 简介长度
 * @param string orderby 排序方式
 * @param int orderway 降序(-1),升序(1) 默认：-1
 * @param int start 开始位置
 * @param int limit 显示几条
 * @param int showviews 是否读取内容浏览量信息
 * @param int field_format 是否格式化主表自定义字段内容（主要是单选框、多选框、下拉框）
 * @param string nocids 排除的分类cid串 多个用英文逗号隔开
 * @param int flag 读取指定属性的列表
 * @param int showcate 是否显示分类信息（当分类层级超过2级时，内容列表要显示所属分类时使用。一般的2级分类结构，不需要这个参数）
 * @param int life 缓存时间
 * @param int son 获取子级的子级分类（三级分类 才用的上）
 * @param int cate_limit 获取几个子级分类？ 默认为全部
 * @return array
 */
function block_listeach($conf) {
	global $run;

	// hook block_listeach_before.php
	$cid = isset($conf['cid']) ? intval($conf['cid']) : (isset($_GET['cid']) ? intval($_GET['cid']) : 0);
	$mid = isset($conf['mid']) ? max(2, intval($conf['mid'])) : (isset($_GET['mid']) ? max(2, intval($_GET['mid'])) : 2);
	$dateformat = empty($conf['dateformat']) ? 'Y-m-d H:i:s' : $conf['dateformat'];
	$titlenum = _int($conf, 'titlenum');
	$intronum = _int($conf, 'intronum');
	$orderby = isset($conf['orderby']) && in_array($conf['orderby'], array('id', 'dateline', 'lasttime', 'comments')) ? $conf['orderby'] : 'id';
	$orderway = isset($conf['orderway']) && $conf['orderway'] == 1 ? 1 : -1;
    $start = _int($conf, 'start', 0);
	$limit = _int($conf, 'limit', 10);
    $showviews = _int($conf, 'showviews', 0);
    $field_format = _int($conf, 'field_format', 0);
    $nocids = empty($conf['nocids']) ? '' : $conf['nocids'];
    $flag = _int($conf, 'flag', 0);
    $showcate = _int($conf, 'showcate', 0);
    $life = isset($conf['life']) ? (int)$conf['life'] : (isset($run->_cfg['life']) ? (int)$run->_cfg['life'] : 0);
    $son = _int($conf, 'son', 0);
    $cate_limit = _int($conf, 'cate_limit', 0);
    $extra = array('block_name'=>'block_listeach');
    $cache_params = array($cid, $mid, $dateformat, $titlenum, $intronum, $orderby, $orderway, $start, $limit, $showviews, $field_format, $nocids, $flag, $showcate, $son, $cate_limit);
    // hook block_listeach_conf_after.php

    //优先从缓存表读取。加前缀，避免不同的block，相同的cache_params，导致缓存数据错乱
    $cache_key = $life ? md5('listeach'.serialize($cache_params)) : '';
    if($cache_key){
        $cache_data = $run->runtime->get_block_data_cache($cache_key);
        if($cache_data){
            return $cache_data;
        }
    }

	if($cid == 0) {
		$cid_arr = $run->category->get_cids_by_mid($mid);

		$table_arr = &$run->_cfg['table_arr'];
		$table = isset($table_arr[$mid]) ? $table_arr[$mid] : 'article';
	}else{
		$_var = $run->category->get_cache($cid);
		if(isset($_var['son_list'])) {
			$cid_arr = $_var['son_list'];
			$table = $_var['table'];
		}else{
			return array();
		}
		$mid = $_var['mid'];
	}
    //过滤单页模型
    if($table == 'page'){
        return array();
    }

	//排除的cid
	if($nocids){
        $nocids_arr = explode(',', $nocids);
    }else{
        $nocids_arr = array();
    }

	// 初始模型表名
	$run->cms_content->table = 'cms_'.$table;

    if($showviews) {
        $run->cms_content_views->table = 'cms_' . $table . '_views';
    }

    if($showcate){
        $allcategorys = $run->category->get_category_db();
    }else{
        $allcategorys = array();
    }

	// 读取内容列表
	$ret = array();
    $i = 1;
	foreach($cid_arr as $_cid => $cids) {
	    if($nocids_arr && in_array($_cid, $nocids_arr)){
	        continue;
        }
		// 读取分类内容
		$cate_arr = $run->category->get_cache($_cid);
        $run->category->format($cate_arr);

		$ret[$_cid]['cate_name'] = $cate_arr['name'];
		$ret[$_cid]['cate_url'] = $run->category->category_url($cate_arr);
        $ret[$_cid]['cate_pic'] = $cate_arr['pic'];
        $ret[$_cid]['cate_cid'] = $cate_arr['cid'];
        $ret[$_cid]['cate_intro'] = $cate_arr['intro'];

		if(!$cids) continue;

		if($son){
            $ret[$_cid]['son'] = array();
		    foreach ($cids as $cid_tmp){
                $cate_tmp = $run->category->get_cache($cid_tmp);
                if($cate_tmp){
                    $run->category->format($cate_tmp);
                    $ret[$_cid]['son'][$cid_tmp] = $cate_tmp;
                }
            }
        }

		// 读取分类列表
		if(is_array($cids)) {
			$where = array('cid' => array("IN" => $cids)); // 影响数据库性能，不推荐这样建分类
		}else{
			$where = array('cid' => $_cid);
		}

		//读取指定属性的内容
		if($flag){
		    $where['flags'] = array('FIND_IN_SET'=>$flag);
        }

		$ret[$_cid]['list'] = $run->cms_content->list_arr($where, $orderby, $orderway, $start, $limit, $limit, $extra);

        if($showviews && $ret[$_cid]['list']){
            $keys = array();
            foreach($ret[$_cid]['list'] as $lv) {
                $keys[] = $lv['id'];
            }
            $views_list_arr = $run->cms_content_views->mget($keys);
            $views_key = 'cms_'.$table.'_views-id-';
        }else{
            $views_key = '';
            $views_list_arr = array();
        }

        $xuhao = 1;
		foreach($ret[$_cid]['list'] as &$v) {
			$run->cms_content->format($v, $mid, $dateformat, $titlenum, $intronum, $field_format);

            if($showcate && $allcategorys){
                $cate = isset($allcategorys[$v['cid']]) ? $allcategorys[$v['cid']] : array();
                $run->category->getCategoryInfoByList($v, $cate);
            }

			if($showviews && $views_list_arr){
                $v['views'] = isset($views_list_arr[$views_key.$v['id']]['views']) ? (int)$views_list_arr[$views_key.$v['id']]['views'] : 0;
            }
            $v['xuhao'] = $xuhao;
            $xuhao++;
            // hook block_listeach_list_foreach_after.php
		}

		if($cate_limit && $i == $cate_limit){break;}

		$i++;

        // hook block_listeach_foreach_after.php
	}

    // hook block_listeach_set_block_data_cache_before.php
    if($cache_key){
        $run->runtime->set_block_data_cache($cache_key, $ret, $life);
    }

	// hook block_listeach_after.php

	return $ret;
}

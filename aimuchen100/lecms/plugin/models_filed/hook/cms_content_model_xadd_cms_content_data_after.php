<?php
defined('ROOT_PATH') || exit;

//执行添加内容时，处理自定义字段内容
//获取自定义字段的值
$user_defined_field = $this->models_field->user_defined_field($mid);
foreach ($user_defined_field as $field=>$field_set){
    if(isset($post[$field])){
        $val = $post[$field];
        if($field_set['inputtype'] == 'checkbox'){  //传递的是数组
            $val = implode(',', $val);
        }elseif (($field_set['inputtype'] == 'images' || $field_set['inputtype'] == 'attachs') && !empty($val)){  //图集和附件集数组转为json存放

            //火车头采集多图入库，用,隔开的格式过来的数据-----------dadadezhou 20250905
            if($field_set['inputtype'] == 'images' && !is_array($val)){
                $val_tmp = explode(',', $val);
                $newval = array();
                foreach ($val_tmp as $v11){
                    $newval[] = array(
                        'aid' => 0,
                        'big' => $v11,
                        'thumb' => $v11,
                        'title' => $title
                    );
                }
                $val = $newval;
            }

            $more_files_arr = (array)$val;

            //附件集排序
            if($field_set['inputtype'] == 'attachs'){
                _array_multisort($more_files_arr, 'orderby');
            }

            //转为json格式
            if($more_files_arr){
                $val = _json_encode($more_files_arr);
            }else{
                $val = '';
            }

            //同步信息到附件表
            $this->cms_content_attach->table = 'cms_'.$table.'_attach';
            foreach ($more_files_arr as $files){
                if(isset($files['filename']) || isset($files['golds']) || isset($files['credits'])){
                    $aid = isset($files['aid']) ? (int)$files['aid'] : 0;
                    if($aid){
                        $attach_data['aid'] = $aid;
                        isset($files['filename']) && $attach_data['filename'] = $files['filename'];
                        isset($files['golds']) && $attach_data['golds'] = $files['golds'];
                        isset($files['credits']) && $attach_data['credits'] = $files['credits'];

                        $this->cms_content_attach->update($attach_data);
                    }
                }
            }
        }
        $err = $this->models_field->check_field_val($val, $field_set);
        if($err){
            return array('err'=>1 ,'msg'=>$err);
        }

        if($field_set['isbase']){   //主表
            $cms_content[$field] = $val;
        }else{
            $cms_content_data[$field] = $val;
        }
    }
}
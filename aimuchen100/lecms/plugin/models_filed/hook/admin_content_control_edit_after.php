<?php
defined('ROOT_PATH') || exit;

//编辑内容时，处理自定义字段内容

$field_arr = $this->models_field->get_file_info_mid($this->_mid, $data, array('edit_cid_id'=>$edit_cid_id));
$field_js = '';
foreach ($field_arr as $f){
    isset($f['js']) && $field_js .= $f['js'];
}

$this->assign('field_arr', $field_arr);
$this->assign('field_js', $field_js);
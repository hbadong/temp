<?php
defined('ROOT_PATH') || exit;

//删除模型时，删除自定义字段表里面的字段信息
$this->models_field->find_delete(array('mid'=>$mid));
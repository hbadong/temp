<?php
defined('ROOT_PATH') || exit;

//自定义字段插件
function models_field_setting(){
    if(empty($_POST)){
        $this->display();
    }
}

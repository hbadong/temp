<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2023-09-01
 * Time: 9:52
 * Description: 正则替换方法
 */
class php5replace {

    private $data;

    function __construct($data) {
        $this->data = $data;
    }

    // 替换数组变量值
    function php55_replace_data($value) {
        return $this->data[$value[1]];
    }

    // 替换函数值
    function php55_replace_function($value) {
        if (function_exists($value[1])) {
            // 执行函数体
            $param = $value[2] == '$data' ? $this->data : $value[2];
            return call_user_func_array(
                $value[1],
                is_array($param) ? array('data' => $param) : explode(',', $param)
            );
        } else {
            return '函数['.$value[1].']未定义';
        }

        return $value[0];
    }

}
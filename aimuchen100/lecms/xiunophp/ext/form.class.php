<?php
class form{
	// 文本
	public static function get_text($name, &$val, $class='layui-input', $other = '') {
        $class == '' && $class = 'layui-input';
		return '<input name="'.$name.'" type="text" value="'.htmlspecialchars($val).'" class="'.$class.'" '.$other.'  />';
	}

    // 文本 自定义类型
    public static function get_text_type($name, &$val, $type='text', $class='layui-input', $other = '') {
        $class == '' && $class = 'layui-input';
        return '<input name="'.$name.'" type="'.$type.'" value="'.htmlspecialchars($val).'" class="'.$class.'" '.$other.'  />';
    }

	// 多行文本
	public static function get_textarea($name, &$val, $class='layui-textarea', $other = '') {
        $class == '' && $class = 'layui-textarea';
		return '<textarea name="'.$name.'" class="'.$class.'" '.$other.' >'.htmlspecialchars($val).'</textarea>';
	}

	// 密码
	public static function get_password($name, &$val, $class='layui-input', $other = '') {
        $class == '' && $class = 'layui-input';
		return '<input name="'.$name.'" type="password" value="'.$val.'" class="'.$class.'" '.$other.'  />';
	}

	// 数字
	public static function get_number($name, &$val, $class='layui-input', $other = '') {
        $class == '' && $class = 'layui-input';
		return '<input name="'.$name.'" type="number" step="1" min="0" value="'.$val.'" class="'.$class.'" '.$other.' />';
	}

	// 单选
	public static function get_yesno($name, &$val) {
		$s = '<label><input class="mr3" name="'.$name.'" type="radio" value="1"'.($val==1 ? ' checked="checked"' : '').'>&#26159;</label>';
		$s .= '<label><input class="mr3" name="'.$name.'" type="radio" value="0"'.($val==0 ? ' checked="checked"' : '').'>&#21542;</label>';
		return $s;
	}

    //radio
    public static function get_radio($name, &$arr, &$val, $other = ''){
        $s = '';
        foreach ($arr as $k=>$v){
            $s .= '<label><input name="'.$name.'" type="radio" value="'.$k.'"'.$other.($val==$k ? ' checked="checked"' : '').'/>'.$v.'</label>';
        }
        return $s;
    }
	
	//layui 单选
    public static function get_radio_layui($name, &$arr, &$val, $other = ''){
	    $s = '';
	    foreach ($arr as $k=>$v){
            $s .= '<input title="'.$v.'" name="'.$name.'" type="radio" value="'.$k.'"'.$other.($val==$k ? ' checked="checked"' : '').'/>';
        }
	    return $s;
    }

	/**
	 * 循环控件
	 * @param string $type 类型
	 * @param string $name 表单名
	 * @param string $arr 分类数组
	 * @param string $val 默认选中值
	 * @param string $split 分隔字符串
	 */
	public static function loop($type, $name, $arr, &$val, $split = '<br>', $other = '') {
		$s = '';
		switch ($type) {
			case 'radio':
				foreach ($arr as $v => $n){
					$s .= '<label><input '.$other.' class="mr3" name="'.$name.'" type="radio" value="'.$v.'"'.($v==$val ? ' checked="checked"' : '').'>'.$n.'</label>'.$split;
				}
				break;
			case 'checkbox':
				foreach ($arr as $v => $n){
					$s .= '<label><input '.$other.' class="mr3" name="'.$name.'[]" type="checkbox" value="'.$v.'"'.(in_array($v, explode(',', $val)) ? ' checked="checked"' : '').'>'.$n.'</label>'.$split;
				}
				break;
			case 'select':
				$s .= '<select '.$other.' name="'.$name.'" class="se1">';
				foreach ($arr as $v => $n){
					$s .= '<option value="'.$v.'"'.($v==$val ? ' selected="selected"' : '').'>'.$n.'</option>';
				}
				$s .= '</select>';
				break;
			case 'multiple':
				$s .= '<select '.$other.' name="'.$name.'[]" multiple="multiple" class="se2">';
				foreach ($arr as $v => $n){
					$s .= '<option value="'.$v.'"'.(in_array($v, explode(',', $val)) ? ' selected="selected"' : '').'>'.$n.'</option>';
				}
				$s .= '</select>';
		}
		return $s;
	}

    /**
     * layui循环控件
     * @param string $type 类型
     * @param string $name 表单名
     * @param string $arr 分类数组
     * @param string $val 默认选中值
     * @param string $split 分隔字符串
     */
    public static function layui_loop($type, $name, $arr, &$val, $split = '', $other = '') {
        $s = '';
        switch ($type) {
            case 'radio':
                foreach ($arr as $v => $n){
                    $s .= '<input '.$other.' name="'.$name.'" type="radio" title="'.$n.'" value="'.$v.'"'.($v==$val ? ' checked="checked"' : '').'>'.$split;
                }
                break;
            case 'checkbox':
                foreach ($arr as $v => $n){
                    $s .= '<input '.$other.' name="'.$name.'[]" type="checkbox" lay-skin="primary" title="'.$n.'" value="'.$v.'"'.(in_array($v, explode(',', $val)) ? ' checked="checked"' : '').'>'.$split;
                }
                break;
            case 'select':
                $s .= '<select '.$other.' name="'.$name.'">';
                foreach ($arr as $v => $n){
                    $s .= '<option value="'.$v.'"'.($v==$val ? ' selected="selected"' : '').'>'.$n.'</option>';
                }
                $s .= '</select>';
                break;
            case 'multiple':
                $s .= '<select '.$other.' name="'.$name.'[]" multiple="multiple">';
                foreach ($arr as $v => $n){
                    $s .= '<option value="'.$v.'"'.(in_array($v, explode(',', $val)) ? ' selected="selected"' : '').'>'.$n.'</option>';
                }
                $s .= '</select>';
        }
        return $s;
    }

    //layui 开关控件， 提交该控件时 选中返回的是on，不选中则不返回
    public static function layui_switch($name, $text = 'ON|OFF', $val = 0, $other = ''){
        $checked = '';
        if($val){
            $checked = 'checked="checked"';
        }
        $s = '<input type="checkbox" name="'.$name.'" lay-skin="switch" lay-text="'.$text.'" '.$checked.$other.'>';
        return $s;
    }
}

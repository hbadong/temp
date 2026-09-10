<?php
defined('ROOT_PATH') or exit;

class models_field_control extends admin_control {

	// 模型字段管理
	public function index() {
	    $mid = max(2, (int)R('mid','G'));
	    $this->assign('mid',$mid);

        // hook admin_models_field_control_index_after.php
        $this->display();
	}

    //获取数据
    public function get_list(){
        // hook admin_models_field_control_get_list_before.php

        //分页
        $page = isset( $_REQUEST['page'] ) ? intval($_REQUEST['page']) : 1;
        $pagenum = isset( $_REQUEST['limit'] ) ? intval($_REQUEST['limit']) : 15;

        $mid = max(2, (int)R('mid','G'));
        $where['mid'] = $mid;
        $total = $this->models_field->find_count($where);

        //页数
        $maxpage = max(1, ceil($total/$pagenum));
        $page = min($maxpage, max(1, $page));

        $inputtype_arr = $this->models_field->_inputype;
        $data_arr = array();
        $cms_arr = $this->models_field->list_arr($where, 'id', 1, ($page-1)*$pagenum, $pagenum, $total);
        foreach ($cms_arr as $v){
            $v['inputtypename'] = isset($inputtype_arr[$v['inputtype']]) ? $inputtype_arr[$v['inputtype']]['name'] : '未知';
            $data_arr[] = $v;
        }
        unset($cms_arr);

        //组合数据 输出到页面
        $arr = array(
            'code' => 0,
            'msg' => '',
            'count' => $total,
            'data' => $data_arr,
        );
        exit( json_encode($arr) );
    }

    //编辑表格字段
    public function set(){
        if( !empty($_POST) ){
            $field = trim( R('field','P') );
            $id = intval( R('id','P') );
            $value = trim( R('value','P') );

            $data = array(
                'id' => $id,
                $field => $value,
            );
            if(!$this->models_field->update($data)) {
                E(1, '更新失败');
            }
            E(0, '更新'.$field.'成功');
        }
    }

    public function add(){
        if(empty($_POST)) {
            //模型
            $mid = max(2, (int)R('mid','G'));
            $models = $this->models->get($mid);
            empty($models) && $this->message(1, '模型不存在！');
            $this->assign('models', $models);

            //默认值
            $data = array(
                'mid'=>$mid,
                'isbase'=>0,
                'orderby'=>0,
                'required'=>0,
                'index'=>0,
                'tips'=>''
            );
            $this->assign('data', $data);
            //字段类型
            $def = 'text';
            $inputtype_temp = array();
            $inputtype_arr = $this->models_field->_inputype;
            foreach ($inputtype_arr as $k=>$v){
                $inputtype_temp[$k] = $v['name'];
            }
            $inputtype = form::layui_loop('select','inputtype',$inputtype_temp,$def);
            $this->assign('inputtype', $inputtype);

            $this->display('models_field_set.htm');
        }else{
            $mid = max(2, (int)R('mid','P'));
            $models = $this->models->get($mid);
            empty($models) && E(1, '模型不存在！');

            $field = trim(R('field', 'P'));
            $name = trim(R('name', 'P'));
            $inputtype = trim(R('inputtype', 'P'));
            $setting = trim(R('setting', 'P'));

            empty($field) && E(1, '数据表字段不能为空！');
            empty($name) && E(1, '显示名称不能为空！');
            empty($inputtype) && E(1, '请选择类型！');

            $where = array(
                'mid'=>$mid,'field'=>$field
            );
            if( !preg_match('/^[a-z0-9]+$/', $field) ) {
                E(1, '数据表字段只能是小写英文字母和数字！');
            }elseif ( in_array($field, $this->models_field->_systemfield) ){
                E(1, $field.'为系统内置字段，不能使用哦！');
            }elseif( $this->models_field->find_fetch_key($where) ){
                E(1, '数据表字段已经存在，不能重复！');
            }

            // hook admin_models_field_control_add_post_data_before.php

            $setting_field_arr = array('radio','checkbox','select');
            if( in_array($inputtype, $setting_field_arr) ){
                if( empty($setting) ){
                    E(1, '单选框、多选框、下拉框，设置 选项不能为空哦！');
                }
                $setting_arr = explode('#',$setting);
                foreach ($setting_arr as $sv){
                    $sv_arr = explode('=>', $sv);
                    if(count($sv_arr) != 2){
                        E(1, '设置值 格式不正确！');
                    }
                }
            }

            $data = array(
                'mid'=>$mid,
                'field'=>$field,
                'name'=>$name,
                'inputtype'=>$inputtype,
                'tips'=>trim(R('tips', 'P')),
                'setting'=>$setting,
                'isbase'=>intval(R('isbase', 'P')),
                'orderby'=>intval(R('orderby', 'P')),
                'required'=>intval(R('required', 'P')),
            );
            $field_index = intval(R('index', 'P'));

            //唯一索引，改为必填项
            if($field_index == 2){
                $data['required'] = 1;
            }

            // hook admin_models_field_control_add_post_data_after.php

            if($id = $this->models_field->create($data)) {
                $data['index'] = $field_index;
                $data['length'] = intval(R('length', 'P'));
                // hook admin_models_field_control_add_post_success.php
                $sql = $this->sql_field_add($models, $data);
                $res = $this->db->query($sql);
                if(!$res){
                    $this->models_field->delete($id);
                    E(1, $sql.' 执行失败！');
                }

                //更改排序值
                $this->models_field->update(
                    array('id'=>$id,'orderby'=>$id*10)
                );

                E(0, '添加成功！');
            }else{
                E(1, '添加失败！');
            }
        }
    }

    //数据表增加字段 sql
    private function sql_field_add($models = array(),$data = array()){
        if($data['isbase']){
            $table = $_ENV['_config']['db']['master']['tablepre'].'cms_'.$models['tablename'];    //主表全名
        }else{
            $table = $_ENV['_config']['db']['master']['tablepre'].'cms_'.$models['tablename'].'_data';    //附表全名
        }

        $inputtype = $data['inputtype'];
        $field = $data['field'];
        $length_post = (int)$data['length'];
        
	    if($inputtype == 'images' || $inputtype == 'editor' || $inputtype == 'attachs'){
            $sql = "ALTER TABLE {$table} ADD COLUMN {$field} mediumtext NOT NULL";
            return $sql;
        }else{
            $inputtype_arr = $this->models_field->_inputype;

            if( $inputtype_arr[$inputtype] ){
                if($inputtype_arr[$inputtype]['length']){
                    $length = $inputtype_arr[$inputtype]['length'];
                }else{
                    switch ($inputtype_arr[$inputtype]['field']){
                        case 'tinyint':
                            $length = 1;
                            break;
                        case 'int':
                            $length = 10;
                            break;
                        default:
                            $length = 255;
                    }
                }
                if($length_post){
                    $length = $length_post;
                }

                $def = $inputtype_arr[$inputtype]['def'];
                if($def === ''){
                    $def = "''";
                }

                $sql = "ALTER TABLE {$table} ADD COLUMN {$field} {$inputtype_arr[$inputtype]['field']} (".$length.") NOT NULL DEFAULT {$def} COMMENT '".$data['name']."'";
            }else{
                if(!$length_post){
                    $length = 255;
                }else{
                    $length = $length_post;
                }
                $sql = "ALTER TABLE {$table} ADD COLUMN {$field} varchar({$length}) NOT NULL DEFAULT '' COMMENT '".$data['name']."'";
            }

            if(isset($data['index']) && $data['index']){
                switch ($data['index']){
                    case 1:
                        $sql .= ', ADD INDEX `'.$field.'` (`'.$field.'`)';
                        break;
                    case 2:
                        $sql .= ', ADD UNIQUE `'.$field.'` (`'.$field.'`)';
                        break;
                }
            }

            return $sql;
        }
    }

    //删除字段
    public function del() {
        $id = (int) R('id', 'P');
        empty($id) && E(1, 'ID不能为空！');

        $err = $this->models_field->xdelete($id);
        if(!$err) {
            E(0, '删除成功！');
        }else{
            E(1, $err);
        }
    }

    //批量删除字段
    public function batch_del(){
        $id_arr = R('id_arr', 'P');
        if(!empty($id_arr) && is_array($id_arr)) {
            $err_num = 0;
            foreach($id_arr as $v) {
                $err = $this->models_field->xdelete($v);
                if($err) $err_num++;
            }

            if($err_num) {
                E(1, $err_num.' 条内容删除失败！');
            }else{
                E(0, '删除成功！');
            }
        }else{
            E(1, '参数不能为空！');
        }
    }
    // hook admin_models_field_control_after.php
}

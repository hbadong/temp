<?php
defined('ROOT_PATH') or exit;

class models_field extends model {
    //自定义字段类型
    public $_inputype = array(
        'text'=>array('name'=>'单行文本','field'=>'varchar','length'=>255,'def'=>''),
        'textarea'=>array('name'=>'多行文本','field'=>'varchar','length'=>255,'def'=>''),
        'number'=>array('name'=>'数字框','field'=>'int','length'=>10,'def'=>0),
        'radio'=>array('name'=>'单选框','field'=>'varchar','length'=>255,'def'=>''),
        'checkbox'=>array('name'=>'多选框','field'=>'varchar','length'=>255,'def'=>''),
        'select'=>array('name'=>'下拉框','field'=>'varchar','length'=>255,'def'=>''),
        'editor'=>array('name'=>'编辑器','field'=>'mediumtext','length'=>'','def'=>''),
        'pic'=>array('name'=>'图片上传','field'=>'varchar','length'=>255,'def'=>''),
        'files'=>array('name'=>'附件上传','field'=>'varchar','length'=>255,'def'=>''),
        'images'=>array('name'=>'多图上传','field'=>'mediumtext','length'=>'','def'=>''),
        'attachs'=>array('name'=>'多附件上传','field'=>'mediumtext','length'=>'','def'=>''),
    );

    //系统字段
    public $_systemfield = array(
        'id','cid','title','alias','tags','intro','pic','uid','author','source','dateline','lasttime','ip','imagenum','filenum','iscomment','comments','flags','seo_title','seo_keywords','seo_description','jumpurl','show_tpl','content'
    );

    function __construct() {
        $this->table = 'models_field';	// 表名
        $this->pri = array('id');	// 主键
        $this->maxid = 'id';		// 自增字段
    }

    // 暂时用些方法解决获取 cfg 值
    function __get($var) {
        if($var == 'cfg') {
            return $this->cfg = $this->runtime->xget();
        }else{
            return parent::__get($var);
        }
    }

    // 获取内容列表
    public function list_arr($where, $orderby, $orderway, $start, $limit, $total) {
        // 优化大数据量翻页
        if($start > 1000 && $total > 2000 && $start > $total/2) {
            $orderway = -$orderway;
            $newstart = $total-$start-$limit;
            if($newstart < 0) {
                $limit += $newstart;
                $newstart = 0;
            }
            $list_arr = $this->find_fetch($where, array($orderby => $orderway), $newstart, $limit);
            return array_reverse($list_arr, TRUE);
        }else{
            return $this->find_fetch($where, array($orderby => $orderway), $start, $limit);
        }
    }

    //删除字段
    public function xdelete($id){
        $data = $this->get($id);
        if(empty($data)){
            return '字段不存在！';
        }
        $models = $this->models->get($data['mid']);
        if(empty($models)){
            $this->delete($id);
            return '';
        }

        if($data['isbase']){
            $table = $_ENV['_config']['db']['master']['tablepre'].'cms_'.$models['tablename'];    //主表全名
        }else{
            $table = $_ENV['_config']['db']['master']['tablepre'].'cms_'.$models['tablename'].'_data';    //附表全名
        }

        $sql = "ALTER TABLE {$table} DROP {$data['field']} ";
        $res = $this->db->query($sql);
        if($res){
            $ret = $this->delete($id);
            return $ret ? '' : '删除失败！';
        }else{
            return '删除表字段失败！';
        }
    }

    //获取自定义字段
    public function user_defined_field($mid = 2){
        $where['mid'] = $mid;
        $total = $this->find_count($where);
        $cms_arr = $this->find_fetch($where, array('orderby' => 1), 0, $total);
        $ret = array();
        foreach ($cms_arr as $v){
            $ret[$v['field']] = $v;
        }
        return $ret;
    }

    //验证自定义字段数据提交时是否合法
    public function check_field_val($val = '',$filed_set = array()){
        if($filed_set['required'] && $val === ''){
            return $filed_set['name'].' 不能为空哦！';
        }
        return '';
    }

    //自定义字段， 系统内置字段是固定的，不在这里哦！
    public function get_file_info_mid($mid = 2, $data = array(), $extend = array()){
        $where['mid'] = $mid;

        $ret = array();

        $cms_arr = $this->find_fetch($where, array('orderby' => 1));
        foreach ($cms_arr as $v){
            $name = $v['name'];
            $inputtype = $v['inputtype'];
            $field = $v['field'];
            $required = (int)$v['required'];
            $endstr = '';

            $val = isset($data[$field]) ? $data[$field] : ($inputtype == 'number' ? 0: '');

            $ret[$field]['name'] = $name;   //显示名

            if($required){    //是否必填的 *
                $ret[$field]['required'] = 1;
                $endstr .= ' lay-verify="required"';
            }else{
                $ret[$field]['required'] = 0;
            }
            $arr = array();

            switch ($inputtype){
                case 'text':
                    if(isset($v['tips']) && $v['tips']){
                        $endstr .= ' placeholder="'.$v['tips'].'"';
                    }
                    $ret[$field]['html'] = form::get_text($field, $val, 'layui-input', $endstr);
                    break;
                case 'textarea':
                    $ret[$field]['html'] = form::get_textarea($field, $val, 'layui-textarea', $endstr);
                    if(isset($v['tips']) && $v['tips']){
                        $ret[$field]['html'] .= '<div class="layui-form-mid layui-word-aux">'.$v['tips'].'</div>';
                    }
                    break;
                case 'number':
                    $val = isset($data[$field]) ? $data[$field] : 0;
                    $ret[$field]['html'] = form::get_number($field, $val, 'layui-input', $endstr);
                    break;
                case 'radio':
                    $setting = $v['setting'];
                    $setting_arr = explode('#', $setting);
                    foreach ($setting_arr as $v){
                        $vv = explode('=>',$v);
                        $arr[$vv[0]] = $vv[1];
                    }
                    $ret[$field]['html'] = form::get_radio_layui($field, $arr, $val);
                    break;
                case 'checkbox':
                    $setting = $v['setting'];
                    $setting_arr = explode('#', $setting);
                    foreach ($setting_arr as $v){
                        $vv = explode('=>',$v);
                        $arr[$vv[0]] = $vv[1];
                    }
                    $ret[$field]['html'] = form::layui_loop('checkbox',$field, $arr, $val, '', $endstr);
                    break;
                case 'select':
                    $setting = $v['setting'];
                    $setting_arr = explode('#', $setting);
                    foreach ($setting_arr as $v){
                        $vv = explode('=>',$v);
                        $arr[$vv[0]] = $vv[1];
                    }
                    $ret[$field]['html'] = form::layui_loop('select', $field, $arr, $val, '', $endstr);
                    break;
                case 'pic':   //layui上传控件
                    $endstr .= ' id="'.$field.'"';
                    $btn_id = $field.'_btn';
                    $edit_cid_id = $extend['edit_cid_id'];

                    $ret[$field]['html'] = '<div class="layui-inline">'.form::get_text($field, $val, 'layui-input', $endstr).'</div><div class="layui-inline"><button type="button" class="layui-btn layui-btn-sm" id="'.$btn_id.'"><i class="layui-icon">&#xe67c;</i>上传图片</button></div>';
                    $ret[$field]['js'] = "layui.use(['layer', 'upload'], function () {
                    var layer = layui.layer, upload = layui.upload;
                    upload.render({
                    elem: '#".$btn_id."',
                    url: 'index.php?attach-upload_image-type-img".$edit_cid_id."',
                    field:'upfile',
                    accept: 'images',
                    acceptMime:'image/*',
                    done: function(res){
                    if(res.err == 1){
                        layer.msg(res.msg, {icon: 5});
                    }else{
                        $('#".$field."').val(res.data.src);layer.msg('上传成功！', {icon: 1});
                    }},
                    error: function(){
                        layer.msg('请求异常',{icon: 5});
                    }});});";
                    break;
                case 'files':   //layui上传控件
                    $endstr .= ' id="'.$field.'"';
                    $btn_id = $field.'_btn';
                    $edit_cid_id = $extend['edit_cid_id'];

                    $ret[$field]['html'] = '<div class="layui-inline">'.form::get_text($field, $val, 'layui-input', $endstr).'</div><div class="layui-inline"><button type="button" class="layui-btn layui-btn-sm" id="'.$btn_id.'"><i class="layui-icon">&#xe681;</i>上传附件</button></div>';
                    $ret[$field]['js'] = "layui.use(['layer', 'upload'], function () {
                    var layer = layui.layer, upload = layui.upload;
                    upload.render({
                    elem: '#".$btn_id."',
                    url: 'index.php?attach-upload_files".$edit_cid_id."',
                    field:'upfile',
                    accept: 'file',
                    done: function(res){
                    if(res.err == 1){
                        layer.msg(res.msg, {icon: 5});
                    }else{
                        $('#".$field."').val(res.data.src);layer.msg('上传成功！', {icon: 1});
                    }},
                    error: function(){
                        layer.msg('请求异常',{icon: 5});
                    }});});";
                    break;
                case 'editor':  //富文本编辑器
                    $endstr .= ' id="'.$field.'"  lay-verify="'.$field.'"';
                    $edit_cid_id = $extend['edit_cid_id'];

                    $ret[$field]['html'] = form::get_textarea($field, $val, 'layui-textarea', $endstr);

                    // $uploadurl = 'index.php?attach-ice_upload-ajax-1';
                    // $js = 'ice.editor("'.$field.'",function(e){
                    //       e.height="300px";
                    //       this.uploadUrl="'.$uploadurl.'";
                    //       e.create();
                    //     });';
                    // $ret[$field]['js'] = $js;

                    $ret[$field]['js'] = "layui.use(['layer', 'upload', 'layedit', 'form'], function () {
                    var layer = layui.layer, upload = layui.upload, layedit = layui.layedit, form = layui.form;
                    layedit.set({uploadImage: {url:'index.php?attach-upload_image-type-img".$edit_cid_id."', type: 'post'}});
                    var layeditindex = layedit.build('".$field."');
                    form.verify({".$field.":function () {layedit.sync(layeditindex)}});
                    });";

                    break;
                case 'images':  //上传图集
                    $edit_cid_id = $extend['edit_cid_id'];
                    $ret[$field]['html'] = '<button type="button" class="layui-btn" id="'.$field.'" data-des="'.$field.'"><i class="layui-icon">&#xe67c;</i>上传图集</button>
                                            <div id="'.$field.'_box" class="layui-row layui-col-space5 images upload_images" style="margin-top:5px;border:2px dashed #E5E5E5;display: none;"><dl style="display: none;"></dl>';

                    $val_html = '';
                    if($val){
                        $arr = (array)_json_decode($val);
                        foreach ($arr as $v){
                            if( substr($v['thumb'], 0, 2) != '//' && substr($v['thumb'], 0, 4) != 'http' ){
                                $thumb = '../'.$v['thumb'];
                            }else{
                                $thumb = $v['thumb'];
                            }

                            $val_html .= '<dl class="layui-col-md1" style="padding-top: 0;">
                                            <dt><img style="width: 100%;" src="'.$thumb.'" data-url="'.$v['thumb'].'"></dt>
                                            <dd aid="'.$v['aid'].'" class="delimg layui-bg-red">删除</dd>
                                            <dt>
                                            <input type="hidden" name="'.$field.'['.$v['aid'].'][aid]" value="'.$v['aid'].'"/>
                                            <input type="hidden" name="'.$field.'['.$v['aid'].'][big]" value="'.$v['big'].'"/>
                                            <input type="hidden" name="'.$field.'['.$v['aid'].'][thumb]" value="'.$v['thumb'].'"/>
                                            <input type="text" class="layui-input" name="'.$field.'['.$v['aid'].'][title]" value="'.$v['title'].'"/>
                                            </dt></dl>';
                        }
                    }

                    $ret[$field]['html'] .= $val_html.'</div>';

                    $js = 'var img_files="",img_html="";var img_id="'.$field.'";';
                    $js .= 'var dl_length = $("#"+img_id+"_box").children("dl").length;if(dl_length > 1){$("#"+img_id+"_box").show();}';
                    $js .= 'layui.use(["layer", "upload"], function () {
                        var layer = layui.layer, upload = layui.upload;
                        upload.render({
                            elem: "#'.$field.'",
                            url: "index.php?attach-upload_image-type-pic'.$edit_cid_id.'",
                            field:"upfile",
                            accept:"images",
                            acceptMime:"image/*",
                            multiple: true,
                            before: function(obj){layer.load();},
                            done: function(res){
                                    if(res.err == 1){
                                        layer.msg(res.msg, {icon: 5});
                                    }else{
                                        var aid = res.data.aid;
                                        if(img_files){
                                            img_files += ","+res.data.path;
                                        }else{
                                            img_files = res.data.path;
                                        }';

                    $js1 = <<<EOD
                            img_html += '<dl class="layui-col-md1" style="padding-top: 0;"><dt><img style="width: 100%;" src="../'+res.data.src+'" data-url="'+res.data.src+'"></dt><dd aid="'+res.data.aid+'" class="delimg layui-bg-red">删除</dd><dt><input type="hidden" name="'+img_id+'['+aid+'][aid]" value="'+aid+'"/><input type="hidden" name="'+img_id+'['+aid+'][big]" value="'+res.data.path+'"/><input type="hidden" name="'+img_id+'['+aid+'][thumb]" value="'+res.data.src+'"/><input type="text" class="layui-input" name="'+img_id+'['+aid+'][title]" value="'+res.data.title+'"/></dt></dl>';
EOD;

                    $js .= $js1;
                    $js .= '}},
                            allDone: function(obj){
                                    var item = this.item;
                                    var des = $(item).data("des");
                                    layer.closeAll("loading");
                                    if(img_files != ""){
                                        $("#"+des+"_box").show().append(img_html);
                                        layer.msg("成功上传"+obj.successful+"张图片");
                                        img_files = "",img_html="";
                                    }else{
                                        layer.msg("全部上传失败");
                                    }
                            },error: function(){
                                   layer.msg("请求异常",{icon: 5});
                            }
                        });
                    });';

                    $js .= '$("#"+img_id+"_box").on("click",".delimg",function(){
                        var aid=$(this).attr("aid");
                        var removeobj = $(this).parents("dl");
                        adminAjax.postd("index.php?attach-del_attach-ajax-1'.$edit_cid_id.'", {"aid":aid},function () {
                            removeobj.remove();
                        });
		            });';

                    $js .= '$("#"+img_id+"_box").dragsort({
                            dragSelector: "dl",
                            dragSelectorExclude: "input,textarea,dd",
                            dragBetween: false,
                            placeHolderTemplate: "<dl><dt></dt></dl>"
                        });';

                    $ret[$field]['js'] = $js;
                    break;

                case 'attachs':
                    $endstr .= ' id="'.$field.'"';
                    $btn_id = $field.'_btn';
                    $attach_table = $field.'_tbody';
                    $edit_cid_id = $extend['edit_cid_id'];
                    $uploadobj = $field.'_uploadListIns';

                    $val_html_body = '';
                    if($val){
                        $table_style = '';
                        $arr = (array)_json_decode($val);
                        foreach ($arr as $k=>$v){
                            $aid = isset($v['aid']) ? (int)$v['aid'] : 0;
                            $orderby = isset($v['orderby']) ? (int)$v['orderby'] : (int)$k;
                            $val_html_body .= '<tr>
                                            <td><input type="hidden" name="'.$field.'['.$aid.'][aid]" value="'.$aid.'"><input type="text" readonly class="layui-input" name="'.$field.'['.$aid.'][filepath]" value="'.$v['filepath'].'"/></td>
                                            <td><input type="text" required class="layui-input" name="'.$field.'['.$aid.'][filename]" value="'.$v['filename'].'"/></td>
                                            <td><input type="number" class="layui-input" name="'.$field.'['.$aid.'][golds]" value="'.$v['golds'].'"/></td>
                                            <td><input type="number" class="layui-input" name="'.$field.'['.$aid.'][orderby]" value="'.$orderby.'"/></td>
                                            <td><button type="button" class="layui-btn layui-btn-xs layui-btn-danger delattach" aid="'.$aid.'">删除</button></td>
                                            </tr>';
                        }
                    }else{
                        $table_style = 'style="display:none;"';
                    }

                    $val_html_start = '<div class="layui-form-item"><table '.$table_style.' class="layui-table '.$field.'_table">'.
                        '<colgroup><col><col><col width="100"><col width="80"></colgroup><thead><th>存储路径</th><th>文件名</th><th>金币</th><th>排序值</th><th>操作</th></thead><tbody id="'.$attach_table.'">';

                    $val_html_end = '</tbody></table></div>';

                    $ret[$field]['html'] = '<div class="layui-inline"><button type="button" class="layui-btn layui-btn-sm" id="'.$btn_id.'"><i class="layui-icon">&#xe681;</i>上传附件集</button></div>'.
                        $val_html_start.$val_html_body.$val_html_end;

                    $ret[$field]['js'] = 'var '.$uploadobj.'=layui.use(["layer", "upload","element"], function () {
                        var layer = layui.layer, upload = layui.upload, element = layui.element;
                        upload.render({
                            elem: "#'.$btn_id.'",
                            multiple: true,
                            url: "index.php?attach-upload_files'.$edit_cid_id.'",
                            field:"upfile",
                            accept: "file",
                            exts: "'.str_replace(',','|',$this->cfg['up_file_ext']).'",
                            size: "'.$this->cfg['up_file_max_size'].'",
                            before: function(obj){layer.load();},
                            done: function(res, index, upload){
                                $(".'.$field.'_table").show();
                                if(res.err == 1){
                                    layer.closeAll("loading");
                                    layer.msg(res.msg, {icon: 5});
                                }else{
                                    var aid = res.data.aid;
                                    var aid_name = "'.$field.'["+aid+"][aid]";
                                    var filepath_name = "'.$field.'["+aid+"][filepath]";
                                    var filename_name = "'.$field.'["+aid+"][filename]";
                                    var golds_name = "'.$field.'["+aid+"][golds]";
                                    var order_by_name = "'.$field.'["+aid+"][orderby]";
                                    
                                    var tr = "<tr><td><input type=\"hidden\" name=\""+aid_name+"\" value=\""+aid+"\"><input type=\"text\" readonly class=\"layui-input\" name=\""+filepath_name+"\" value=\""+res.data.path+"\"></td><td><input type=\"text\" required class=\"layui-input\" name=\""+filename_name+"\" value=\""+res.data.title+"\"></td><td><input type=\"number\" class=\"layui-input\" name=\""+golds_name+"\" value=\"0\"></td><td><input type=\"number\" class=\"layui-input\" name=\""+order_by_name+"\" value=\"0\"></td><td><button type=\"button\" class=\"layui-btn layui-btn-xs layui-btn-danger delattach\" aid=\""+aid+"\">删除</button></td></tr>";
                                    
                                    $("#'.$attach_table.'").append(tr);
                                    
                                }
                            },
                            allDone: function(obj){
                                layer.closeAll("loading");
                                var msg = "总文件数："+obj.total+"，上传成功："+obj.successful+"，上传失败："+obj.aborted;
                                layer.msg(msg, {icon: 1});
                            },
                            error: function(){
                                layer.msg("请求异常",{icon: 5});
                            }
                        });
                    });
                    $("#'.$attach_table.'").on("click",".delattach",function(){
                        var aid=$(this).attr("aid");
                        var removeobj = $(this).parents("tr");
                        
                        var del_index = layer.confirm("确定删除？", {btn: ["确定", "取消"]}, function(){
                            layer.close(del_index);
                            if(aid){
                                adminAjax.postd("index.php?attach-del_attach-ajax-1'.$edit_cid_id.'", {"aid":aid},function () {
                                    removeobj.remove();
                                });
                            }else{
                                removeobj.remove();
                            }
                            return false;
                        }, function(){});
		            });
                    ';

                    break;
            }

        }

        return $ret;
    }

    //格式化显示自定义字段的值（主要是单选框、多选框、下拉框、图集）
    public function field_val_format($models_field, &$post, $isbase = 1){
        foreach ($models_field as $field=>$set){
            if($isbase == 1 && $set['isbase'] != 1){
                continue;
            }
            $post[$field.'_format'] = '';
            switch ($set['inputtype']){
                case 'radio':
                    $setting_arr = explode('#', $set['setting']);
                    foreach ($setting_arr as $v1){
                        $vv = explode('=>',$v1);
                        if(isset($post[$field]) && $post[$field] == $vv[0]){
                            $post[$field.'_format'] = $vv[1];
                            break;
                        }
                    }
                    break;
                case 'select':
                    $setting_arr = explode('#', $set['setting']);
                    foreach ($setting_arr as $v1){
                        $vv = explode('=>',$v1);
                        if(isset($post[$field]) && $post[$field] == $vv[0]){
                            $post[$field.'_format'] = $vv[1];
                            break;
                        }
                    }
                    break;
                case 'checkbox':
                    $field_val_arr = isset($post[$field]) ? explode(',', $post[$field]) : array();
                    $setting_arr = explode('#', $set['setting']);
                    $field_val_format = array();
                    foreach ($setting_arr as $v1){
                        $vv = explode('=>',$v1);
                        if( $field_val_arr && in_array($vv[0], $field_val_arr) ){
                            $field_val_format[] = $vv[1];
                        }

                    }
                    $post[$field.'_format'] = implode(',', $field_val_format);
                    break;
                case 'images':
                    $post[$field.'_format'] = isset($post[$field]) ? (array)_json_decode($post[$field]) : array();
                    foreach ($post[$field.'_format'] as &$v){
                        if( isset($v['big']) && substr($v['big'], 0, 2) != '//' && substr($v['big'], 0, 4) != 'http' ){ //不是外链图片
                            $v['big'] = $this->cfg['weburl'].$v['big'];
                        }
                        if( isset($v['thumb']) && substr($v['thumb'], 0, 2) != '//' && substr($v['thumb'], 0, 4) != 'http' ){ //不是外链图片
                            $v['thumb'] = $this->cfg['weburl'].$v['thumb'];
                        }
                    }
                    break;
                case 'files':
                    $mid = isset($set['mid']) ? (int)$set['mid'] : 2;
                    $table = $this->models->get_table($mid);
                    $field_attach = array();

                    $this->cms_content_attach->table = 'cms_'.$table.'_attach';
                    $list_arr = $this->cms_content_attach->find_fetch(array('id'=>$post['id'], 'isimage'=>0));
                    foreach ($list_arr as &$v){
                        $v['url'] = $this->urls->attach_url($mid, $v['aid']);

                        if( $post[$field] == 'upload/attach/'.$v['filepath']){
                            $field_attach = $v;
                            break;
                        }
                    }
                    $post[$field.'_format'] = $field_attach;
                    break;
            }
        }
    }

    // hook models_field_model_after.php
}

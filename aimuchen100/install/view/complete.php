<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-10-09
 * Time: 15:51
 * Description:安装 参数配置
 */
?>
<!doctype html>
<head>
    <title><?php echo lang('installation_wizard'); ?></title>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="<?php echo $installwebdir; ?>static/layui/lib/layui-v2.8.15/css/layui.css" media="all">
    <style>
        .layui-badge{margin-bottom: 5px;}
    </style>
</head>
<body scroll="no">
<div class="layui-container">
    <div class="layui-fluid">
        <fieldset class="layui-elem-field">
            <legend><?php echo lang('installation_info'); ?></legend>
            <div class="layui-field-box">
                <div id="cont" class="content"></div>
            </div>
        </fieldset>
    </div>
</div>
<script src="<?php echo $installwebdir; ?>static/js/jquery.js" type="text/javascript"></script>
<script type="text/javascript">
    function jsShow(s = '', err = 0) {
        var str = '';
        if(err == 1){
            str = '<span class="layui-badge">'+s+'</span>';
        }else if(err == 2){
            str = s;
        }else if(err == 3){
            str = '<span class="layui-badge layui-bg-blue">'+s+'</span>';
        }else{
            str = '<span class="layui-badge layui-bg-green">'+s+'</span>';
        }
        $("#cont").append(str+"<br>").scrollTop(9999);
    }
</script>
</body>
</html>

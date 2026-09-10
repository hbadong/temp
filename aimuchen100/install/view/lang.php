<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-10-10
 * Time: 15:51
 * Description:安装 授权协议
 */

include INSTALL_PATH.'/config.sample.php';
$version = $_ENV['_config']['version'];
?>
<!doctype html>
<head>
    <title>LECMS <?php echo $version; ?> 选择语言</title>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="<?php echo $installwebdir; ?>static/layui/lib/layui-v2.8.15/css/layui.css" media="all">
    <link rel="stylesheet" href="./css/install.css" media="all">
</head>
<body scroll="no">
<div class="layui-container">
    <div class="layui-fluid install">
        <div class="layui-card layui-form">
            <div class="layui-card-header">LECMS  <?php echo $version; ?> 选择语言</div>
            <div class="layui-card-body">
                <form action="index.php?do=license" method="post" id="form">
                    <div class="layui-form-item">
                        <label class="layui-form-label" style="width: 250px;">Choose Language (选择语言)：</label>
                        <div class="layui-input-inline">
                            <select name="lang">
                                <option value="zh-cn" selected>简体中文</option>
                                <option value="en">英文</option>
                            </select>
                        </div>
                    </div>
                    <div class="layui-form-item">
                        <button type="submit" class="layui-btn layui-btn-fluid" lay-submit lay-filter="lang">下一步(Next)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo $installwebdir; ?>static/layui/lib/layui-v2.8.15/layui.js" charset="utf-8"></script>
<script type="text/javascript">
    layui.use(['form','jquery'], function(){
        var form = layui.form, $ = layui.$;
        //监听提交
        form.on('submit(lang)', function(data){
            var data = data.field;
            $.post("index.php?do=lang",data,function(res){
                window.location.href = "index.php?do=license";
            },'json');
            return false;
        });
    });
</script>
</body>
</html>

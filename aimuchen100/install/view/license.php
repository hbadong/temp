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
    <title>LECMS <?php echo $version.' '.lang('license_title'); ?></title>
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
            <div class="layui-card-header">LECMS  <?php echo $version.lang('license_title'); ?></div>
            <div class="layui-card-body">
                <div class="layui-form-item">
                <?php echo lang('license_content'); ?>
                </div>
                <div class="layui-form-item">
                    <a class="layui-btn layui-btn-fluid" href="index.php?do=check_env"><?php echo lang('agree_license_to_continue');?></a>
                </div>
                <div class="layui-form-item">
                    <button type="button" class="layui-btn layui-btn-fluid layui-btn-danger" onclick="no_agree()"><?php echo lang('no_agree');?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    function no_agree() {
        window.location.replace("about:blank");
        window.close();
    }
</script>
</body>
</html>

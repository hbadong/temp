<?php
include INSTALL_PATH.'/config.sample.php';
$version = $_ENV['_config']['version'];
?>
<!doctype html>
<head>
<title><?php echo lang('installation_wizard'); ?></title>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="<?php echo $installwebdir; ?>static/layui/lib/layui-v2.8.15/css/layui.css" media="all">
    <link rel="stylesheet" href="./css/install.css" media="all">
</head>
<body scroll="no">
<div class="layui-container">
	<div class="layui-fluid">
        <div class="layui-card" style="margin-top: 50px;">
            <div class="layui-card-header">LECMS  <?php echo $version.' '.lang('installation_info'); ?></div>
            <div class="layui-card-body">
                <p class="layui-font-18" style="margin-top: 10px;"><?php echo lang('installed_tips'); ?></p>
            </div>
        </div>
	</div>
</div>
</body>
</html>

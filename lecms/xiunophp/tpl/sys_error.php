<?php defined('FRAMEWORK_PATH') || exit; ?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo 'L'.'ecms'.' '.C('version').' '.lang('error'); ?></title>
<style type="text/css">
body,div,ul,li,h1{margin:0;padding:0}
.lecont h1,.lecont ul,.lecont ul li,.lecont ul li span{font:14px/1.6 'Microsoft YaHei',Verdana,Arial,sans-serif}
.lecont{width:65%;margin:150px auto 0;overflow:hidden;color:#000;border-radius:5px;box-shadow:0 0 20px #555;background:#fff;min-width:300px}
.lecont h1{font-size:18px;height:26px;line-height:26px;padding:10px 3px 0;border-bottom:1px solid #dbdbdb;font-weight:700}
.lecont ul,.lecont h1{width:95%;margin:0 auto;overflow:hidden}
.lecont ul{list-style:none;padding:3px;word-break:break-all}
.lecont ul li{padding:0 3px}
.lecont .fo{border-top:1px solid #dbdbdb;padding:5px 3px 10px;color:#666;text-align:right}
</style>
</head>
<body style="background:#f2f2f2">
<div class="lecont">
	<h1><?php echo lang('error_info'); ?></h1>
	<ul>
		<li><span><?php echo lang('exception_message'); ?>:</span> <font color="red"><?php echo $message;?></font></li>
		<li><span><?php echo lang('exception_file'); ?>:</span> <?php echo $file;?></li>
		<li><span><?php echo lang('exception_line'); ?>:</span> <?php echo lang('exception_lines', array('line'=>$line));?></li>
	</ul>
    <ul class="fo"><?php echo 'L'.'ecms'.' '.C('version'); ?></ul>
</div>
</body>
</html>

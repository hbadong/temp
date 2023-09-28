<?php defined('FRAMEWORK_PATH') || exit; ?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo 'L'.'ecms'.' '.C('version').' '.lang('error'); ?></title>
<style type="text/css">
body,div,ul,li,h1{margin:0;padding:0}
.lecont h1,.lecont ul,.lecont ul li,.lecont ul li span,.lecont ul table tr td{font:14px/1.6 'Microsoft YaHei',Verdana,Arial,sans-serif}
.lecont{width:98%;margin:8px auto;overflow:hidden;color:#000;border-radius:5px;box-shadow:0 0 20px #555;background:#fff;min-width:300px}
.lecont h1{font-size:18px;height:26px;line-height:26px;padding:10px 3px 0;border-bottom:1px solid #dbdbdb;font-weight:700}
.lecont ul,.lecont h1{width:98%;margin:0 auto;overflow:hidden}
.lecont ul{list-style:none;padding:3px;word-break:break-all}
.lecont ul li,.lecont ul table tr td{padding:0 3px}
.lecont ul li span{float:left;display:inline;width:70px}
.lecont ul li.even{background:#ddd}
.lecont .fo{border-top:1px solid #dbdbdb;padding:5px 3px 10px;color:#666;text-align:right}
</style>
</head>
<body style="background:#f2f2f2;padding:8px 0">
<div class="lecont">
	<h1><?php echo lang('error_info'); ?></h1>
	<ul>
		<li><span><?php echo lang('exception_message'); ?>:</span> <font color="red"><?php echo $message;?></font></li>
		<li><span><?php echo lang('exception_file'); ?>:</span> <?php echo $file;?></li>
		<li><span><?php echo lang('exception_line'); ?>:</span> <?php echo lang('exception_lines', array('line'=>$line));?></li>
	</ul>
	<h1><?php echo lang('error_line'); ?></h1>
	<ul><?php echo self::get_code($file, $line);?></ul>
	<h1><?php echo lang('basic_trace'); ?></h1>
	<ul>
		<li><span><?php echo lang('model_trace'); ?>:</span> <?php echo MODEL_PATH;?></li>
		<li><span><?php echo lang('view_trace'); ?>:</span> <?php echo VIEW_PATH.(isset($_ENV['_theme']) ? $_ENV['_theme'] : 'default').'/'; ?></li>
		<li><span><?php echo lang('control_trace'); ?>:</span> <?php echo CONTROL_PATH;?><font color="red"><?php echo $_GET['control'];?>_control.class.php</font></li>
		<li><span><?php echo lang('logs_trace'); ?>:</span> <?php echo RUNTIME_PATH.'logs/';?></li>
	</ul>
	<h1><?php echo lang('program_flow'); ?></h1>
	<ul><?php echo self::arr2str(explode("\n", $tracestr), 0);?></ul>
	<h1>SQL</h1>
	<ul><?php echo self::arr2str($_ENV['_sqls'], 1, FALSE);?></ul>

	<h1>$_GET</h1>
	<ul><?php echo self::arr2str($_GET);?></ul>

	<h1>$_POST</h1>
	<ul style="white-space:pre"><?php echo print_r(_htmls($_POST), 1);?></ul>

	<h1>$_COOKIE</h1>
	<ul><?php echo self::arr2str($_COOKIE);?></ul>

	<h1><?php echo lang('include_file'); ?></h1>
	<ul><?php echo self::arr2str(get_included_files(), 1);?></ul>

	<h1><?php echo lang('other_trace'); ?></h1>
	<ul>
		<li><span><?php echo lang('request_uri_trace'); ?>:</span> <?php echo $_SERVER['REQUEST_URI'];?></li>
		<li><span><?php echo lang('time_trace'); ?>:</span> <?php echo date('Y-m-d H:i:s', $_ENV['_time']);?></li>
		<li><span><?php echo lang('ip_trace'); ?>:</span> <?php echo $_ENV['_ip'];?></li>
		<li><span><?php echo lang('runtime_trace'); ?>:</span> <?php echo runtime();?></li>
		<li><span><?php echo lang('runmen_trace'); ?>:</span> <?php echo runmem();?></li>
	</ul>
	<ul class="fo"><?php echo 'L'.'ecms'.' '.C('version'); ?></ul>
</div>
</body>
</html>

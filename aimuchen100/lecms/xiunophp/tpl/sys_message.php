<?php defined('FRAMEWORK_PATH') || exit; ?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo lang('tips'); ?></title>
<?php if(APP_NAME != 'lecms'){ ?>
<link rel="stylesheet" href="../static/layui/lib/layui-v2.8.15/css/layui.css" media="all">
<?php }else { ?>
<link rel="stylesheet" href="<?php echo http_url_path(); ?>static/layui/lib/layui-v2.8.15/css/layui.css" media="all">
<?php } ?>
<style type="text/css">
.lecms_message{margin: 10px;background-color: whitesmoke;width: calc(100vw - 20px);}
.result {text-align: center;}
.result .success svg {
    color: #32C682;
    text-align: center;
    margin-top: 40px;
}
.result .error svg {
    color: #f56c6c;
    text-align: center;
    margin-top: 40px;
}
.result .message {
    margin-top: 25px;
    width: 60%;
    margin-left: 20%;
    color: rgba(0, 0, 0, .45);
}
</style>
</head>
<body class="lecms_message">
<div class="layui-card">
    <div class="layui-card-header"><i class="layui-icon layui-icon-tips-fill"></i> <?php echo lang('tips'); ?></div>
    <div class="layui-card-body">
        <div class="result">
            <?php if($status){ ?>
            <div class="success">
                <svg viewBox="64 64 896 896" data-icon="check-circle" width="80px" height="80px" fill="currentColor" aria-hidden="true" focusable="false" class=""><path d="M699 353h-46.9c-10.2 0-19.9 4.9-25.9 13.3L469 584.3l-71.2-98.8c-6-8.3-15.6-13.3-25.9-13.3H325c-6.5 0-10.3 7.4-6.5 12.7l124.6 172.8a31.8 31.8 0 0 0 51.7 0l210.6-292c3.9-5.3.1-12.7-6.4-12.7z"></path><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"></path></svg>
            </div>
            <?php }else{ ?>
            <div class="error">
                <svg viewBox="64 64 896 896" data-icon="close-circle" width="80px" height="80px" fill="currentColor" aria-hidden="true" focusable="false" class=""><path d="M685.4 354.8c0-4.4-3.6-8-8-8l-66 .3L512 465.6l-99.3-118.4-66.1-.3c-4.4 0-8 3.5-8 8 0 1.9.7 3.7 1.9 5.2l130.1 155L340.5 670a8.32 8.32 0 0 0-1.9 5.2c0 4.4 3.6 8 8 8l66.1-.3L512 564.4l99.3 118.4 66 .3c4.4 0 8-3.5 8-8 0-1.9-.7-3.7-1.9-5.2L553.5 515l130.1-155c1.2-1.4 1.8-3.3 1.8-5.2z"></path><path d="M512 65C264.6 65 64 265.6 64 513s200.6 448 448 448 448-200.6 448-448S759.4 65 512 65zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"></path></svg>
            </div>
            <?php } ?>

            <h2 class="title"><?php echo $status ? lang('successfully') : lang('failed');?></h2>
            <div class="message">
                <?php echo $message;?>
            </div>
            <hr/>
            <div id="jump"></div>
        </div>
    </div>
</div>
<?php if($jumpurl != -1) { ?>
<script type="text/javascript">
var dot = '', t;
var jump = document.getElementById("jump");
var time = <?php echo $delay;?>;
function jumpurl(){
	<?php echo $jumpurl == 'history.back()' ? 'history.back()' : 'location.href = "'.$jumpurl.'"';?>;
}
function display(){
	dot += '.';
	if(dot.length > 6) dot = '.';
	jump.innerHTML = '<div class="layui-btn-group"><a href="javascript:;" class="layui-btn layui-btn-primary">' + (time--) + '<?php echo lang('auto_jump_tips'); ?>' + dot + '</a><a class="layui-btn layui-btn-primary layui-border" href="javascript:jumpurl();"><?php echo lang('jump_now'); ?></a></div>';
	if(time == -1) {
		clearInterval(t);
		jumpurl();
	}
}
display();
t = setInterval(display, 1000);
</script>
<?php } ?>
</body>
</html>

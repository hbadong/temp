<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-10-09
 * Time: 15:51
 * Description:安装 检测环境
 */

$err = 0;
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
    <div class="layui-fluid install">
        <div class="layui-card">
            <div class="layui-card-header"><?php echo lang('runtime_env_check');?></div>
            <div class="layui-card-body">
                <div class="layui-form-item">
                    <table class="layui-table">
                        <colgroup>
                            <col width="200">
                            <col width="250">
                            <col>
                        </colgroup>
                        <thead>
                        <tr>
                            <th><?php echo lang('runtime_env_check'); ?></th>
                            <th><?php echo lang('required');?></th>
                            <th><?php echo lang('current');?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><?php echo lang('os');?></td>
                            <td>Apache/2.2.x-Linux</td>
                            <td><?php echo trim(preg_replace(array('#PHP\/[\d\.]+#', '#\([\w]+\)#'), '', $_SERVER['SERVER_SOFTWARE'])).'-'.PHP_OS;?></td>
                        </tr>
                        <tr>
                            <td><?php echo lang('php_version');?></td>
                            <td>5.4-8.+</td>
                            <td><?php echo PHP_VERSION; ?></td>
                        </tr>
                        <tr>
                            <td><?php echo lang('file_uploads'); ?></td>
                            <td>2M</td>
                            <td><?php echo function_exists('ini_get') && ini_get('file_uploads') ? ini_get('upload_max_filesize') : 'unknow'; ?></td>
                        </tr>
                        <tr>
                            <td><?php echo lang('disk_free_space'); ?></td>
                            <td>10M+</td>
                            <td><?php echo function_exists('disk_free_space') ? get_byte(disk_free_space(ROOT_PATH)) : 'unknow'; ?></td>
                        </tr>
                        <tr>
                            <td><?php echo lang('mysql'); ?></td>
                            <td><?php echo lang('open'); ?></td>
                            <td><?php
                                if(extension_loaded('mysql')) {
                                    echo '<span class="layui-badge layui-bg-green">mysql</span>';
                                }elseif(extension_loaded('mysqli')){
                                    echo '<span class="layui-badge layui-bg-green">mysqli</span>';
                                }else{
                                    $err = 1;
                                    echo '<span class="layui-bg-red">'.lang('close').'</span>';
                                } ?> (<?php echo lang('close_tips_1'); ?>)</td>
                        </tr>
                        <tr>
                            <td><?php echo lang('gd'); ?></td>
                            <td><?php echo lang('open'); ?></td>
                            <td><?php
                                $gd  = '';
                                if(extension_loaded('gd')) {
                                    function_exists('imagepng') && $gd .= ' png';
                                    function_exists('imagejpeg') && $gd .= ' jpg';
                                    function_exists('imagegif') && $gd .= ' gif';
                                }
                                echo $gd ? '<span class="layui-badge layui-bg-green">'.lang('open').' ['.$gd.']</span>' : '<span class="layui-badge layui-bg-red">'.lang('close').'</span>';
                                ?> (<?php echo lang('close_tips_2'); ?>)</td>
                        </tr>
                        <tr>
                            <td>allow_url_fopen</td>
                            <td><?php echo lang('open'); ?></td>
                            <td><?php echo ini_get('allow_url_fopen') ? '<span class="layui-badge layui-bg-green">'.lang('open').'</span>' : '<span class="layui-badge layui-bg-red">'.lang('close').'</span>'; ?> (<?php echo lang('close_tips_3'); ?>)</td>
                        </tr>
                        </tbody>
                    </table>
                    <table class="layui-table">
                        <colgroup>
                            <col width="200">
                            <col width="250">
                            <col>
                        </colgroup>
                        <thead>
                        <tr>
                            <th><?php echo lang('dir');?></th>
                            <th><?php echo lang('required');?></th>
                            <th><?php echo lang('current');?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        echo '<tr><td>/</td><td>'.lang('writable').' ('.lang('unix_like').' 0777)</td><td>';
                        if(_is_writable(ROOT_PATH)) {
                            echo '<span class="layui-badge layui-bg-green">'.lang('writable').'</span>';
                        }else{
                            $err = 1;
                            echo '<span class="layui-badge layui-bg-red">'.lang('unwritable').'</span>';
                        }
                        echo '</td></tr>';

                        $dirs = array(APP_NAME.'/config', 'log', 'runcache', APP_NAME.'/plugin', 'view', 'upload');
                        foreach($dirs as $dir) {
                            $ret = _dir_write(ROOT_PATH.'/'.$dir, TRUE);
                            echo '<tr><td>/'.$dir.'/*</td><td>'.lang('writable').' ('.lang('unix_like').' 0777)</td><td>';
                            if(!empty($ret['no'])) {
                                $err = 1;
                                echo '<span class="layui-badge layui-bg-red">'.lang('unwritable');
                                foreach($ret['no'] as $i => $row) {
                                    echo '<br>['.$row[1].'] '.str_replace(ROOT_PATH, '', $row[0]);
                                    if($i>8) {
                                        echo '<br>******'; break;
                                    }
                                }
                            }else{
                                echo '<span class="layui-badge layui-bg-green">'.lang('writable').'</span>';
                            }
                            echo '</u></td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
                <div class="layui-form-item">
                    <?php if($err == 0){ ?>
                        <a class="layui-btn layui-btn-fluid" href="index.php?do=check_db"><?php echo lang('next_step');?></a>
                    <?php }else{ ?>
                        <button class="layui-btn layui-btn-danger layui-btn-fluid" onclick="window.location.reload()"><?php echo lang('check_again');?></button>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

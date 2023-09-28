<?php
/**
 * Author: dadadezhou <zhoudada97@foxmail.com>
 * Date: 2022-12-20
 * Time: 11:52
 * Description: LECMS 安装语言包
 */
return array(
    'installation_wizard'=>'LECMS 安装向导',
    'license_title'=>'授权协议',
    'license_content' => '感谢您选择 LECMS。<br><br>LECMS 具有安全、高效、稳定、速度快、负载超强的特点，尤其在大数据量下，它的优秀才更显卓越。
                <br><br>
                LECMS 可以移动端、PC单独加载各端模板，并且URL保持不变，有着非常方便的插件和模板开发机制。
                <br><br>
                LECMS 只有 20 张表，运行速度非常快，处理单次请求在 0.01 秒级别。前后端代码分离，支持自定义模型内容，是一个二次开发非常好的基石。
                <br><br>
                采用 Layuimini 作为后端类库，全面支持移动端浏览器；后端基于 PHP5.3+ MySQL，支持Apc/Yac/Redis/Memcached等 NoSQL 的方式操作各种数据库。
                <br><br>
                LECMS 基于 XiunoPHP 开源框架开发，采用 MIT 协议发布，您可以自由修改、派生版本、商用而不用担心任何法律风险。
                <br><br>
                LECMS 主程序为免费提供使用，不提供任何形式的免费服务。使用者不得将本系统应用于任何形式的非法用途，由此产生的一切法律风险，需由使用者自行承担，与本程序和开发者无关。<br><br>一旦下载、安装、使用LECMS，表示您即承认您已阅读、理解并同意受此条款的约束，并遵守所有相应法律和法规。如果您不同意此类条款，请不要使用本程序。
                <br><br>
                警告：按照我国法律，在未取得相关资源（包含但不限于影片、动画、图书、音乐等）授权的情况下，请勿传播任何形式的相关资源（包含但不限于资源数据文件、种子文件、网盘文件、FTP 文件等）。',
    'agree_license_to_continue'=>'同意协议继续安装',
    'no_agree'=>'不同意协议退出安装',
    'step_1_title' => '安装环境检测',
    'runtime_env_check'=>'网站运行环境检测',
    'required' => '需要',
    'current' => '当前',
    'dir'=>'目录名',
    'os' => '操作系统',
    'php_version' => 'PHP 版本',
    'file_uploads'=>'上传限制',
    'disk_free_space'=>'磁盘空间',
    'mysql'=>'mysql扩展',
    'gd'=>'gd扩展',
    'open'=>'开启',
    'close'=>'关闭',
    'writable'=>'可写',
    'unwritable'=>'不可写',
    'unix_like'=>'类 UNIX',
    'next_step'=>'下一步',
    'check_again'=>'重新检测',
    'close_tips_1'=>'关闭将无法使用本系统',
    'close_tips_2'=>'关闭将不支持缩略图、水印和验证码',
    'close_tips_3'=>'关闭将不支持远程本地化，在线安装模板和插件',

    'db_and_admin'=>'数据库信息和创始用户信息',
    'step_2_title' => '数据库设置',
    'administrators'=>'创始用户',
    'db_host' => '数据库IP',
    'db_host_tip'=>'一般为localhost或者127.0.0.1',
    'db_port' => '数据库端口',
    'db_port_tip' => '默认为3306',
    'db_name' => '数据库名',
    'db_user' => '用户名',
    'db_pass' => '密码',
    'db_user_tip' => '数据库用户名',
    'db_pass_tip' => '数据库密码',
    'db_prefix'=>'表前缀',
    'username'=>'用户名',
    'password'=>'密码',
    'author'=>'作者',
    'author_name'=>'管理员',
    'password_tips'=>'请输入6~32位管理员密码',
    'cover'=>'覆盖安装',
    'yes'=>'是',
    'install'=>'安装',
    'no_mysql_extend'=>'无数据库扩展，无法安装',
    'no_mysql'=>'无mysql和mysqli和pdo_mysql扩展',
    'installation_info'=>'安装信息',
    'installed_tips' => '程序已经安装过了。如需重新安装，请先删除lecms/config/config.inc.php',

    'data_error'=>'数据错误',
    'db_host_no_empty'=>'数据库主机不能为空',
    'db_port_no_empty'=>'数据库端口不能为空',
    'db_name_no_empty'=>'数据库用户名不能为空',
    'db_name_error'=>'数据库名只能是字母数字下划线',
    'db_prefix_no_empty'=>'数据库表前辍不能为空',
    'db_prefix_error'=>'数据库表前辍只能是小写字母和下划线',
    'username_no_empty'=>'创始人用户名不能为空',
    'username_dis_less_2'=>'创始人用户名不能小于2位数',
    'username_dis_over_16'=>'创始人用户名不能大于16位数',
    'password_dis_less_6'=>'创始人密码不能小于6位数',
    'password_dis_over_32'=>'创始人密码不能大于32位数',

    'clear'=>'清除',
    'setting'=>'设置',
    'successfully'=>'成功',
    'failed'=>'失败',
    'plugin_file_non_existent'=>'plugin.sample.php 文件丢失',
    'config_file_non_existent'=>'config.sample.php 文件丢失',
    'install_successfully'=>'恭喜，您的网站已安装完成',
    'home_url'=>'首页地址',
    'admin_url'=>'后台地址',
    'delete_install_dir'=>'为了更加安全，请删除install文件夹哦',
);
<?php defined('ROOT_PATH') or exit;
/**
 * Hook: view_display_after.php
 *
 * 在 view::display() 输出前对渲染结果做运行时 CSS 类前缀替换（模板伪原创）：
 *   站点已启用且配置独立前缀时，将输出 HTML 中所有 \bgm- 类名替换为 {站点前缀}-，
 *   使各站前台 DOM 类名互不相同（防搬运/降模板雷同）。
 *
 * 仅前台生效（后台 admin 用自己视图层）；未启用/未配置前缀时原样输出。
 * 幂等：DEBUG 模式下每次 display 都会 include 本文件，helper 内 function_exists 包裹防重声明。
 */

// 加载共享助手（require_once 幂等）
require_once ROOT_PATH . 'lecms/plugin/template_rewrite/lib/template_rewrite_helper.php';

// 仅前台生效；后台 admin 子应用有独立视图层，不会走到这里（防御性判断）。
// 本 hook 只就地修改 $html，由 view::display() 统一 echo（多插件同名 hook 各自 echo 会输出重复）。
if(defined('APP_NAME') && APP_NAME === 'admin') {
    return;
}

$tr_site_id = defined('CURRENT_SITE_ID') ? (int)CURRENT_SITE_ID : 0;
$tr_cfg = tr_get_settings($tr_site_id);

if(empty($tr_cfg['enabled']) || $tr_cfg['prefix'] === '') {
    return;
}

$html = tr_apply($html, $tr_cfg['prefix']);

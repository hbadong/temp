<?php
defined('ROOT_PATH') || exit;

/**
 * URL路由解析 Hook
 * 在 parseurl_control 解析前拦截，检查 le_cms_url_map 中的站群URL
 *
 * 修复要点：
 * 1) parseurl_control 继承 control（非 base_control），CURRENT_SITE_ID 通常尚未定义，
 *    此处按 HTTP_HOST 独立解析站点ID，不再依赖 base_control 的构造 hook。
 * 2) 命中 URL 映射后必须 return，阻止后续标准解析器 unset 掉 control/action 导致 404。
 * 3) url_hash 统一使用 SHA256（与 url_generator_model 生成端一致）。
 * 4) 控制器不存在时记录日志并返回404（REQ-04-AC4）。
 * 5) 本 hook 内联在 parseurl_control::index() 的 rewrite 分支内，该分支已执行
 *    `$uri = $_GET['rewrite']; unset($_GET['rewrite']);`，因此 $_GET['rewrite'] 此时
 *    已被删除，必须使用作用域内仍有效的 $uri 变量（REQ-04-AC1 修复）。
 */

// 获取 rewrite 参数（优先使用 parseurl 分支内已解出的 $uri 局部变量）
$url = isset($uri) && $uri !== '' ? $uri : (isset($_GET['rewrite']) ? $_GET['rewrite'] : '');
if(empty($url)) return;

// 构造完整 URL 路径（用于哈希计算）
$full_url = '/' . $url;

// 计算 URL Hash（SHA256 截断 40 位，与 url_generator_model::make_url_hash 一致；
// le_cms_url_map.url_hash 列为 char(40)，存 64 位完整哈希会被 MySQL 截断，
// 查询端必须同样截断才能命中）
$url_hash = substr(strtolower(hash('sha256', $full_url)), 0, 40);

// 解析当前站点ID：优先使用 base_control 已定义的常量，否则按域名独立解析
$site_id = defined('CURRENT_SITE_ID') ? CURRENT_SITE_ID : 0;
if(!$site_id) {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    // 去掉端口号后再做域名匹配（dev 环境常带 :8080 / :80）
    $host_noport = $host;
    if($host_noport && strpos($host_noport, ':') !== false) {
        $host_noport = substr($host_noport, 0, strpos($host_noport, ':'));
    }
    if($host) {
        $site_map = null;
        // 优先读运行时缓存（与 site_manager base_control_construct_before 共用）
        if(isset($this->runtime)) {
            $site_map = $this->runtime->xget('site_domain_map');
        }
        if(is_array($site_map) && isset($site_map[$host_noport])) {
            $site_id = (int)$site_map[$host_noport];
        } elseif(is_array($site_map) && isset($site_map[$host])) {
            $site_id = (int)$site_map[$host];
        } else {
            $tablepre = $_ENV['_config']['db']['master']['tablepre'];
            $row = $this->db->fetch_first("SELECT sid FROM `{$tablepre}site_manager` WHERE domain='" . addslashes($host_noport) . "' AND status=1 LIMIT 1");
            if($row) {
                $site_id = (int)$row['sid'];
                if(is_array($site_map)) {
                    $site_map[$host_noport] = $site_id;
                } else {
                    $site_map = array($host_noport => $site_id);
                }
                if(isset($this->runtime)) {
                    $this->runtime->set('site_domain_map', $site_map);
                }
            }
        }
    }
}
if($site_id) {
    // 查询 URL 映射表
    $tablepre = $_ENV['_config']['db']['master']['tablepre'];
    $url_hash = addslashes($url_hash);
    $site_id = (int)$site_id;
    $row = $this->db->fetch_first("
        SELECT id, url, type, control, action, params
        FROM `{$tablepre}cms_url_map`
        WHERE url_hash = '{$url_hash}' AND site_id = {$site_id} AND status IN (1, 2)
        LIMIT 1
    ");

    if($row) {
        // 控制器存在性校验（REQ-04-AC4）：校验通过才命中，否则记录日志返回404
        $control_name = preg_replace('/[^a-zA-Z0-9_]/', '', $row['control']);
        $control_ok = $control_name !== '' && is_file(CONTROL_PATH . $control_name . '_control.class.php');
        if(!$control_ok && !empty($row['control'])) {
            // 插件目录下的控制器
            foreach(get_dirs(PLUGIN_PATH) as $_p) {
                if(is_file(PLUGIN_PATH.$_p.'/control/'.$control_name.'_control.class.php')) {
                    $control_ok = true;
                    break;
                }
            }
        }
        if(!$control_ok) {
            if(DEBUG > 0) {
                $_ENV['_trace'][] = "[url-generator] 控制器不存在: {$row['control']} → 404";
            }
            core::error404();
            return;
        }

        // 命中站群URL - 设置路由参数并终止解析（关键：阻止标准解析器覆盖）
        $_GET['control'] = $row['control'];
        $_GET['action'] = $row['action'];

        // 解析 params JSON，注入路由参数（REQ-04-AC5）
        $params = json_decode($row['params'], true);
        if(is_array($params)) {
            foreach($params as $key => $value) {
                $_GET[$key] = $value;
            }
        }

        // 记录命中日志（调试用）
        if(DEBUG > 0) {
            $_ENV['_trace'][] = "[url-generator] 命中URL: {$full_url} → control={$row['control']}, action={$row['action']}";
        }

        return;
    }
}

// 未命中 - 继续 LECMS 标准路由

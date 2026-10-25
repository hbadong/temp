<?php
/**
 * SEO 预渲染路由（方案 A：UA 识别 + 服务端预渲染）
 * ----------------------------------------------------------------
 * 作为 PHP 内置服务器的 router 脚本：
 *   - 对「搜索引擎爬虫」请求的【动态参数工具页】返回服务端预渲染后的完整 HTML；
 *   - 对其它所有请求 return false，由内置服务器按原逻辑服务（不破坏现有站点）。
 *
 * 动态页：char.html?c=X  /  surname.html?c=X[&wx=Y]  /  mingzi.html?name=X
 * 预渲染结果按 URL 落地文件缓存（默认 30 天），命中即秒回，二次爬虫零成本。
 *
 * 死锁规避：PHP 内置服务器单线程，公网 8090 的 router 不能回连自身让
 * Chromium 抓取。故预渲染抓取指向【内部后端 8091】（独立 worker），
 * 三者（8090 公网 / 8091 内部后端 / 8099 预渲染服务）互不阻塞。
 *
 * 2026-10-25 SEO 优化（审计修复）：
 *   H2  静态文本资源统一 gzip + ETag + Cache-Control（原 22 资源零缓存）
 *   M3  全站安全响应头（X-Content-Type-Options / X-Frame-Options / Referrer-Policy）
 *   M4  surname 矩阵页转发 wx 参数并生成差异化 TDK（原被剥离导致矩阵页 TDK 与主页雷同）
 *   M4  canonical 仍集中到 ?c=X（权重集中设计不变），sitemap 只提交规范 URL
 */

// ---------- 配置 ----------
$BOT_RE = '/(Baiduspider|Googlebot|Bingbot|360Spider|HaosouSpider|Sogou web spider|Sogou|Sogou inst spider|YisouSpider|YandexBot|DuckDuckBot|facebookexternalhit|TwitterBot|Applebot|Bytespider|SemrushBot|AhrefsBot|PetalBot|GPTBot|ClaudeBot|CCBot|ia_archiver|archive\.org_bot|Yeti|NaverBot|Tegaki|Daum)/i';
$RENDER_SVC   = 'http://127.0.0.1:8099/render?url=';
$RENDER_BACKEND = 'http://127.0.0.1:8091';   // 内部静态/php 后端，供 Chromium 抓取
$CACHE_DIR   = '/var/www/lecms/prerender/cache';
$CACHE_TTL   = 30 * 86400;
$DOCROOT     = '/var/www/lecms';

// ---------- 安全响应头（全请求生效，审计 M3） ----------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// 调试开关：带 ?__prerender=1 可强制走预渲染（便于人工验证，不影响正常用户）
$force = isset($_GET['__prerender']) && $_GET['__prerender'] === '1';

$ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH);
$qs   = parse_url($uri, PHP_URL_QUERY) ?? '';

$pageType = null;
if (preg_match('#/tool/char\.html$#i', $path))       $pageType = 'char';
elseif (preg_match('#/tool/surname\.html$#i', $path)) $pageType = 'surname';
elseif (preg_match('#/tool/mingzi\.html$#i', $path))  $pageType = 'mingzi';
elseif (preg_match('#/tool/faq\.html$#i', $path))       $pageType = 'faq';
elseif (preg_match('#/tool/shengxiao\.html$#i', $path)) $pageType = 'shengxiao';
elseif (preg_match('#/tool/(poems|shijing|chuci|tangshi|songci)\.html$#i', $path)) $pageType = 'poems';
elseif (preg_match('#/tool/baijiaxing\.html$#i', $path)) $pageType = 'baijiaxing';
elseif (preg_match('#/tool/lib\.html$#i', $path))       $pageType = 'lib';

$isBot = $force || preg_match($BOT_RE, $ua);
$param = ($pageType === 'mingzi') ? 'name' : (($pageType === 'shengxiao') ? 'z' : 'c');
/* 无参数内容页：核心内容纯 JS 渲染，爬虫需预渲染后才可见（2026-10-25 L1 扩展） */
$NO_PARAM_PAGES = ['faq', 'shengxiao', 'poems', 'baijiaxing', 'lib'];

parse_str($qs, $params);
$paramVal = isset($params[$param]) ? trim($params[$param]) : '';
/* M4：surname 矩阵页的 wx 参数一并转发给渲染层（原实现被剥离，
   导致 JS 的差异化 TDK 分支永不执行，矩阵页与主页 TDK 完全雷同） */
$wxVal = '';
if ($pageType === 'surname') {
    $wxQ = isset($params['wx']) ? trim($params['wx']) : '';
    if (in_array($wxQ, ["金", "木", "水", "火", "土"], true)) $wxVal = $wxQ;
}
/* shengxiao 的 z 参数限定在十二生肖内，避免缓存键被任意值撑爆 */
if ($pageType === 'shengxiao' && $paramVal !== '') {
    $ZODIAC = ["鼠", "牛", "虎", "兔", "龙", "蛇", "马", "羊", "猴", "鸡", "狗", "猪"];
    if (!in_array($paramVal, $ZODIAC, true)) $paramVal = '';
}

$wantPrerender = false;
if ($pageType && $isBot) {
    if (in_array($pageType, $NO_PARAM_PAGES, true)) $wantPrerender = true;
    elseif ($paramVal !== '') $wantPrerender = true;
}

if (!$wantPrerender) {
    // 非预渲染请求：静态文本资源走 gzip + 缓存快路径（H2）
    $staticFile = resolveStaticFile($path);
    if ($staticFile !== null) {
        sendStaticFile($staticFile);
        return true;
    }
}

if ($wantPrerender) {
    // 内部抓取地址（核心参数 + 矩阵 wx；剔除 sharecode 等追踪参数避免污染缓存键）
    $renderParams = [];
    if ($paramVal !== '') $renderParams[$param] = $paramVal;
    if ($wxVal !== '') $renderParams['wx'] = $wxVal;
    $local = $RENDER_BACKEND . $path . ($renderParams ? '?' . http_build_query($renderParams) : '');
    $cacheKey  = md5($local);
    $cacheFile = $CACHE_DIR . '/' . $cacheKey . '.html';

    $html = null;
    if (is_file($cacheFile) && (time() - filemtime($cacheFile) < $CACHE_TTL)) {
        $html = file_get_contents($cacheFile);
    } else {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $RENDER_SVC . urlencode($local),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FAILONERROR    => false,
            CURLOPT_ENCODING       => 'gzip',
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($raw !== false && $code === 200 && strlen($raw) > 500) {
            $html = $raw;
            if (!is_dir($CACHE_DIR)) mkdir($CACHE_DIR, 0755, true);
            @file_put_contents($cacheFile, $html);
        } else {
            error_log("prerender miss for $local http=$code err=$err len=" . strlen((string)$raw));
        }
    }

    if ($html) {
        // 公共 canonical：用请求来源的 Host 还原公网地址。
        // 参数页保留主参数（char/surname/mingzi 的 c/name、shengxiao 的 z），
        // 矩阵页的 wx 与 sharecode 等追踪参数剥离（权重集中到主页，设计不变）；
        // 无参数内容页（faq/典籍/百家姓/文库）canonical 即页面本身。
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        // 代理后通常为 https，且 Host 已是公网域名
        $scheme = 'https';
        $canonQuery = [];
        if ($paramVal !== '' && in_array($pageType, ['char', 'surname', 'mingzi', 'shengxiao'], true)) {
            $canonQuery[$param] = $paramVal;
        }
        $pubUrl = $scheme . '://' . $host . $path . ($canonQuery ? '?' . http_build_query($canonQuery) : '');

        // 重写 canonical（渲染页里是 127.0.0.1:8091，必须改回公网）
        $html = preg_replace(
            '#<link rel="canonical"[^>]*>#i',
            '<link rel="canonical" href="' . htmlspecialchars($pubUrl, ENT_QUOTES) . '">',
            $html, 1, $cnt
        );
        if ($cnt === 0) {
            $html = preg_replace(
                '#</head>#i',
                '<link rel="canonical" href="' . htmlspecialchars($pubUrl, ENT_QUOTES) . '">' . "\n</head>",
                $html, 1
            );
        }

        // 按参数生成唯一 TDK（渲染页标题是通用模板，需改成参数专属）
        $html = rewriteTdk($html, $pageType, $paramVal, $wxVal);

        header('Content-Type: text/html; charset=utf-8');
        header('X-Prerendered: 1');
        sendBody($html, 'text/html; charset=utf-8');
        return true;
    }
    // 预渲染失败：回退到静态空壳（与现状一致，不更差）
}

// 其它请求：原样放行，保持现有站点行为
return false;

/* ================= 以下为 2026-10-25 新增的工具函数 ================= */

/**
 * 解析静态文本资源路径（仅文本类：js/css/json/xml/txt/html/svg）。
 * 图片/字体/视频等二进制资源返回 null，交由内置服务器原生处理（保留 Range 支持）。
 * 带目录穿越防护：realpath 必须位于 DOCROOT 内。
 */
function resolveStaticFile($path) {
    global $DOCROOT;
    if (!preg_match('#\.(js|css|json|xml|txt|html?|svg)$#i', $path)) return null;
    if (preg_match('#\.(php|py|sh|md|lock)$#i', $path)) return null;
    $decoded = urldecode($path);
    $file = realpath($DOCROOT . '/' . ltrim($decoded, '/'));
    if ($file === false || !is_file($file)) return null;
    $root = realpath($DOCROOT);
    if ($root === false || strpos($file, $root) !== 0) return null;
    return $file;
}

/** 静态文本资源响应：ETag/304 + Cache-Control + gzip（审计 H2） */
function sendStaticFile($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $types = [
        'js'   => 'application/javascript', 'css' => 'text/css',
        'json' => 'application/json', 'xml' => 'application/xml',
        'txt'  => 'text/plain', 'html' => 'text/html', 'htm' => 'text/html',
        'svg'  => 'image/svg+xml',
    ];
    $ctype = $types[$ext] . '; charset=utf-8';
    $mtime = filemtime($file);
    $size  = filesize($file);
    $etag  = '"' . $mtime . '-' . $size . '"';

    header('ETag: ' . $etag);
    if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
        http_response_code(304);
        return;
    }
    // 版本化资源（?v= 查询串或 .7.x 命名）→ 30 天强缓存；sitemap/robots → 1 小时；
    // 其它页面外壳 → 5 分钟必须重验证（内容引用均已带版本号）
    $reqUri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#[?&]v=#', $reqUri) || preg_match('#\.7\.(js|css)$#', $file)) {
        header('Cache-Control: public, max-age=2592000, immutable');
    } elseif (in_array($ext, ['xml', 'txt'], true)) {
        header('Cache-Control: public, max-age=3600');
    } else {
        header('Cache-Control: public, max-age=300, must-revalidate');
    }
    $content = file_get_contents($file);
    sendBody($content, $ctype);
}

/**
 * 统一输出：客户端接受 gzip 且内容可压缩时启用 gzip（审计 H2，
 * 首页 22 个资源约 584 KiB 可省；百度/Google 爬虫均声明 Accept-Encoding: gzip）
 */
function sendBody($content, $ctype) {
    $accept = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (stripos($accept, 'gzip') !== false && strlen($content) > 512) {
        $gz = gzencode($content, 6);
        if ($gz !== false) {
            header('Content-Encoding: gzip');
            header('Vary: Accept-Encoding');
            header('Content-Type: ' . $ctype);
            header('Content-Length: ' . strlen($gz));
            if ($method !== 'HEAD') echo $gz;
            return;
        }
    }
    header('Content-Type: ' . $ctype);
    header('Content-Length: ' . strlen($content));
    if ($method !== 'HEAD') echo $content;
}

/**
 * 根据参数类型重写 <title> / description / og:title / og:description，
 * 让每个动态落地页拥有唯一且贴合的 TDK，提升收录与点击。
 * 2026-10-25 M4：surname 矩阵页（带 wx）使用差异化 TDK，与主页公式区分。
 */
function rewriteTdk($html, $type, $val, $wx = '') {
    if ($type === 'char') {
        $c = $val;
        $title = "{$c}字寓意_{$c}字起名含义、五行属性与康熙笔画 - 好名网";
        $desc  = "详解「{$c}」字的拼音部首、康熙笔画、五行属性与起名寓意，并提供同音、同部首、同五行用字推荐，助你为宝宝挑选好名。";
    } elseif ($type === 'surname') {
        $c = $val;
        if ($wx !== '') {
            // 矩阵页差异化 TDK（与 main.7.js initSurname 的公式保持一致）
            $title = "{$c}姓五行属{$wx}的名字_{$c}姓五行缺{$wx}怎么取名 - 好名网";
            $desc  = "{$c}姓五行属{$wx}的名字专题：{$wx}行用字与{$c}姓的笔画搭配、音韵协调与范例名，回答「{$c}姓五行缺{$wx}怎么取名」，可直接在线生成{$c}姓{$wx}行名字。";
        } else {
            $title = "{$c}姓起名_{$c}姓男孩女孩名字大全与八字起名 - 好名网";
            $desc  = "查看「{$c}」姓的拼音、康熙笔画与起名搭配，提供{$c}姓男孩女孩名字推荐及八字起名入口。";
        }
    } elseif ($type === 'mingzi') {
        $name = $val;
        $title = "{$name}名字寓意_{$name}姓名解析、五格数理与打分 - 好名网";
        $desc  = "解析「{$name}」的用字寓意、五格数理配置、音律结构与八字匹配度，并提供相近名字推荐。";
    } else {
        // 无参数内容页（faq/shengxiao/典籍/百家姓/文库）：保留 JS 渲染的页面级 TDK
        return $html;
    }

    $html = preg_replace('#<title>[^<]*</title>#i', '<title>' . htmlspecialchars($title, ENT_QUOTES) . '</title>', $html, 1);
    $html = preg_replace(
        '#<meta name="description" content="[^"]*"#i',
        '<meta name="description" content="' . htmlspecialchars($desc, ENT_QUOTES) . '"',
        $html, 1
    );
    $html = preg_replace(
        '#<meta property="og:title" content="[^"]*"#i',
        '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES) . '"',
        $html, 1
    );
    $html = preg_replace(
        '#<meta property="og:description" content="[^"]*"#i',
        '<meta property="og:description" content="' . htmlspecialchars($desc, ENT_QUOTES) . '"',
        $html, 1
    );
    return $html;
}

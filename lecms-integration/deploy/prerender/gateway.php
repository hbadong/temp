<?php
/**
 * SEO 预渲染网关（PHP-FPM 版 · 生产环境）
 * ----------------------------------------------------------------
 * 由 Nginx 将「搜索引擎爬虫 UA + 动态工具页」请求 proxy_pass 到本脚本。
 * 逻辑与开发环境 router.php 的预渲染部分完全一致（缓存/canonical/TDK 重写），
 * 区别：静态资源服务已移交 Nginx，本脚本只干预渲染一件事。
 *
 * 部署位置：<网站根>/prerender/gateway.php
 * Nginx 调用：proxy_pass http://127.0.0.1:8098;（配一个专属 upstream 或直连 FPM）
 *
 * 工作原理：
 *   爬虫请求 /view/haoiming/tool/char.html?c=林
 *     → Nginx 命中 UA 规则与本路径规则 → 转发本脚本
 *     → 本脚本以「普通 UA」请求本站同一 URL（内部回源），交给 Playwright 渲染服务
 *     → 渲染结果按 URL 落地缓存（默认 30 天），命中即秒回
 *     → 重写 canonical 为公网地址 + 按参数生成差异化 TDK
 *     → gzip 输出（爬虫均声明 Accept-Encoding: gzip）
 *
 * 2026-10-25 从开发环境 router.php 抽取移植（SEO 第二十四轮部署包）。
 */

// ---------- 配置（按服务器实际改这里） ----------
$RENDER_SVC      = 'http://127.0.0.1:8099/render?url=';   // Playwright 预渲染服务
$SITE_ORIGIN     = 'http://127.0.0.1';                    // 本站回源地址（Nginx 监听的内网地址）
$CACHE_DIR       = __DIR__ . '/cache';                    // 预渲染产物缓存目录
$CACHE_TTL       = 30 * 86400;                            // 缓存 30 天
$PUBLIC_SCHEME   = 'https';                               // 对外协议（CDN/反代终止 SSL 时为 https）
$DEBUG           = false;                                 // true 时输出调试日志到 error_log

$BOT_RE = '/(Baiduspider|Googlebot|Bingbot|360Spider|HaosouSpider|Sogou web spider|Sogou|Sogou inst spider|YisouSpider|YandexBot|DuckDuckBot|facebookexternalhit|TwitterBot|Applebot|Bytespider|SemrushBot|AhrefsBot|PetalBot|GPTBot|ClaudeBot|CCBot|ia_archiver|archive\.org_bot|Yeti|NaverBot|Tegaki|Daum)/i';

// ---------- 入口：只处理白名单内的动态工具页，其余交还 Nginx 404 ----------
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$qs   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_QUERY) ?? '';

$pageType = null;
if (preg_match('#/tool/char\.html$#i', $path))            $pageType = 'char';
elseif (preg_match('#/tool/surname\.html$#i', $path))     $pageType = 'surname';
elseif (preg_match('#/tool/mingzi\.html$#i', $path))      $pageType = 'mingzi';
elseif (preg_match('#/tool/faq\.html$#i', $path))         $pageType = 'faq';
elseif (preg_match('#/tool/shengxiao\.html$#i', $path))   $pageType = 'shengxiao';
elseif (preg_match('#/tool/(poems|shijing|chuci|tangshi|songci)\.html$#i', $path)) $pageType = 'poems';
elseif (preg_match('#/tool/baijiaxing\.html$#i', $path))  $pageType = 'baijiaxing';
elseif (preg_match('#/tool/lib\.html$#i', $path))         $pageType = 'lib';

if ($pageType === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'gateway: unsupported path';
    exit;
}

$ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (!preg_match($BOT_RE, $ua)) {
    // 非爬虫不应到达这里（Nginx 已分流）；兜底直接回源静态页
    header('Location: ' . $SITE_ORIGIN . $path . ($qs ? "?$qs" : ''), true, 302);
    exit;
}

$param = ($pageType === 'mingzi') ? 'name' : (($pageType === 'shengxiao') ? 'z' : 'c');
$NO_PARAM_PAGES = ['faq', 'shengxiao', 'poems', 'baijiaxing', 'lib'];

parse_str($qs, $params);
$paramVal = isset($params[$param]) ? trim($params[$param]) : '';

/* surname 矩阵页的 wx 参数一并转发（M4：原实现被剥离致矩阵页 TDK 雷同） */
$wxVal = '';
if ($pageType === 'surname') {
    $wxQ = isset($params['wx']) ? trim($params['wx']) : '';
    if (in_array($wxQ, ["金", "木", "水", "火", "土"], true)) $wxVal = $wxQ;
}
/* shengxiao 的 z 限定十二生肖，避免缓存键被任意值撑爆 */
if ($pageType === 'shengxiao' && $paramVal !== '') {
    $ZODIAC = ["鼠", "牛", "虎", "兔", "龙", "蛇", "马", "羊", "猴", "鸡", "狗", "猪"];
    if (!in_array($paramVal, $ZODIAC, true)) $paramVal = '';
}

if (!in_array($pageType, $NO_PARAM_PAGES, true) && $paramVal === '') {
    // 无参数动态页：不是预渲染目标，交还静态外壳（JS 会自行 noindex 无效参数）
    header('Location: ' . $SITE_ORIGIN . $path . ($qs ? "?$qs" : ''), true, 302);
    exit;
}

// ---------- 安全响应头（与 Nginx 层双保险） ----------
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// ---------- 预渲染（缓存优先） ----------
$renderParams = [];
if ($paramVal !== '') $renderParams[$param] = $paramVal;
if ($wxVal !== '')    $renderParams['wx'] = $wxVal;
$local = $SITE_ORIGIN . $path . ($renderParams ? '?' . http_build_query($renderParams) : '');
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
    } elseif ($DEBUG) {
        error_log("gateway prerender miss for $local http=$code err=$err len=" . strlen((string)$raw));
    }
}

if (!$html) {
    // 预渲染失败：回源静态空壳（不比不预渲染更差）
    header('Location: ' . $SITE_ORIGIN . $path . ($qs ? "?$qs" : ''), true, 302);
    exit;
}

// ---------- canonical 重写（回源地址 → 公网地址） ----------
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$canonQuery = [];
if ($paramVal !== '' && in_array($pageType, ['char', 'surname', 'mingzi', 'shengxiao'], true)) {
    $canonQuery[$param] = $paramVal;
}
$pubUrl = $PUBLIC_SCHEME . '://' . $host . $path . ($canonQuery ? '?' . http_build_query($canonQuery) : '');

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

// ---------- 差异化 TDK ----------
$html = rewriteTdk($html, $pageType, $paramVal, $wxVal);

header('Content-Type: text/html; charset=utf-8');
header('X-Prerendered: 1');
sendBody($html, 'text/html; charset=utf-8');

/* ================= 工具函数（与开发环境 router.php 保持一致） ================= */

function sendBody($content, $ctype) {
    $accept = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    if (stripos($accept, 'gzip') !== false && strlen($content) > 512) {
        $gz = gzencode($content, 6);
        if ($gz !== false) {
            header('Content-Encoding: gzip');
            header('Vary: Accept-Encoding');
            header('Content-Type: ' . $ctype);
            header('Content-Length: ' . strlen($gz));
            echo $gz;
            return;
        }
    }
    header('Content-Type: ' . $ctype);
    header('Content-Length: ' . strlen($content));
    echo $content;
}

function rewriteTdk($html, $type, $val, $wx = '') {
    if ($type === 'char') {
        $c = $val;
        $title = "{$c}字寓意_{$c}字起名含义、五行属性与康熙笔画 - 好名网";
        $desc  = "详解「{$c}」字的拼音部首、康熙笔画、五行属性与起名寓意，并提供同音、同部首、同五行用字推荐，助你为宝宝挑选好名。";
    } elseif ($type === 'surname') {
        $c = $val;
        if ($wx !== '') {
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
        '<meta property="og:title" content="' . htmlspecialchars($title, ENT_QUOTES) . '">',
        $html, 1
    );
    $html = preg_replace(
        '#<meta property="og:description" content="[^"]*"#i',
        '<meta property="og:description" content="' . htmlspecialchars($desc, ENT_QUOTES) . '">',
        $html, 1
    );
    return $html;
}

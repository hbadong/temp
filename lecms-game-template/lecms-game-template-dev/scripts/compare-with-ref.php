<?php
/**
 * 与参考站对比脚本（核心沉淀 #14, #16）
 * 用法：php scripts/compare-with-ref.php <参考站HTML> <本地模板> [本地渲染HTML]
 *
 * 例：
 *   静态对比：php scripts/compare-with-ref.php ref.html view/macfkm/index.htm
 *   渲染对比：php scripts/compare-with-ref.php ref.html view/macfkm/index.htm http://localhost:8099/
 */

if ($argc < 3) {
    fwrite(STDERR, "用法：php scripts/compare-with-ref.php <参考站HTML> <本地模板或目录> [本地URL前缀]\n");
    exit(1);
}

$ref_file = $argv[1];
$local = $argv[2];
$url_prefix = $argv[3] ?? null;

if (!file_exists($ref_file)) {
    fwrite(STDERR, "❌ 参考站文件不存在: $ref_file\n");
    exit(1);
}

$ref = file_get_contents($ref_file);

/* === 本地内容获取 === */
if (is_dir($local)) {
    // 目录：取 index.htm
    $local = rtrim($local, '/') . '/index.htm';
}
if (!file_exists($local)) {
    fwrite(STDERR, "❌ 本地文件不存在: $local\n");
    exit(1);
}

$local_content = file_get_contents($local);

/* 如果提供了 URL 前缀，curl 渲染后取 HTML */
if ($url_prefix) {
    $url = rtrim($url_prefix, '/') . '/' . pathinfo($local, PATHINFO_FILENAME);
    echo "（curl 渲染本地: $url）\n";
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $rendered = curl_exec($ch);
    if ($rendered) {
        $local_content = $rendered;
    }
    curl_close($ch);
}

/* === 1. section 区块对比（核心沉淀 #14） === */
echo "=== 1. section 区块清单对比 ===\n";
preg_match_all('/<section class="([^"]+)"/', $ref, $mr);
preg_match_all('/<section class="([^"]+)"/', $local_content, $mo);
$ref_sections = array_unique(array_map(create_function('$c', 'return preg_replace("/__[a-zA-Z0-9_-]+__/", "", $c);'), $mr[1]));
$local_sections = array_unique(array_map(create_function('$c', 'return preg_replace("/__[a-zA-Z0-9_-]+__/", "", $c);'), $mo[1]));

$missing = array_diff($ref_sections, $local_sections);
$extra = array_diff($local_sections, $ref_sections);

if ($missing) {
    echo "❌ 参考站有但本地缺 " . count($missing) . " 个区块：\n";
    foreach (array_slice($missing, 0, 10) as $s) echo "  - $s\n";
} else {
    echo "✓ 区块清单一致\n";
}
if ($extra) {
    echo "⚠️ 本地多 " . count($extra) . " 个区块：\n";
    foreach (array_slice($extra, 0, 10) as $s) echo "  - $s\n";
}

/* === 2. 关键 class 元素级对比（核心沉淀 #16） === */
echo "\n=== 2. 关键 class 元素级对比 ===\n";
$key_classes = [
    'heroSection', 'heroPrimary', 'heroBadge', 'heroCardTitle',
    'storeCard', 'storeCardImage', 'storeCardTitle',
    'discoverItem', 'discoverName',
    'quickFactChip', 'supportList',
    'compactActionCard', 'compactShareButton',
    'footerLink', 'footerCopy',
    'topnavLink', 'topnavItem', 'brandStore',
];
$matched = 0; $mismatched = 0;
foreach ($key_classes as $cls) {
    // 同时去掉 CSS Modules hash（如 windows-homepage-module__xxx__heroSection）
    $in_ref = preg_match('/' . preg_quote($cls, '/') . '/', $ref);
    $in_local = preg_match('/' . preg_quote($cls, '/') . $local_content);
    $status = ($in_ref === $in_local) ? '✓' : '⚠️';
    ($in_ref && $in_local) ? $matched++ : (($in_ref !== $in_local) ? $mismatched++ : 0);
    echo "  $status $cls (ref:" . ($in_ref ? 'Y' : 'N') . " local:" . ($in_local ? 'Y' : 'N') . ")\n";
}

/* === 3. 区块内容量对比 === */
echo "\n=== 3. 区块内容量对比 ===\n";
foreach (['storeCard', 'discoverItem', 'quickFactChip', 'rankingPanel', 'detailInfoItem'] as $cls) {
    $r = substr_count($ref, $cls);
    $l = substr_count($local_content, $cls);
    $status = ($r === $l) ? '✓' : '⚠️';
    echo "  $status $cls: 参考站=$r 本地=$l\n";
}

/* === 总结 === */
echo "\n=== 总结 ===\n";
echo "区块清单: " . ($missing ? '❌ 有缺失' : '✓ 一致') . "\n";
echo "关键 class: $matched 一致 / $mismatched 差异\n";
exit($missing || $mismatched ? 1 : 0);
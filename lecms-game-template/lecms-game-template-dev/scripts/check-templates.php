<?php
/**
 * 模板检查脚本（核心沉淀 #14-#18）
 * 用法：php scripts/check-templates.php [主题目录]
 * 例：php scripts/check-templates.php view/macfkm
 */

$theme_dir = $argv[1] ?? 'view/macfkm';
if (!is_dir($theme_dir)) {
    fwrite(STDERR, "❌ 主题目录不存在: $theme_dir\n");
    exit(1);
}

$issues = 0;

/* === 1. 必需文件清单 === */
echo "=== 1. 必需文件清单（核心沉淀 #14）===\n";
$required = [
    'info.ini', 'show.jpg',
    'index.htm', 'inc-header.htm', 'inc-footer.htm',
    'page_show.htm', '404.htm', 'comment.htm',
];
foreach ($required as $f) {
    $exists = file_exists("$theme_dir/$f");
    echo ($exists ? '✓' : '❌') . " $f\n";
    $exists ?: $issues++;
}

/* === 2. 标签平衡检查（核心沉淀 #15） === */
echo "\n=== 2. 标签平衡检查（核心沉淀 #15）===\n";
$balance_issues = 0;
foreach (glob("$theme_dir/*.htm") as $f) {
    $s = file_get_contents($f);
    $base = basename($f);

    foreach (['block', 'loop', 'if'] as $tag) {
        $open = preg_match_all('/\{' . $tag . '[:\}]/', $s);
        $close = preg_match_all('/\{\/' . $tag . '\}/', $s);
        if ($open !== $close) {
            echo "⚠️ $base: $tag 不平衡（$open 开 / $close 闭）\n";
            $balance_issues++;
        }
    }

    // section 平衡
    $sec_open = preg_match_all('/<section[\s>]/', $s);
    $sec_close = substr_count($s, '</section>');
    if ($sec_open !== $sec_close) {
        echo "⚠️ $base: section 不平衡（$sec_open 开 / $sec_close 闭）—— 注意跨文件 section 可能正常\n";
    }
}
echo $balance_issues ? "" : "✓ 所有模板 block/loop/if 平衡\n";
$issues += $balance_issues;

/* === 3. inc 映射检查 === */
echo "\n=== 3. inc 映射检查（核心沉淀 #2 命名规范）===\n";
$content = '';
foreach (glob("$theme_dir/*.htm") as $f) {
    $content .= file_get_contents($f);
}
preg_match_all('/\{inc:([^}]+)\}/', $content, $m);
$incs = array_unique($m[1]);
$missing = [];
foreach ($incs as $inc) {
    // 命名限制：文件名不能含连字符
    if (strpos($inc, '-') !== false) {
        echo "⚠️ {inc:$inc} 文件名含连字符（编译后会解析为 inc-inc...），建议改名\n";
        $issues++;
        continue;
    }
    $target = "$theme_dir/inc-$inc";
    if (!file_exists($target)) {
        $missing[] = $inc;
        echo "❌ {inc:$inc} → inc-$inc 不存在\n";
        $issues++;
    }
}
if (empty($missing) && empty(array_filter($incs, create_function('$i', 'return strpos($i, "-") !== false;')))) {
    echo "✓ 所有 inc 文件存在（" . count($incs) . " 个引用）\n";
}

/* === 4. PHP 语法（嵌入模板的 {php} 块） === */
echo "\n=== 4. {php} 块语法检查（核心沉淀 #32）===\n";
$php_issues = 0;
foreach (glob("$theme_dir/*.htm") as $f) {
    $s = file_get_contents($f);
    if (preg_match_all('/\{php\}(.*?)\{\/php\}/s', $s, $m)) {
        foreach ($m[1] as $i => $code) {
            $tmp = tempnam(sys_get_temp_dir(), 'php_check_');
            file_put_contents($tmp, "<?php\n" . trim($code) . "\n");
            $out = shell_exec("php -l $tmp 2>&1");
            unlink($tmp);
            if (strpos($out, 'No syntax errors') === false) {
                echo "⚠️ " . basename($f) . " {php} 块 #$i 语法错误：\n$out\n";
                $php_issues++;
                $issues++;
            }
        }
    }
}
echo $php_issues ? "" : "✓ 所有 {php} 块语法正确\n";

/* === 总结 === */
echo "\n=== 总结 ===\n";
echo $issues ? "❌ $issues 个问题需修复\n" : "✓ 全部通过\n";
exit($issues ? 1 : 0);
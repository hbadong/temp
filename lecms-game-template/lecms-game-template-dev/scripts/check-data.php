<?php
/**
 * 数据库层检查脚本（核心沉淀 #11-#13）
 * 用法：php scripts/check-data.php [数据库名]
 * 默认从 le_kv cfg 读取数据库连接
 */

$db_name = $argv[1] ?? null;
$host = '127.0.0.1';
$user = 'root';
$pass = '';

if ($db_name) {
    // 直接使用
} else {
    // 从 le_kv 读取 cfg
    if (!file_exists('index.php')) {
        fwrite(STDERR, "❌ 请在项目根目录运行此脚本，或指定数据库名作为参数\n");
        exit(1);
    }
    $kv = @file_get_contents('index.php');
    // 简化：提示用户配置
    fwrite(STDERR, "用法：php scripts/check-data.php <数据库名>\n");
    fwrite(STDERR, "或配置脚本顶部的 \$host/\$user/\$pass\n");
    exit(1);
}

$conn = @new mysqli($host, $user, $pass, $db_name);
if ($conn->connect_errno) {
    fwrite(STDERR, "❌ 数据库连接失败: " . $conn->connect_error . "\n");
    fwrite(STDERR, "请修改脚本顶部配置 \$host/\$user/\$pass/\$db_name\n");
    exit(1);
}

$issues = 0;

/* === 1. le_category alias 完整性（核心沉淀 #11） === */
echo "=== 1. 分类 alias 检查（核心沉淀 #11）===\n";
$r = $conn->query("SELECT COUNT(*) c FROM le_category WHERE alias = '' OR alias IS NULL");
$row = $r->fetch_assoc();
if ($row['c']) {
    echo "❌ $row[c] 个分类 alias 为空（URL 将 404）\n";
    $issues += $row['c'];
} else {
    echo "✓ 所有分类 alias 非空\n";
}

// alias 唯一性
$r = $conn->query("SELECT alias, COUNT(*) c FROM le_category WHERE alias != '' GROUP BY alias HAVING c > 1");
if ($r->num_rows) {
    echo "❌ 重复 alias：\n";
    while ($row = $r->fetch_assoc()) echo "  - $row[alias] ($row[c] 次)\n";
    $issues += $r->num_rows;
} else {
    echo "✓ alias 唯一\n";
}

/* === 2. le_only_alias 与 le_category 一致性（核心沉淀 #11） === */
echo "\n=== 2. alias 注册一致性（核心沉淀 #11）===\n";
$r = $conn->query("SHOW TABLES LIKE 'le_only_alias'");
if (!$r->num_rows) {
    echo "❌ le_only_alias 表不存在\n";
    $issues++;
} else {
    $r = $conn->query("SELECT a.alias, c.cid FROM le_only_alias a LEFT JOIN le_category c ON a.alias=c.alias WHERE c.cid IS NULL");
    if ($r->num_rows) {
        echo "❌ le_only_alias 有 " . $r->num_rows . " 条孤儿记录\n";
        $issues += $r->num_rows;
    } else {
        echo "✓ le_only_alias 与 le_category 一致\n";
    }
}

/* === 3. 数据表核对（核心沉淀 #13） === */
echo "\n=== 3. 数据表核对（核心沉淀 #13）===\n";
$required_tables = [
    // 模型相关（按需启用）
    // 'le_cms_{model}_data', 'le_cms_{model}_flag', 'le_cms_{model}_tag_data', 'le_cms_{model}_views'
];

// 检查 tag_data 主键
foreach ($conn->query("SHOW TABLES LIKE 'le_cms_%_tag_data'") as $row) {
    $table = $row[0];
    $r = $conn->query("SHOW INDEX FROM $table WHERE Key_name = 'PRIMARY'");
    if (!$r->num_rows) {
        echo "❌ $table 无主键\n";
        $issues++;
        continue;
    }
    $cols = [];
    while ($idx = $r->fetch_assoc()) $cols[] = $idx['Column_name'];
    sort($cols);
    if ($cols !== ['id', 'tagid']) {
        echo "⚠️ $table 主键为 (" . implode(',', $cols) . ")，建议为 (tagid, id) 联合主键\n";
        $issues++;
    } else {
        echo "✓ $table 联合主键 (tagid, id) 正确\n";
    }
}

// 检查 views 表
foreach ($conn->query("SHOW TABLES LIKE 'le_cms_%_views'") as $row) {
    echo "✓ $row[0] 存在\n";
}

// 检查 flag 表主键
foreach ($conn->query("SHOW TABLES LIKE 'le_cms_%_flag'") as $row) {
    $table = $row[0];
    $r = $conn->query("SHOW INDEX FROM $table WHERE Key_name = 'PRIMARY'");
    if ($r->num_rows) {
        $cols = [];
        while ($idx = $r->fetch_assoc()) $cols[] = $idx['Column_name'];
        sort($cols);
        if ($cols !== ['cid', 'flag', 'id']) {
            echo "⚠️ $table 主键为 (" . implode(',', $cols) . ")，建议为 (flag, cid, id) 联合主键\n";
            $issues++;
        } else {
            echo "✓ $table 联合主键 (flag, cid, id) 正确\n";
        }
    }
}

/* === 4. 叶子分类 type 检查（核心沉淀 #8） === */
echo "\n=== 4. 分类 type 检查（核心沉淀 #8）===\n";
$r = $conn->query("SELECT COUNT(*) c FROM le_category WHERE type = 1 AND mid IN (2,3,6)");
$row = $r->fetch_assoc();
if ($row['c']) {
    echo "⚠️ $row[c] 个 mid IN (2,3,6) 的分类 type=1（顶级）但应作为叶子（type=0）\n";
    echo "  提示：type=1 顶级分类需在频道页展示，type=0 是叶子用于 list 列表\n";
}

/* === 5. le_runtime 缓存时效（核心沉淀 #20） === */
echo "\n=== 5. le_runtime 缓存时效（核心沉淀 #20）===\n";
$r = $conn->query("SELECT k, FROM_UNIXTIME(expiry) expires FROM le_runtime WHERE expiry > 0 ORDER BY expiry DESC LIMIT 5");
if ($r->num_rows) {
    echo "最近的缓存条目（提示：修改分类/配置后需 DELETE FROM le_runtime）：\n";
    while ($row = $r->fetch_assoc()) echo "  $row[k] → 到期 $row[expires]\n";
} else {
    echo "（无缓存条目或全部永久）\n";
}

/* === 总结 === */
echo "\n=== 总结 ===\n";
echo $issues ? "❌ $issues 个问题需修复\n" : "✓ 数据库层全部通过\n";

$conn->close();
exit($issues ? 1 : 0);
## 相关章节

| 类型 | 文件 |
|------|------|
| 前提 | [04-develop-templates.md](04-develop-templates.md) |
| 后台配置 | [05-backend-config.md](05-backend-config.md) |
| 常见陷阱 | [09-pitfalls.md](09-pitfalls.md) |

---

## 六、占位符替换

开发阶段先用中文占位符标记待替换值：

```html
{block:list cid="手游攻略cid" limit="5"}
{block:category cid="手机游戏分类cid" type="child">
```

配置完成后，从数据库查询真实 ID 批量替换：

```bash
php -r "
\$pdo = new PDO('mysql:host=127.0.0.1;dbname=DBNAME;charset=utf8mb4', 'USER', 'PASS');
\$stmt = \$pdo->query('SELECT cid, name, mid, upid, alias FROM le_category ORDER BY upid, cid');
foreach(\$stmt->fetchAll(PDO::FETCH_ASSOC) as \$r) {
    echo \$r['cid'] . ' | ' . \$r['name'] . ' | mid=' . \$r['mid'] . ' | upid=' . \$r['upid'] . ' | ' . \$r['alias'] . PHP_EOL;
}
"
```

> **重要**：替换后必须清缓存（`le_runtime` + `runcache/lecms_view/`），否则分类/模板数据不生效。


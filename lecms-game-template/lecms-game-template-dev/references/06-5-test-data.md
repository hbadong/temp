## 相关章节

| 类型 | 文件 |
|------|------|
| 后台配置 | [05-backend-config.md](05-backend-config.md) |
| 后续使用 | [07-functional-test.md](07-functional-test.md) |
| 排序一致性 | SKILL.md 核心沉淀 #46-#50（cid 劫持 / views 垃圾行 / 缺口盘点） |

---

## 六点五、测试数据生成（可选）

SQL 直接插入测试内容需注意：

| 数据 | 同步操作 |
|------|---------|
| 主表 `cms_{model}` | 插入 title/cid/intro/pic/dateline/flags 等 |
| 数据表 `cms_{model}_data` | `content` 正文 |
| 浏览表 `cms_{model}_views` | `views` 浏览量（排行区块数据源，缺了排行空） |
| 标签表 `cms_{model}_tag` | 标签名 + count |
| 标签数据表 `cms_{model}_tag_data` | tagid↔id 关联（注意联合主键） |
| 属性表 `cms_{model}_flag` | 从主表 flags 字段解析填充 |
| 分类计数 `le_category.count` | `UPDATE le_category SET count = n` |

### 批量插入脚本规范（实战教训）

1. **三表同步**：主表 + `_data` + `_views` 必须同时插入；只插主表 → 详情页能开但"相关游戏"（list）取不到 content、排行区块（list_top views）空
2. **必须校验 `lastInsertId()`**：主表插入失败（如 SQL 参数数量不匹配 HY093）时 `lastInsertId()` 返回 0，**继续执行关联表插入会留下 `id=0` 垃圾行**（views 表主键无约束时第一次能插入成功）→ views 排序取到 id=0 → mget 返回 NULL → 渲染中断（详见 SKILL.md #47）。正确写法：

   ```php
   $st->execute($params);
   $iid = $pdo->lastInsertId();
   if (!$iid) { echo '插入失败: ' . $title . PHP_EOL; continue; } // 跳过关联表
   $pdo->prepare('INSERT INTO le_cms_game_views (id,cid,views) VALUES (?,?,?)')->execute([$iid, $cid, $views]);
   ```
3. **执行后核查垃圾行**：`SELECT COUNT(*) FROM le_cms_{model}_views WHERE id=0`（应为 0）；同时确认主表/视图表 id=0 行为 0
4. **脚本执行方式**：bash 内联 `php -r "..."` 写长 SQL/数组极易引号错误（HY093、parse error）——用 Write 工具写临时 .php 文件 → `php 脚本.php` → 验证输出 → 删除脚本
5. **插入后清理**：`DELETE FROM le_runtime`（分类计数/结构缓存）

### 数据缺口盘点流程（详情页区块非空的前提）

详情页"相关游戏/相关软件"（block:list）取**当前分类**数据，分类仅 1 条时模板排除自身后区块空白。修完模板统一盘点：

```sql
-- 1. 各分类数据量
SELECT cid, COUNT(*) c FROM le_cms_game GROUP BY cid ORDER BY cid;
-- 2. 每个详情页所属分类的"同分类+兄弟分类"可用量
--    注意：子查询不要 JOIN le_category（别名不引用会按全表行数放大 COUNT）
SELECT t.cid,
  (SELECT COUNT(*) FROM le_cms_game WHERE cid = t.cid) AS same_cate,
  (SELECT COUNT(*) FROM le_cms_game g2 WHERE g2.cid IN
     (SELECT cid FROM le_category WHERE upid = (SELECT upid FROM le_category WHERE cid = t.cid))
     AND g2.id != t.id) AS sibling
FROM le_cms_game t WHERE t.id IN (33, 39);  -- 替换为详情页测试内容 id
```

缺口分类统一补齐（游戏名/简介/大小/更新时间/views 各成体系，views 值错开保证排序有层次）。


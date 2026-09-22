## 相关章节

| 类型 | 文件 |
|------|------|
| 后续使用 | [04-develop-templates.md](04-develop-templates.md)（模板开发时引用模型字段）|
| 后续使用 | [06-placeholder-replace.md](06-placeholder-replace.md) |
| 数据库检查工具 | [scripts/check-data.php](../scripts/check-data.php)（核心沉淀 #11-#13）|
| 常见陷阱 | [09-pitfalls.md](09-pitfalls.md) |

---

## 五、后台配置

### 5.1 模型创建

```sql
INSERT INTO `le_models` (`name`, `tablename`, `system`, `icon`, `index_tpl`, `cate_tpl`, `show_tpl`)
VALUES ('游戏', 'game', 0, 'iconfont', 'index.htm', 'game_list.htm', 'game_show.htm');
```

创建后自动生成 6 张表：主表、附件、数据、标签、标签数据、浏览。

### 5.2 添加自定义字段

后台 → 模型管理 → 对应模型 → 字段管理 → 添加

常用字段：

| 字段名 | 类型 | 用途 |
|--------|------|------|
| `size` | 单行文本 | 文件大小 |
| `update_time` | 单行文本 | 更新时间 |
| `download_url_android` | 单行文本 | 安卓下载 |
| `download_url_ios` | 单行文本 | iOS 下载 |
| `download_url_pc` | 单行文本 | PC 下载 |
| `platform` | 单行文本 | 平台 |

### 5.3 分类创建（关键！字段值直接影响列表/详情数据）

后台 → 分类管理，每个分类填写：

| 字段 | 值 | 错误后果（实战验证） |
|------|-----|---------------------|
| 名称 | 如"手机游戏" | - |
| 模型 mid | 顶级频道=对应模型；**单页分类=1**；子分类=父级 mid | mid=0 单页 → `table_arr[0]` 越界报错 |
| 类型 type | **顶级=1（频道）；叶子=0（普通）** | 叶子 type=1 → son_cids 为空 → list/list_top/global_cate 查不到内容 |
| 上级分类 upid | 子分类填父级 cid（**不是 0**） | upid=0 → 变成顶级分类 → 频道页列表为空 |
| 列表模板 cate_tpl | 只写文件名（如 `game_list.htm`）**不要加目录前缀** | 带 `game/` 前缀 → 模板找不到 |
| 详情模板 show_tpl | 同左 | 同左 |
| 别名 alias | `game`（用于 URL） | - |
| 排序 orderby | 原网站标签顺序（1 起） | 标签顺序不对 |

**分类层级规则（实战验证）：**
```
顶级分类（upid=0, type=1）：手机游戏(15) 手机软件(30) 电脑游戏(29) 电脑软件(38) 资讯攻略(39)
子分类（upid=父级, type=0）：休闲益智(5) BT游戏(11) 游戏辅助(31) ...
单页（upid=0, mid=1, type=1）：关于我们(46) ...
```

**修改分类后的缓存同步（必须做）：**
```bash
# 1. 注册/更新 alias 到 URL 映射表（分类 URL 解析用）
DELETE FROM le_only_alias;
INSERT INTO le_only_alias (alias, mid, cid, id)
  SELECT LOWER(alias), mid, cid, 0 FROM le_category WHERE alias != '';
# 2. 清空运行时缓存（cate_arr/son_cids 缓存）
DELETE FROM le_runtime;
# 3. 清模板编译缓存
rm -f runcache/lecms_view/*.php
```

### 5.4 数据表结构核对（模型创建后必须检查）

创建模型后自动生成的表**可能结构不一致**，逐表核对（对比已有模型）：

| 表 | 核对项 | 实战问题 |
|----|--------|---------|
| `cms_{model}_tag_data` | 主键应为 `(tagid, id)` 联合 | software 模型建成了单独 `id` 主键 → 同标签多内容冲突 |
| `cms_{model}_flag` | 字段应为 `flag, cid, id` | software 模型建成了 `id, aid, flagid` → 属性查询报错 |
| `cms_{model}_views` | **表必须存在** | software 模型漏建 → 排行榜查询报错 |

修复示例：
```sql
-- tag_data 主键修复
ALTER TABLE `le_cms_software_tag_data` MODIFY `id` int(10) unsigned NOT NULL DEFAULT '0',
  DROP PRIMARY KEY, ADD PRIMARY KEY (`tagid`, `id`);
```

### 5.5 属性表（flags）机制

LeCMS 的属性（推荐/热门/幻灯）查询**不读主表 flags 字段**，而是读 `cms_{model}_flag` 表：

```
内容发布时：主表 flags 字段（逗号分隔）+ cms_{model}_flag 表（flag, cid, id 关系）
block_list_flag / block_global_flags → 查 cms_{model}_flag 表
```

SQL 导入测试数据后需同步填充属性表：
```php
// 从主表 flags 同步到属性表
foreach (explode(',', $row['flags']) as $f) {
    INSERT INTO le_cms_{model}_flag (flag, cid, id) VALUES ($f, $cid, $id);
}
```

### 5.6 导航 + 主题

- 后台 → 导航管理 → 添加导航链接
- 后台 → 主题管理 → 启用 game 主题
- 后台 → SEO 设置 → 配置标题规则（`{$cfg[titles]}` 输出）

### 5.7 共数据双前端（移动端自动切换，实战验证）

手机端与 PC 端共数据共模型时，**不要新建模型/分类**，用 LeCMS 内置移动端机制：

```sql
-- le_kv 表 cfg JSON 中设置（或 后台 → 设置 → 基本设置 → 启用移动端模板）
"open_mobile_view": 1,
"mobile_view": "game955m"   -- 移动端主题目录名
```

切换逻辑（runtime_model.class.php 78-80 行）：
```php
if($key == 'cfg' && !empty($this->data['cfg']['open_mobile_view']) && is_mobile()==1){
    $this->data['cfg']['theme'] = $this->data['cfg']['mobile_view']; // 移动 UA → 移动主题
}
```

**实测验证：**
```bash
# PC UA → 应输出 PC 主题路径
curl -s -A "Mozilla/5.0 (Windows NT 10.0; Win64; x64)" URL | grep -o 'game955/[^"]*'
# Mobile UA → 应输出移动主题路径
curl -s -A "Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)" URL | grep -o 'game955m/[^"]*'
```

> 修改 le_kv 后清 `le_runtime` + `runcache/lecms_view/`。
> 移动端导航可在模板硬编码（原站手机端导航通常只有 首页/手游/资讯 3 项），无需 le_navigate_link。


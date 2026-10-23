# SEO 收录落地清单（自有域名部署后执行）

> 配套文档：《方案 A 预渲染交付报告.md》
> 配套脚本：《seo_push.py》（百度 / 谷歌主动推送）
> 目标：让好名网起名系统在百度、谷歌等搜索引擎**可被正常收录并获取排名**。

---

## 一、已完成 SEO 基建（盘点）

以下工作**已在系统中落地并验证**，无需重复：

| 模块 | 状态 | 要点 |
|---|---|---|
| `sitemap.xml` | ✅ 107 URL | 已 percent-encode 合规，含首页/文章/栏目/静态工具页/热门字动态样本 |
| 静态工具页 SEO | ✅ | H1、唯一 TDK、OG 四件套、JSON-LD |
| **动态参数页预渲染（方案 A）** | ✅ 本阶段完成 | `char/surname/mingzi` 对爬虫返回完整渲染 HTML（首屏 <0.5s，缓存命中毫秒级） |
| 内链机制 | ✅ | 动态配额（2–6 个/千字）+ 自链剔除 + 样板链接收敛，24 篇合计 85 个 contextual 链接 |
| 文章 / 栏目 description | ✅ | 24 篇文章 71–93 字、4 栏目 57 字，均达标 |
| `canonical` | ✅ 全站 15/15 | 规避参数重复收录 |
| 移动端响应式 | ✅ 62/62 | iPhone/Android 双视口零横向溢出，已修复 4 个工具页页头 bug |
| 自动内链 | ✅ | 字（引号单字）+ 词（45 条）双通道，安全 DOM 注入 |

---

## 二、上线前置（部署到自有域名）

> ⚠️ **当前 `*.app.workbuddy.host` 预览环境因平台「分享链接」鉴权（强制 `?sharecode=`）会拦截无参爬虫请求，方案 A 在预览环境无法被真实爬虫触发。** 必须把代码部署到自有域名后才能发挥收录价值。

1. **环境与代码**
   - 服务器：PHP ≥ 7.4 + Python 3.11（含 `flask` `playwright`）+ MariaDB。
   - 部署目录：`/var/www/lecms`（或等价目录）。

2. **三服务启动**（参考已固化的 `/workspace/.cloudstudio` 启动逻辑）
   ```
   service mariadb start
   php -S 127.0.0.1:8091 -t /var/www/lecms &        # 内部后端
   python3 /var/www/lecms/prerender/prender.py &     # 预渲染(8099)
   php -S 0.0.0.0:8090 -t /var/www/lecms /var/www/lecms/prerender/router.php  # 公网入口
   ```
   生产建议用 Nginx 反代 8090，静态资源（CSS/JS/图片）由 Nginx 直出。

3. **域名相关修改**
   - `sitemap` 生成脚本（`scripts/gen_sitemap.py`）里的站点 base URL → 改成你的域名。
   - `router.php` 第 79 行 `$scheme = 'https';`：无 SSL 站点改为动态判断。

4. **伪静态规则**
   - 确保 LECMS 的伪静态（`?show--cid-N-id-M.html`、`?cate--cid-N`）在服务器层正确 rewrite 到 `index.php`，否则文章/栏目 URL 404。

5. **上线前自检**
   - `curl -A "Baiduspider/2.0" https://你的域名/view/haoiming/tool/char.html?c=林` → 返回含「林字寓意」的完整 HTML。
   - `curl -A "Mozilla/5.0" https://你的域名/` → 返回 SPA 正常页。

---

## 三、向搜索引擎提交

### 百度（搜索资源平台 ziyuan.baidu.com）
1. 添加站点并**完成验证**（文件验证 / CNAME / HTML 标签）。
2. **提交 sitemap**：站点管理 → 数据提交 → sitemap → 提交 `https://你的域名/sitemap.xml`。
3. **主动推送**（见第四节）：新页面上线即推，收录速度远快于被动抓取。

### 谷歌（Search Console search.google.com/search-console）
1. 添加属性并验证。
2. **提交 sitemap**：Sitemaps → 输入 `sitemap.xml`。
3. **Indexing API**（见第四节）：实时通知 Google 收录/更新。

---

## 四、主动推送（seo_push.py）

脚本位置：`/workspace/analysis/seo_push.py`

**1. 配置**（三选一：改脚本 CONFIG / 环境变量 / 命令行参数）
```bash
export BAIDU_SITE="https://www.你的域名.com"
export BAIDU_TOKEN="百度平台分配的token"
export GOOGLE_CREDENTIALS="/path/to/service-account.json"
```

**2. 推送全部 URL（来自 sitemap）**
```bash
python3 seo_push.py --sitemap https://你的域名/sitemap.xml
```

**3. 只推送新增/更新的 URL**
```bash
python3 seo_push.py --urls new_urls.txt     # 每行一个 URL
```

**4. 只推某个引擎**
```bash
python3 seo_push.py --sitemap ... --only baidu
python3 seo_push.py --sitemap ... --only google
```

**5. 上线/内容更新时自动跑**（建议接入部署钩子或 cron）
- 新文章发布 → 把该 URL 加入推送列表立即推。
- 每日定时全量推一次（百度每日配额有限，按需）。

---

## 五、持续运营（收录后）

| 动作 | 频率 | 工具 |
|---|---|---|
| 查索引覆盖率 | 每周 | 百度/谷歌后台「覆盖率」报告 |
| 主动推送新页 | 实时/每日 | `seo_push.py` |
| 内容更新（文章/热门字扩充） | 持续 | 后台编辑 |
| 外链 / 友链建设 | 持续 | 行业站互链 |
| 关键词竞争复盘 | 每月 | 5118 / 百度指数 |

---

## 六、风险与注意

1. **预览环境 ≠ 收录环境**：当前 `*.app.workbuddy.host` 的 sharecode 限制挡住真实爬虫，不要据此判断"收录失败"；以自有域名部署后的抓取为准。
2. **动态参数页权重**：`?c=林` 这类参数 URL 天生收录权重偏低，sitemap 已收录热门字样本；长尾生僻字靠内链 + 主动推送弥补。
3. **沙箱重启恢复**：`.cloudstudio` 已固化四级启动顺序（DB→后端→预渲染→公网），重启后方案 A 自动恢复；预渲染 python 已改用完整 pyenv 路径，避免缺依赖。
4. **内容质量优先**：技术只是入口，最终排名靠内容原创度与用户停留；文章/字典条目保持可读、有信息量。

---

## 七、当前可立即执行项（无需自有域名）

- ✅ 已在系统侧完成全部技术优化（sitemap / 预渲染 / 内链 / TDK / 移动端）。
- ✅ 已交付 `seo_push.py`，部署域名后 5 分钟可跑通推送。
- ⏳ 待用户：准备自有域名 + 服务器，按第二节部署。
- ⏳ 待用户：在百度/谷歌平台验证站点、填入 token、首次推送。

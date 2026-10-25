# Search Console 数据接入与收录复查方案

> 制定日期：2026-10-25（SEO 第二十三轮修复发布后）
> 站点：https://ad6acb5cb3aeb83e6.app.workbuddy.link

---

## 一、当前状态（接入前）

| 检查项 | 状态 |
|---|---|
| Google Search Console 资源 | **未创建 / 未验证**（无 `google-site-verification` meta、无验证文件） |
| 百度站长平台 | **未验证**（无 `baidu-site-verification` meta） |
| robots.txt | ✅ 已提交 sitemap.xml（886 URL，全部规范 URL） |
| Google API 凭据 | 未配置（需用户在 GCP 创建，见第四节） |
| site: 收录查询 | 沙箱网络不可达（Bing 302 / Google 连接失败），**真实收录数只能来自 GSC 面板或 API** |

## 二、当前基线快照（2026-10-25，供 2-4 周后对比）

修复发布时的站内可验证指标（非搜索引擎侧数据）：

| 指标 | 基线值 | 说明 |
|---|---|---|
| sitemap URL 总数 | 886 | 原 1477（已撤下 605 个非规范矩阵 URL，补入 faq/lib/12 生肖页） |
| 矩阵页 URL 提交 | 0 | canonical 权重集中设计：605 个 `surname.html?c=X&wx=Y` 不再单独提交 |
| 单字页 TDK 唯一性 | 26/26 | title 与 description 全部唯一 |
| 矩阵页 TDK 唯一性 | 25/25 | 均含「五行属X」差异化，canonical 全部集中到姓氏主页 |
| noindex 生效面 | 7/7 用例 | 无效字符/无效参数页正确 noindex，防软 404 |
| sitemap 抽样可达性 | 25/25 HTTP 200 | 无死链 |
| 预渲染内容页 | 5 类全过 | faq（FAQPage 20 问）/百家姓（121 姓氏）/生肖 12/典籍 5/文库 |
| Lighthouse | Performance 93 / SEO 100 | 渲染阻塞资源已消除（FCP 0.7s / TBT 70ms） |
| 静态资源 | gzip + ETag + 分级缓存 | main.7.js 195KB→61KB（省 69%） |

## 三、验证接入步骤（约 10 分钟）

### Google Search Console（优先）

1. 登录 <https://search.google.com/search-console> → 添加资源 → 选「网址前缀」→ 输入 `https://ad6acb5cb3aeb83e6.app.workbuddy.link`
2. 验证方式选 **「HTML 标记」**，复制形如 `google-site-verification=XXXXX` 的验证码
3. 将验证码注入站点（二选一）：

   **方式 A：meta 标签（推荐，改一处全站生效）**
   ```bash
   # 把 <meta name="google-site-verification" content="XXX"> 插入 inc-header.htm 的 <head> 顶部
   cd /var/www/lecms/view/haoiming
   sed -i '0,/<meta name="viewport"/s//<meta name="google-site-verification" content="XXX" \/>\n\t<meta name="viewport"/' inc-header.htm
   # 同步 theme 副本
   rsync -a --delete /var/www/lecms/view/haoiming/ /workspace/lecms-integration/theme/haoiming/
   ```

   **方式 B：HTML 文件验证**（把 GSC 给的 `googleXXXX.html` 放到 `/var/www/lecms/` 根目录即可）

4. 回 GSC 点「验证」→ 通过后进入「Sitemap」提交 `sitemap.xml`
5. 用「网址检查」工具对以下代表页手动请求编入索引：
   - `/`（首页）
   - `/view/haoiming/tool/baijiaxing.html`（百家姓枢纽页）
   - `/view/haoiming/tool/faq.html`（FAQPage 结构化数据）
   - `/view/haoiming/tool/char.html?c=%E6%9E%97`（单字页代表）
   - `/view/haoiming/tool/surname.html?c=%E7%8E%8B`（矩阵页代表）

### 百度站长平台（国内流量主力，同步做）

1. <https://ziyuan.baidu.com> → 添加网站 → 选「HTML 标签验证」
2. 复制 `baidu-site-verification` 验证码，按上面同样的方式注入 `inc-header.htm`
3. 提交 sitemap：`https://ad6acb5cb3aeb83e6.app.workbuddy.link/sitemap.xml`
4. 重点使用「普通收录-快速提交」推送首页与百家姓/char 代表页（百度对 JS 渲染页收录慢，预渲染管线已解决抓取层）

## 四、API 数据接入方案（自动化复查用）

若要让复查脚本直接拉取 GSC 数据（收录量、点击、展示、排名），需：

1. 用户在 **GCP 控制台**创建项目 → 启用 **Search Console API**
2. 创建 **Service Account** → 下载 JSON 密钥 → 放到 `scripts/gsc-credentials.json`（⚠️ 该文件已加入 `.gitignore`，禁止提交）
3. 在 GSC → 设置 → 用户和权限中，把 service account 邮箱加为**完整权限**用户
4. 复查脚本（后续按需开发）调用 `searchanalytics.query`：
   - 维度：`page` + `query`
   - 指标：`clicks / impressions / ctr / position`
   - 时间窗：发布前 7 天 vs 发布后 7 天对比

## 五、监控指标定义（2-4 周后复查用）

| 指标 | 数据来源 | 修复前预期问题 | 达标参考 |
|---|---|---|---|
| 已编入索引的页面数 | GSC 页面报告 | 内容页（faq/百家姓/生肖）0 收录（爬虫只见空壳） | 修复后 2-4 周 > 50 页 |
| 矩阵页展示/点击 | GSC 效果报告筛选 URL 含 `surname.html` | TDK 605 页雷同、sitemap 混入非规范 URL | 展示量逐步上升、无重复 TDK 投诉 |
| 软 404 / 已排除页 | GSC 页面报告 | 无效参数页返回 200 被计为软 404 | noindex 生效后软 404 清零 |
| 平均 CTR | GSC 效果报告 | TDK 无差异化导致 CTR 低 | 差异化 TDK 上线后 CTR 提升 |
| Core Web Vitals | GSC 网页指标报告 | 渲染阻塞 3.68s | 全部「良好」 |

## 六、复查清单（建议 2026-11-15 与 2026-11-29 两次）

- [ ] GSC 已编入索引页面数（vs 基线第二节）
- [ ] 矩阵页（surname.html?c=X）展示量与平均排名
- [ ] 软 404 / 「已抓取 - 尚未编入索引」清单（针对性补推送）
- [ ] FAQPage 富媒体结果是否展示（GSC 增强报告 / 百度站长「数据标注」）
- [ ] Core Web Vitals 全部指标
- [ ] 如收录不及预期：检查预渲染缓存命中率（`X-Prerendered: 1` 响应头）与爬虫抓取日志（8099 prender.log）
- [ ] 百度侧：观察百度站长平台的「抓取异常」与「死链提交」是否干净

---

**备注**：本域名是 workbuddy 隧道演示域名，若后续切换到正式域名，需在 GSC/百度重新验证资源（旧资源保留 90 天对比数据），并同步更新 sitemap.xml 与 canonical（`scripts/gen_sitemap.py` 内域名配置）。

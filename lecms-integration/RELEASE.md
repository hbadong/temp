# 好名网发布流程 Checklist

> 版本：2026-10-25 第二十三轮（SEO 优化 + M2 渲染优化后）
> 自动化脚本：`scripts/release.sh`（清缓存 / 版本审计 / 三连回归 / 副本同步）

---

## 双仓库架构（先理解再发布）

```
/var/www/lecms/                     ← 编辑源 + 线上运行（PHP 内置服务器 8090 公网 / 8091 后端）
  view/haoiming/                    ← 主题模板（所有页面改这里）
  prerender/router.php + prender.py ← 预渲染管线（8099 服务，爬虫 UA 命中）
  prerender/cache/                  ← 预渲染产物缓存（TTL 30 天，md5(URL) 命名）
        │
        │  rsync -a --delete（发布时单向同步）
        ▼
/workspace/lecms-integration/       ← 集成交付仓库（git 根 /workspace，分支 haoiming）
  theme/haoiming/                   ← 主题快照副本
  server/prerender/                 ← router.php / prender.py / robots.txt 归档
  scripts/                          ← 全部管线与验证脚本
  seo-optimization-20261025.md      ← 本轮 SEO 修复报告
  search-console-setup.md           ← Search Console 接入与复查方案
```

## 发布 Checklist

### A. 改动前

- [ ] 确认改动范围：主题模板（`view/haoiming/**`）/ 预渲染管线（`prerender/**`）/ 数据脚本（`scripts/**`）
- [ ] 主题类改动：先查是否影响预渲染选择器（`DETAIL_SELECTORS` in prender.py）与 canonical/TDK 逻辑（router.php `rewriteTdk`）

### B. 改动后（每轮必做）

- [ ] **静态资源版本号**：改了 `js/main.7.js`、`css/style.7.css` 等内容 → 升级页面引用 `?v=YYYYMMDDx`（`release.sh --audit` 会查漏）
- [ ] **清 prerender 缓存**（⚠️ 最高频事故点）：
  - 主题结构/TDK/JSON-LD 有变更 → **必须** `rm -f prerender/cache/*.html`
  - 仅数据内容微调且版本号已升 → 可不清（HTML 外壳 5 分钟缓存会自愈）
  - 命令：`scripts/release.sh --cache`
- [ ] **三连回归**（不过不发布）：`scripts/release.sh --verify`
  1. `verify_seo.py` — TDK 唯一性 / noindex / canonical / sitemap 可达 / 五类内容页预渲染
  2. `verify_m2.py` — JS 零错误 / 异步 CSS 生效 / 截图产物
  3. `check_mobile.py` — 移动端零溢出
- [ ] E2E 功能回归（改引擎/表单时必跑）：`scripts/test_final.py`

### C. 发布

- [ ] `scripts/release.sh`（无参数 = 全流程：清缓存 → 版本审计 → 三连回归 → rsync 同步 → 打印 git 提示）
- [ ] 人工执行 git（脚本不自动提交，防误提交）：
  ```bash
  cd /workspace
  git add lecms-integration/ && git status        # 人工过目变更清单
  git commit -m '<轮次描述>；验证：三连回归 + E2E 全过'
  git -c http.version=HTTP/1.1 -c http.lowSpeedLimit=0 -c http.lowSpeedTime=600 \
      push https://<PAT>@github.com/hbadong/temp.git haoiming
  ```
- [ ] 推送后确认远程指针：`git ls-remote <remote> haoiming` 与本地 HEAD 一致

### D. 发布后 24 小时

- [ ] 线上抽查（爬虫 UA）：TDK / canonical / `X-Prerendered: 1` / gzip 头
- [ ] Lighthouse 首页（Performance ≥ 90 / SEO = 100 为基线）
- [ ] 预渲染服务健康：`curl http://127.0.0.1:8099/healthz`
- [ ] JS 源码防护：`curl -o /dev/null -w '%{http_code}' https://域名/view/haoiming/js/main.7.js` 应为 **403**（未压缩源文件禁止下载，详见 js-security-assessment.md）

### E. 生产特有项（沙箱无）

- [ ] nginx conf 的「未压缩 JS 源文件 deny」规则生效（`deploy_check.sh` 已含该项检查）
- [ ] web 根不含 `.map` source map 文件
- [ ] `.git/` 不同步到生产

## 各脚本速查

| 脚本 | 用途 | 频率 |
|---|---|---|
| `release.sh` | 发布主流程（见上） | 每轮 |
| `gen_sitemap.py` | 重新生成 sitemap.xml + 净化 robots.txt | URL 结构变动时 |
| `verify_seo.py` | SEO 全量回归 | 每轮 |
| `verify_m2.py` | 渲染/JS 错误回归 + 截图 | 每轮 |
| `check_mobile.py` | 移动端溢出 | 每轮 |
| `extract_critical.py` | 从 style.7.css 再生 critical.css（改样式后） | 样式大改时 |
| `patch_header.py` | inc-header.htm 的 M2 改造（新主题接入时一次性） | 新主题 |
| `test_final.py` | E2E 五项功能回归 | 改引擎/表单时 |
| `shot_seo*.py` | SEO 证据截图 | 需要时 |

## 历史事故备忘（为什么清单这么定）

1. **sitemap 外站污染（C1）**：robots.txt 曾声明含 7131 个竞品 URL 的 sitemap-dict.xml → 防回归：`dict_gen.py` 产物写入 `archive/`、`gen_sitemap.py` 重写 robots 只留本站 sitemap
2. **矩阵页 TDK 雷同 605 页（M4）**：router.php 曾剥离 `wx` 参数致 JS 差异化分支失效 → 防回归：服务端 `rewriteTdk` 按参数生成 + sitemap 只提交规范 URL
3. **预渲染缓存陈旧**：主题改了但缓存 30 天 TTL 内不刷新，爬虫持续拿到旧 HTML → 防回归：本清单 B 节强制清缓存 + `release.sh --cache` 一键化
4. **内联 CSS 相对路径失效（M2）**：critical CSS 内联后 `url(img/...)` 解析基准变为文档目录 → 防回归：`style.7.css` 已全部改为绝对路径，`extract_critical.py` 重生成后需人工 grep `url(img/` 确认

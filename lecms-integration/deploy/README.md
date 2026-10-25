# 好名网 · 服务器部署指南与文件目录结构

> 适用版本：SEO 第二十四轮后（2026-10-25）
> 目标环境：Ubuntu 22.04 / Nginx 1.24+ / PHP 8.3-FPM / MySQL 8 / Python 3.11 + Playwright
> 本目录 `deploy/` 即「生产部署包」，随代码库一起版本化管理

---

## 一、系统架构总览

```
                    ┌─────────────────────────────────────────────┐
   搜索引擎爬虫 ───▶ │  Nginx (80/443)                              │
   （百度/Google）   │   ├─ UA 识别 map $is_bot                     │
                    │   ├─ 普通用户 → 静态文件 / LECMS PHP 动态页    │
                    │   ├─ 爬虫 + 工具页 → rewrite → gateway.php ──┼──▶ prender.py
                    │   ├─ gzip / 三级缓存 / 安全头 / 敏感目录保护   │   (Playwright
                    │   └─ /admin 后台 IP 白名单                    │    Chromium
                    └─────────────────────────────────────────────┘    渲染服务)
                              │                                          │
                              ▼                                          │
                    ┌─────────────────┐      ┌──────────────────┐      │
                    │  PHP-FPM 8.3    │────▶ │  MySQL 8 (lecms) │      │
                    │  LECMS 框架      │      │  文章/分类/配置   │      │
                    └─────────────────┘      └──────────────────┘      │
                              │                                          │
                              ▼                                          │
                    ┌─────────────────────────────────────────┐        │
                    │  静态资产层                             │        │
                    │  view/haoiming/（主题：页面+JS+CSS）     │        │
                    │  chengyu/ cidian/ zidian/（词典静态页）  │◀───────┘ 渲染回源
                    │  upload/（文章图片）                     │
                    └─────────────────────────────────────────┘
```

**核心设计**：
- **工具页是静态 HTML + JS 渲染**：`view/haoiming/tool/*.html` 是外壳，内容由 `main.7.js` 按 URL 参数渲染——普通用户直接服务外壳；爬虫由预渲染管线代劳，拿到与用户端 100% 一致的完整 HTML
- **预渲染网关 `prerender/gateway.php`**：Nginx 只把「爬虫 UA + 12 类动态工具页」分流给它；它调 Playwright 服务渲染后按 URL 缓存（30 天），秒回二次抓取，并重写 canonical 与差异化 TDK
- **静态资源三级缓存**：JS/CSS 30 天 immutable（全部带 `?v=` 或 `.7.` 版本号）、HTML 外壳 5 分钟、sitemap/robots 1 小时

---

## 二、服务器要求

| 项目 | 最低 | 推荐 |
|---|---|---|
| CPU / 内存 | 2C / 4G | 4C / 8G（预渲染 Chromium 吃内存） |
| 磁盘 | 20G | 50G SSD（词典静态页 46MB + 数据库 + 日志） |
| 操作系统 | Ubuntu 22.04 LTS | Ubuntu 22.04 LTS |
| 带宽 | 5Mbps | 10Mbps+ |
| 域名 | 公网可达 | **已备案**（国内服务器必须，未备案用境外节点） |

> ⚠️ 预渲染服务需要 `/dev/shm` ≥ 512MB 或禁用 Chromium shm（`chromium.launch(args=['--disable-dev-shm-usage'])`，当前代码已在 Docker 环境验证可跑；若服务器 shm 小需加此参数）

---

## 三、完整文件目录结构

### 3.1 网站根目录（部署后 `/var/www/lecms/`）

```
/var/www/lecms/
├── index.php                     # LECMS 唯一入口（所有动态请求经此）
├── admin/                        # 管理后台（Nginx 按 IP 白名单保护）
├── install/                      # 安装器（部署完成后应删除）
├── lecms/                        # LECMS 框架内核（禁止外网访问）
│   ├── config/config.inc.php     # ⚙️ 数据库连接/加密KEY/站点配置（改这里）
│   └── ...                       # 模型/控件/函数库
├── view/                         # 模板主题目录
│   ├── default/                  # LECMS 默认主题（未启用）
│   └── haoiming/                 # ⭐ 好名网主题（本系统核心，见 3.2）
├── chengyu/  cidian/  zidian/    # 词典静态页（1367+1405+4362 个 HTML，46MB）
├── upload/                       # 文章图片等上传资产（runcache 可重建）
├── static/                       # LECMS 自带静态资源
├── data/                         # 运行数据（stats.json 统计）
├── runcache/                     # 模板编译缓存（可随时清空）
├── log/                          # 运行日志（禁止外网访问）
├── favicon.ico
├── robots.txt                    # ⭐ SEO：爬虫协议（只声明本站 sitemap.xml）
├── sitemap.xml                   # ⭐ SEO：886 个规范 URL（gen_sitemap.py 生成）
├── thumb.php  stats-api.php      # 缩图/统计接口（保留）
└── prerender/                    # ⭐ SEO 预渲染管线（见 3.3）
    ├── gateway.php               # 预渲染网关（Nginx 分流目标）
    ├── prender.py                # Playwright 渲染服务（systemd 守护）
    ├── cache/                    # 预渲染产物缓存（主题变更后清空！）
    └── prender.log               # 服务日志
```

### 3.2 主题目录 `view/haoiming/`（改页面就是改这里）

```
view/haoiming/
├── inc-header.htm                # ⭐ 全站公共头部：meta/OG/关键CSS内联/JS defer
├── inc-footer.htm                # 全站公共页脚（JS 填充）
├── index.htm                     # 首页（水墨横幅 + 三Tab起名表单 + 热度索引）
├── 404.htm  article_*.htm  ...   # LECMS 文章/列表/详情模板
├── css/
│   ├── style.7.css               # ⭐ 主样式表（86KB，图片路径已绝对化）
│   └── style.css                 # 旧版（保留兼容）
├── js/
│   ├── data.7.js                 # ⭐ 字库 704 字/121 姓氏/成语（134KB）
│   ├── engine.7.js               # ⭐ 起名引擎：八字/五格/音律/热度（111KB）
│   ├── main.7.js                 # ⭐ 全站交互 + 21 个页面 init 函数（198KB）
│   ├── shuowen.7.js              # 说文解字数据（147KB）
│   └── dict.7.js                 # 词典数据
├── img/                          # banner SVG 等主题图片
└── tool/                         # ⭐ 26 个静态工具页（JS 渲染内容）
    ├── char.html                 #   单字页   ?c=X
    ├── surname.html              #   姓氏页   ?c=X[&wx=五行]
    ├── mingzi.html               #   名字页   ?name=X
    ├── faq.html  shengxiao.html  #   FAQ 20问 / 生肖专题（无参数内容页）
    ├── poems.html shijing.html   #   典籍名句库（5 页）
    │   chuci.html tangshi.html songci.html
    ├── baijiaxing.html           #   百家姓枢纽（121 姓氏内链）
    ├── lib.html                  #   好名文库
    ├── kxzd.html                 #   康熙字典（热度索引 80 字）
    ├── search.html               #   统一搜索（热度索引 40 字）
    ├── naming.html bazi.html score.html match.html ...
    └── ...                       #   其余计算类工具（起名/测名/配对/排盘等）
```

### 3.3 交付仓库 `lecms-integration/`（git 管理，源码与文档）

```
/workspace/lecms-integration/
├── RELEASE.md                    # ⭐ 发布流程 checklist（清缓存铁律）
├── seo-optimization-20261025.md  # SEO 修复报告（9 项问题明细）
├── search-console-setup.md       # Search Console 接入与复查方案
├── theme/haoiming/               # 主题快照副本（与 view/haoiming/ 双向同步）
├── server/                       # 服务端脚本归档（router.php/prender.py/robots.txt）
├── scripts/                      # ⭐ 全部管线脚本（见 3.4）
├── seo_fixes/                    # 验证截图与 Lighthouse 报告
└── deploy/                       # ⭐ 本部署包（nginx/网关/systemd/数据库/验收）
    ├── README.md                 # 本文档
    ├── nginx/haoiming.conf       # 生产站点配置（改 5 处即可上线）
    ├── prerender/gateway.php     # FPM 版预渲染网关
    ├── systemd/prender.service   # 预渲染服务守护单元
    ├── mysql/lecms.sql           # 数据库全量导出（20 表）
    └── scripts/deploy_check.sh   # 上线验收（7 组 30+ 检查项）
```

### 3.4 脚本目录 `scripts/` 速查

| 脚本 | 用途 |
|---|---|
| `gen_sitemap.py` | 重新生成 sitemap.xml + 净化 robots.txt |
| `dict_gen.py` | 三站静态词典页生成器 |
| `verify_seo.py` | SEO 全量回归（TDK/noindex/canonical/sitemap/预渲染） |
| `verify_m2.py` | 渲染回归（JS 错误/异步 CSS/截图） |
| `check_mobile.py` | 移动端溢出检查 |
| `extract_critical.py` | 从 style.7.css 再生首屏关键 CSS |
| `patch_header.py` | inc-header.htm 的 M2 改造（新主题接入用） |
| `release.sh` | 发布主流程（清缓存→审计→三连回归→同步） |
| `test_final.py` | E2E 五项功能回归 |
| `../deploy/scripts/deploy_check.sh` | 上线验收 |

---

## 四、部署步骤（约 60-90 分钟）

### 步骤 1：基础环境

```bash
# 系统依赖
sudo apt update && sudo apt install -y nginx mysql-server \
  php8.3-fpm php8.3-mysql php8.3-gd php8.3-curl php8.3-mbstring php8.3-xml php8.3-bcmath

# Python + Playwright（预渲染服务）
curl -sSf https://pyenv.run | bash   # 或用系统 python3.11
pyenv install 3.11.1 && pyenv global 3.11.1
pip install flask playwright
playwright install chromium        # 下载无头浏览器（约 150MB）
playwright install-deps chromium   # 安装系统依赖库
```

### 步骤 2：数据库

```bash
sudo mysql -e "CREATE DATABASE lecms DEFAULT CHARSET utf8mb4;"
sudo mysql -e "CREATE USER 'lecms'@'localhost' IDENTIFIED BY '改成强密码';"
sudo mysql -e "GRANT ALL PRIVILEGES ON lecms.* TO 'lecms'@'localhost'; FLUSH PRIVILEGES;"
mysql -u lecms -p lecms < /workspace/lecms-integration/deploy/mysql/lecms.sql
```

### 步骤 3：代码部署

```bash
# 网站代码（从本仓库同步主题 + 词典 + 框架）
sudo mkdir -p /var/www/lecms
# 方式 A：整包拷贝当前沙箱的 /var/www/lecms（排除缓存/日志/.git）
sudo rsync -a --exclude='.git' --exclude='runcache/*' --exclude='log/*' \
  --exclude='prerender/cache/*' /var/www/lecms/ /var/www/lecms/    # 源路径按实际替换
# 方式 B：从 git 仓库取（推荐，历史干净）
cd /var/www && sudo git clone -b haoiming https://github.com/hbadong/temp.git lecms-tmp
# 注意：框架主体（index.php/lecms/admin/install）以原有 LECMS 包为准，
#       主题与预Renderer管线以 lecms-integration 为准，合并部署。

# 部署预渲染网关（FPM 版）
sudo cp /workspace/lecms-integration/deploy/prerender/gateway.php /var/www/lecms/prerender/
sudo mkdir -p /var/www/lecms/prerender/cache

# 权限（Nginx/PHP-FPM 以 www-data 运行）
sudo chown -R www-data:www-data /var/www/lecms
sudo chmod -R 755 /var/www/lecms
sudo chmod -R 775 /var/www/lecms/upload /var/www/lecms/runcache /var/www/lecms/log
```

### 步骤 4：应用配置

编辑 `/var/www/lecms/lecms/config/config.inc.php`：

```php
'db' => array(
    'type' => 'mysqli',
    'master' => array(
        'host' => '127.0.0.1',
        'port' => '3306',
        'user' => 'lecms',
        'password' => '改成强密码',      // 与步骤 2 一致
        'name' => 'lecms',
    ),
),
'auth_key' => '随机32位字符串',           // 加密 KEY，改一次终身不变
```

编辑 `deploy/prerender/gateway.php` 顶部配置区：确认 `$RENDER_SVC`（8099）、`$SITE_ORIGIN`、`$CACHE_DIR`、`$PUBLIC_SCHEME`（有证书时 https）。

### 步骤 5：Nginx

```bash
sudo cp /workspace/lecms-integration/deploy/nginx/haoiming.conf /etc/nginx/sites-available/haoiming
sudo ln -s /etc/nginx/sites-available/haoiming /etc/nginx/sites-enabled/
# 必须修改的 5 处：server_name / root（如不同）/ php-fpm socket 版本 / admin 白名单 IP / SSL 段
sudo nginx -t && sudo systemctl reload nginx
```

### 步骤 6：预渲染服务守护

```bash
sudo cp /workspace/lecms-integration/deploy/systemd/prender.service /etc/systemd/system/
# 按服务器实际改 ExecStart 的 Python 路径（which python3）
sudo systemctl daemon-reload && sudo systemctl enable --now prender
systemctl status prender           # 确认 active (running)
curl http://127.0.0.1:8099/healthz # 应返回 ok
```

### 步骤 7：域名与证书

```bash
# 正式域名 + 已备案后：
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
# 证书自动续期；随后在 nginx conf 取消 HTTPS 段注释、80 段改 return 301
```

---

## 五、上线验收

```bash
/workspace/lecms-integration/deploy/scripts/deploy_check.sh https://your-domain.com
```

覆盖 7 组 30+ 项：基础可达 / 安全头 / gzip+缓存 / robots 净化 / 预渲染管线（TDK/canonical/noindex/矩阵页）/ 敏感目录防护 / sitemap 规模。全绿即可投入使用。

---

## 六、日常维护

| 事项 | 频率 | 操作 |
|---|---|---|
| 数据库备份 | 每天 | `mysqldump lecms \| gzip > /backup/lecms-$(date +%F).sql.gz` |
| 全量备份 | 每周 | 代码 + upload/ + 数据库（三个部分都要） |
| 预渲染缓存清理 | 主题变更后 | `rm -f /var/www/lecms/prerender/cache/*.html`（或 `release.sh --cache`） |
| 日志轮转 | logrotate 默认 | nginx + prender.log |
| 发布流程 | 每轮改动 | 见 `lecms-integration/RELEASE.md` |
| 收录复查 | 每 2 周 | 见 `lecms-integration/search-console-setup.md` |

### 备份白名单（最小集）

```
/var/www/lecms/lecms/config/config.inc.php   # 应用配置（含数据库密码，保密）
/var/www/lecms/upload/                        # 上传资产
/var/www/lecms/view/haoiming/                 # 主题（git 也有）
/var/www/lecms/prerender/                     # 网关+渲染服务（cache/ 除外）
/var/www/lecms/robots.txt sitemap.xml         # SEO 文件
数据库 lecms                                   # 文章内容
```

### 可随时重建（不用备份）

`runcache/`（模板编译缓存）、`log/`、`prerender/cache/`（30 天自愈）、`data/stats.json`。

---

## 七、从当前沙箱迁移到自有服务器的数据流向

```
沙箱 /var/www/lecms（本机）                      你的服务器
├── lecms/ + admin/ + install/（框架）  ──rsync──▶ /var/www/lecms/lecms/ 等
├── view/haoiming/（主题）              ──rsync──▶ /var/www/lecms/view/haoiming/
├── chengyu/ cidian/ zidian/（词典）     ──rsync──▶ /var/www/lecms/
├── prerender/gateway.php + prender.py  ──cp────▶ /var/www/lecms/prerender/
├── robots.txt sitemap.xml              ──cp────▶ /var/www/lecms/
└── MySQL lecms 库（20 表）             ──dump──▶ deploy/mysql/lecms.sql → 服务器导入
```

域名相关：sitemap.xml 与 canonical 中的域名由 `scripts/gen_sitemap.py` 配置生成，换域名后重跑一次即可（脚本内有域名配置项）。

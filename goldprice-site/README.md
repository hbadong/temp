# 金价查询网站系统 — 逆向分析与功能重建

> 目标站点：`http://www.huangjinjiage.cn/`（自有站点，已获授权分析）
> 交付内容：不依赖原站素材、用自研代码重建的同类金价查询系统
> 技术栈：Node.js 原生 HTTP 服务（零运行时依赖）+ 原生前端 + ECharts

---

## 一、功能解释（原站是怎么实现的）

### 1.1 整体架构：静态页面 + 前端 JSONP 拉行情 + 定时刷新

原站是一个 **GBK 编码的传统静态站**（非 SPA）：每个栏目是独立 HTML 文件，页面骨架由服务端直出，**价格数字全部由浏览器端脚本事后填充**。

核心链路：

```
浏览器打开页面
   └─ <script src="http://res.huangjinjiage.com.cn/jin.js?t=时间戳">
         └─ 返回 JSONP 形式的 JS 变量（新浪财经行情的中继副本）
              └─ 页面内联 loadPrice() 解析变量 → 写入表格单元格
                   └─ setInterval 每 60 秒重新加载一次
```

关键证据：首页内联脚本中直接引用了 `res.huangjinjiage.com.cn/jin.js`，并且该文件内容与新浪 `hq.sinajs.cn` 的 `hq_str_*` 变量格式一致。说明原站**并不自己采集行情**，而是做了一层"行情中继 + 派生计算"。

### 1.2 数据源：新浪财经行情接口

原站使用的底层标的一次性请求，等价于：

```
https://hq.sinajs.cn/list=hf_XAU,hf_XPT,hf_XAG,hf_XPD,gds_AUTD,gds_AGTD,fx_susdcny,fx_susdhkd
```

| 代码 | 含义 | 单位 |
|---|---|---|
| `hf_XAU` | 国际现货黄金 | 美元/盎司 |
| `hf_XPT` | 国际现货铂金 | 美元/盎司 |
| `hf_XAG` | 国际现货白银 | 美元/盎司 |
| `hf_XPD` | 国际现货钯金 | 美元/盎司 |
| `gds_AUTD` | 上海黄金交易所 黄金 T+D | 元/克 |
| `gds_AGTD` | 上海黄金交易所 白银 T+D | 元/千克 |
| `fx_susdcny` | 美元兑人民币 | — |
| `fx_susdhkd` | 美元兑港元 | — |

> **重要约束**：该接口校验 `Referer`，必须带 `https://finance.sina.com.cn/` 才能返回数据；返回体为 GBK 编码，数值字段是纯 ASCII（可直接按字节提取），中文名称需转码。

### 1.3 派生价格：原站的"换算公式"

原站页面上大部分价格不是接口直接给出的，而是由基础行情**实时换算**得到。分析还原出的公式：

| 展示项 | 计算方式 | 证据 |
|---|---|---|
| 国内金价 | `gds_AUTD` 最新价 | 首页表 2 `jiage4` |
| 国内银价 | `gds_AGTD / 1000`（元/千克 → 元/克） | 首页表 2 `jiage5` |
| 投资金条 | 国内金价 + 约 16 元/克（加工费） | `jiage7` 偏移量 |
| 黄金回收 | 国内金价 − 约 15 元/克（回收折扣） | `jiage6` 偏移量 |
| 铂金/钯金回收 | `国际价 / 31.1035 × 汇率 × 回收系数` | 回收页 `hspj1.js` |
| 18K 金回收 | 国内金价 × 0.7123（含金量 75% × 折价） | 回收页观测值校准 |
| 品牌金价 | 国内基础金价 + 品牌工费溢价（各品牌不同） | 品牌页 12 行 |
| 香港金价 | `国际金价 / 31.1035 × USD/HKD`（港元/克）；× 37.429（港元/司马两） | 香港页 |

单位换算常量：**1 金衡盎司 = 31.1035 克**，**1 司马两 = 37.429 克**。

### 1.4 页面结构与栏目

| 页面 | 核心内容 | 数据填充方式 |
|---|---|---|
| `index.html` 首页 | 国际 4 品种行情表、国内 7 行行情表、12 家品牌金价表、行情快讯时间轴、24 小时走势图、侧边栏 | 表行 `id="jiage1"`…`id="bajin"`，脚本按 td 索引写入 |
| `guojijinjia.html` 国际金价 | 伦敦金/铂金/白银/钯金 4 行行情 + 美元盎司↔人民币克换算器 + 24h 走势图 | 同上 + 汇率换算 |
| `guoneijinjia.html` 国内金价 | 黄金/白银/铂金/钯金 6 列表（今日价/销售价/最高/最低/日期）+ 相关报价 | `fillDomRow()` 六列填充 |
| `huishou.html` 黄金回收 | 7 类回收价 + ECharts 七日回收走势 + 防坑科普 | `hspj1.js` + `echarts.setOption()` |
| `pinpaijinjia.html` 品牌金价 | 12 家品牌（周大福、周生生、六福…）黄金/铂金/金条价 | 按品牌区块渲染 |
| `zoushitu.html` 走势图 | 沪金/沪银分时+日线（GIF），伦敦金/铂/钯/银分时+日线 | `<img>` 引用新浪图表 |
| `xianggang.html` 香港金价 | 港元/克、港元/司马两、汇率对照 | 派生计算 |

### 1.5 视觉与交互约定

- 顶部深蓝渐变导航条，左侧 Logo，横向栏目菜单，当前栏目高亮。
- 内容区左主右辅（约 3:1），右侧为"热门查询 / 友情链接 / 实时报价"卡片。
- 行情表：品种名居中、最新价加粗、涨跌与幅度同色；**涨红跌绿**（中国习惯）。
- 数据状态提示："数据正常 · 更新于 HH:MM:SS · 每 60 秒自动刷新"。
- 移动端断点：导航折叠、主副栏堆叠、图表单列。

---

## 二、证据清单

| # | 证据类型 | 位置 / 标识 | 说明 |
|---|---|---|---|
| E1 | HTML 响应 | `http://www.huangjinjiage.cn/`（GBK 编码） | 首页骨架，`<meta charset=gb2312>` |
| E2 | 外部脚本 | `res.huangjinjiage.com.cn/jin.js` | 行情中继，JSONP 变量 |
| E3 | 外部脚本 | `res.huangjinjiage.com.cn/panjia2.js` | 国内金价页派生数据 |
| E4 | 外部脚本 | `www.huangjinjiage.cn/bgyzs/hspj1.js` | 回收页历史序列 |
| E5 | 样式表 | `res.huangjinjiage.com.cn/css/art_con.css` | 布局与行情表视觉参数 |
| E6 | 表格行 ID | `jiage1`~`jiage7`、`bajin` | 首页国际/国内行情行 |
| E7 | 数据接口 | `hq.sinajs.cn/list=hf_XAU,...` | 需 Referer，GBK 响应 |
| E8 | 图表接口 | `image.sinajs.cn/newchart/v5/futures/min/AU0.gif` | 国内期货 GIF（可用） |
| E9 | 图表接口 | `image.sinajs.cn/newchart/v5/global/...` | 国际现货 GIF（**返回空**，见局限） |
| E10 | 国际 JSON | `stock2.finance.sina.com.cn/.../GlobalFuturesService.getGlobalFuturesMinLine` | 国际分时 JSON（重建采用） |
| E11 | 国际 JSON | `.../GlobalFuturesService.getGlobalFuturesDailyKLine` | 国际日 K JSON（重建采用） |
| E12 | 运行时捕获 | REA `capture-browser-scenario` 首页 10s | 网络请求、渲染后 DOM、哈希 |
| E13 | 数值校验 | 原站观测：回收 845.7 / 铂金 293 / 18K 613 / 周大福 1208 | 与重建结果逐项吻合 |

---

## 三、局限与未知

1. **静态推断 ≠ 运行时事实**：以上公式由页面脚本、接口返回与观测值交叉验证得出，属"行为重建"，不代表原站源码实现。系数（如回收折扣 -15、18K 0.7123）为**按观测值反推的校准值**，原站可能使用服务端配置或分时段调整。
2. **原站存在服务端渲染成分**：部分回收价在原站是静态写死的值（每日人工/定时更新），重建改为实时推算，因此盘中会出现小幅差异。
3. **国际现货图片图表不可用**：`image.sinajs.cn/newchart/v5/global/*` 对 XAU/XPT/XPD/XAG 返回空体（HTTP 200 但 0 字节），仅国内期货合约 `AU0/AG0` 可用。重建因此对国际品种改用 JSON 接口 + ECharts 自绘 —— **这是有意偏离原站的技术改进，非等价复刻**。
4. **品牌金价非实时逐店采集**：原站品牌挂牌价通常每日更新一次，重建用"基础金价 + 固定品牌溢价"推算，单日内的相对次序稳定，但绝对值和门店实际挂牌可能存在个位数偏差。
5. **未复刻的内容**：原站的广告位、统计代码、备案信息、部分资讯文章正文、图片素材与 Logo 均未复制（版权与合规考虑）。
6. **行情源可用性依赖第三方**：新浪接口可能限频、改格式或加校验；重建已内置 3 秒缓存 + 失败降级（返回上次数据并标记 `stale`）。

---

## 四、重建实现说明

### 4.1 目录结构

```
goldprice-site/
├─ server.js                  # 零依赖 HTTP 服务：行情中继 / 派生计算 / 图表代理 / 静态服务
├─ config.json                # 运维配置：回收折扣/系数、品牌溢价、汇率回退、刷新间隔、默认主题
├─ package.json
├─ data/
│  └─ recycle-history.json    # 回收价历史（每日累积，供七日走势）
└─ public/
   ├─ index.html              # 首页
   ├─ guojijinjia.html        # 国际金价
   ├─ guoneijinjia.html       # 国内金价
   ├─ huishou.html            # 黄金回收
   ├─ pinpaijinjia.html       # 品牌金价
   ├─ zoushitu.html           # 价格走势图
   ├─ xianggang.html          # 香港金价
   ├─ favicon.svg
   ├─ css/style.css           # 全站样式（响应式、涨红跌绿）
   └─ js/
      ├─ quotes.js            # 行情拉取 + 表格填充 + 60s 轮询
      ├─ charts.js            # 国际品种 ECharts 分时/日K 绘制
      └─ echarts.min.js       # 本地化 ECharts 5.5（离线可用）
```

### 4.2 与原站的数据管线对照

| 环节 | 原站 | 重建 |
|---|---|---|
| 行情获取 | 浏览器 JSONP → 原站中继 `jin.js` | 浏览器 `fetch /api/quotes` → 本服务中继新浪 |
| 跨域/防盗链 | 由中继域名规避 | 服务端带 Referer 请求，前端同源 |
| 派生计算 | 页面内联脚本 | 服务端统一计算，前端只渲染 |
| 刷新机制 | `setInterval` 60s | `setInterval` 60s（一致） |
| 失败降级 | 无明显降级 | 3s 缓存 + `stale` 标记 + 保留上次数据 |
| 国际图表 | 图片 GIF（实际不可用） | ECharts 自绘（JSON 数据） |
| 国内图表 | 图片 GIF | 保留 GIF 代理（可用） |

### 4.3 API

| 接口 | 说明 |
|---|---|
| `GET /api/quotes` | 全量行情：`intl`（国际4品种）、`dom`（T+D）、`derived`（派生价）、`brands`（12品牌）、`hk`（香港）、`usdcny`、`usdhkd`、`stale` |
| `GET /api/recycle-history` | 回收价历史序列（每日一条，自动累积） |
| `GET /api/minline?symbol=XAU` | 国际品种分时（`points[{t,p,avg}]`、`prevClose`、`date`），60s 缓存 |
| `GET /api/daily?symbol=XAU&days=120` | 国际品种日 K（`bars[{date,o,h,l,c}]`），60s 缓存 |
| `GET /api/chart/newchart/v5/futures/{min\|daily}/AU0.gif` | 国内期货图表代理（路径白名单校验） |
| `GET /api/config` | 前端所需配置：刷新间隔选项、默认主题、站点名/免责声明 |

`symbol` 仅允许 `XAU / XPT / XPD / XAG`；图表代理用正则白名单防目录穿越。

### 4.4 运行

```bash
cd goldprice-site
node server.js          # 默认 3000 端口，可用 PORT=8080 node server.js 覆盖
```

打开 `http://localhost:3000/`。无 npm 依赖，Node.js 18+ 即可。

### 4.5 验证结果（与原站观测值对照）

| 指标 | 原站观测 | 重建输出 | 结论 |
|---|---|---|---|
| 黄金回收（元/克） | 845.7 | 845.70 | 一致 |
| 铂金回收（元/克） | 293 | 293 | 一致 |
| 18K 金回收（元/克） | 613 | 613 | 一致 |
| 周大福金价（元/克） | 1208 | 1208 | 一致 |
| 国内金价（元/克） | 860.7 | 860.70 | 一致 |
| 香港金价（港元/克） | 约 1009 | 1009.66 | 一致 |

页面渲染经无头 Chromium 逐页截图核验：首页、国际/国内/回收/品牌/走势图/香港 7 个页面数据全部填充，无空白占位。

### 4.6 可配置项（config.json）

所有"运营旋钮"集中在项目根目录 `config.json`，修改后**重启服务生效，无需改代码**：

| 配置段 | 字段 | 说明 | 默认 |
|---|---|---|---|
| `rates` | `usdcnyFallback` / `usdhkdFallback` | 汇率接口异常时的回退汇率 | 6.67 / 7.84 |
| `units` | `ozToGram` / `taelToGram` | 盎司→克、司马两→克 | 31.1035 / 37.429 |
| `derive` | `huishouGoldOffset` | 黄金回收 = 国内金价 + 该偏移 | −15 |
| | `touziJintiaoOffset` | 投资金条 = 国内金价 + 该偏移 | +16 |
| | `auSalesOffset` | 国内销售价 = 国内金价 + 该偏移 | +3 |
| | `huishouPtFactor` / `huishouPdFactor` | 铂/钯回收 = 国内折算价 × 系数 | 0.871 / 0.673 |
| | `huishou18kFactor` / `huishouSilverFactor` | 18K/白银回收系数 | 0.7123 / 0.694 |
| `brands` | `name/gold/pt/bar/ptOn` | 12 家品牌及各自溢价（gold/pt/bar 为加价额，ptOn 是否展示铂金价） | 见文件 |
| `refresh` | `defaultIntervalSec` / `options` | 默认刷新间隔、可选间隔（秒） | 60 / [30,60,120] |
| `theme` | `default` / `allowUserToggle` | 默认主题（light/dark）、是否允许用户切换 | light / true |

实测：将 `huishouGoldOffset` 由 −15 改为 −20 并重启，`/api/quotes` 的 `huishouGold` 与 `autd` 差值精确变为 −20。

### 4.7 刷新与主题交互

- **刷新间隔**：导航栏右侧下拉可选 30 / 60 / 120 秒，切换即时生效并写入 `localStorage`；页面隐藏时暂停轮询，切回自动恢复。
- **状态提示**：轮询开始时圆点脉冲"刷新中…"；成功显示"数据正常 · 更新于 HH:MM:SS"；上游限频降级显示"行情源限频，显示上次数据"；彻底失败显示"行情获取失败"。
- **暗色模式**：导航栏"🌙 暗色 / ☀ 亮色"切换，写入 `localStorage`；全部配色走 CSS 变量，ECharts 图表随主题重绘。也可用 URL 参数直接指定：`?theme=dark` / `?theme=light`。

---

## 五、重建建议（若要投产）

1. **行情源冗余**：单一新浪源存在限频风险，建议并行接入第二源（如上海黄金交易所延时行情、其他公开行情 API），做一致性校验与自动切换。
2. **派生系数配置化**：把回收折扣、品牌溢价、18K 系数抽到配置文件/数据库，支持运营按日调整，而非硬编码。
3. **历史数据落库**：当前用 JSON 文件累积，生产建议换 SQLite/PostgreSQL，按 `(date, item)` 建唯一索引，便于绘制长周期走势与做同比。
4. **品牌价真实采集**：若要贴近门店实价，应对各品牌官网/小程序做合规的定时采集（遵守 robots 与频率限制），或接入其开放数据。
5. **缓存与降级**：给 `/api/quotes` 增加 Redis 层与熔断；前端展示"数据时间"而非"服务器时间"，避免误导。
6. **合规**：行情数据需注明来源与"仅供参考"；不得复制原站文案、图片、Logo。

---

## 六、免责声明

本项目为**授权范围内的逆向分析与功能重建演示**，用于学习研究自有软件的功能实现。所有行情数据来自公开接口，价格仅供参考，不构成任何投资建议。原站的文案、图片、Logo 等受版权保护的素材未被复制。使用者应自行确保其使用场景符合当地法律法规与数据源服务条款。

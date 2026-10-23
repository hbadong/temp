# 好名网（qiming-system）

对 `www.qiming.cn`（起名网）做 REA 功能分析后，用**原创设计与内容**实现的功能等价网站系统。
功能与页面结构对齐原站，但文案、数据、样式均为原创，命理算法采用传统公共领域模型。

> 合规说明：仅复现功能与信息架构；未拷贝原站的文案、图片、LOGO 或任何受版权保护的素材。

## 功能清单

| 页面 | 功能 |
|---|---|
| `index.html` | 首页：三 Tab 表单（宝宝起名/姓名测试/姓名打分）、服务矩阵、用字排行、五行分类、文章四栏 |
| `naming.html` | 起名结果：八字（四柱干支+五行喜用）、诗词、周易三种模式 |
| `score.html` | 姓名测试打分：康熙笔画 → 天/人/地/外/总五格 + 81数理 + 三才 |
| `match.html` | 姓名配对（娱乐） |
| `poems.html` | 诗词起名库：诗经/楚辞/唐诗/宋词分类（诗文为公共领域古典作品） |
| `kxzd.html` | 康熙字典：按五行分类浏览，支持搜索 |
| `baijiaxing.html` | 百家姓 → 一键进入姓氏起名 |
| `gongsi.html` | 公司起名：8 大行业字库 × 吉祥数理 |
| `gaiming.html` / `bazi.html` / `shici.html` / `zhouyi.html` / `about.html` | 服务介绍页 |
| `search.html` | 站内搜索（汉字/诗词/文章） |
| `article.html` | 原创文章详情页 |

## 算法（`assets/js/engine.js`，原创实现）

- **干支历法**：年柱以立春（2/4 近似）为界；日柱用儒略日 `(JDN-11)%60`；月柱节气近似；时柱五鼠遁
- **五行喜用**：四柱八字五行计数，取最弱者为喜用神（简化演示模型）
- **三才五格**：康熙笔画 → 五格 → 81 数理吉凶 → 三才生克 → 综合评分
- **公司起名**：行业字库 × 吉祥字 × 数理评分

## 运行方式

纯静态站点，无需构建：

```bash
# 方式一：直接双击打开 index.html
# 方式二：本地服务
cd qiming-system && python3 -m http.server 8080
# 浏览器访问 http://localhost:8080
```

## 目录结构

```
qiming-system/
├── index.html            # 首页
├── naming.html           # 起名结果（支持 ?mode=bazi|poem|zhouyi）
├── score.html            # 姓名打分（?name=姓名）
├── match.html            # 姓名配对
├── poems.html            # 诗词起名库
├── kxzd.html             # 康熙字典
├── baijiaxing.html       # 百家姓
├── gongsi.html           # 公司起名
├── gaiming.html          # 成人改名
├── bazi.html / shici.html / zhouyi.html / about.html
├── search.html / article.html
└── assets/
    ├── css/style.css     # 原创样式
    └── js/
        ├── data.js       # 字库/姓氏/诗词/行业库/文章（原创数据）
        ├── engine.js     # 干支/五行/五格/起名引擎（原创算法）
        └── main.js       # 交互层
```

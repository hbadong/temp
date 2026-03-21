# 起名平台系统

融合传统国学智慧与现代AI技术的智能起名服务平台。

## 功能特性

### 前台用户端
- **宝宝起名** - AI智能起名，基于大数据分析
- **八字起名** - 根据生辰八字五行分析定制
- **诗词起名** - 从唐诗宋词等古典文学取名
- **周易起名** - 基于易经八卦的起名服务
- **姓名测试打分** - 九维测名法综合评分
- **公司起名** - 企业品牌命名服务
- **康熙字典** - 汉字五行属性查询
- **百家姓** - 姓氏名字大全
- **今日黄历** - 每日运势查询

### 后台管理系统
- 用户管理、订单管理、名字库管理
- 文章管理、系统配置、数据统计

## 技术栈

- **前端**: Vue 3 + Vite + Ant Design Vue
- **后端**: Node.js + Express + MySQL + Redis
- **核心引擎**: 八字计算、三才五格、九维打分

## 快速开始

### 环境要求
- Node.js >= 18.0.0
- MySQL >= 8.0
- Redis >= 6.0

### 安装

```bash
# 克隆项目
git clone https://github.com/hbadong/temp.git
cd temp/qiming-name-platform

# 安装依赖
chmod +x scripts/install.sh
./scripts/install.sh

# 或手动安装
npm install
cd frontend && npm install
cd ../backend && npm install
cd ../admin && npm install
```

### 配置

```bash
# 复制环境变量配置
cp backend/.env.example backend/.env

# 编辑 backend/.env 配置数据库和Redis
```

### 数据库初始化

```bash
cd backend
mysql -u root -p < scripts/initDb.sql
```

### 启动服务

```bash
# 开发模式（同时启动前端和后端）
npm run dev

# 或分别启动
cd frontend && npm run dev
cd backend && npm run dev
```

### Docker部署

```bash
# 启动所有服务
docker-compose up -d

# 查看日志
docker-compose logs -f

# 停止服务
docker-compose down
```

## 项目结构

```
qiming-name-platform/
├── frontend/           # 前台用户端 (Vue 3)
│   ├── src/
│   │   ├── api/       # API调用
│   │   ├── pages/     # 页面组件
│   │   ├── router/    # 路由配置
│   │   └── utils/     # 工具函数
│   └── vite.config.js
│
├── backend/            # 后端服务 (Node.js)
│   ├── src/
│   │   ├── config/    # 配置文件
│   │   ├── engines/   # 核心引擎
│   │   ├── routes/    # API路由
│   │   └── server.js  # 服务入口
│   └── scripts/       # 数据库脚本
│
├── admin/             # 后台管理端 (Vue 3)
│   └── src/
│
├── docker-compose.yml  # Docker配置
└── scripts/           # 工具脚本
```

## API接口

| 接口 | 方法 | 描述 |
|------|------|------|
| /api/v1/auth/register | POST | 用户注册 |
| /api/v1/auth/login | POST | 用户登录 |
| /api/v1/names/recommend | POST | 名字推荐 |
| /api/v1/names/test | POST | 名字测试打分 |
| /api/v1/bazi/calculate | POST | 八字计算 |
| /api/v1/kanxi/search | GET | 康熙字典查询 |
| /api/v1/surnames/:surname/names | GET | 姓氏名字大全 |
| /api/v1/almanac/today | GET | 今日黄历 |

## 核心算法

### 八字命盘计算
- 天干地支转换
- 五行旺衰分析
- 喜用神判定

### 三才五格
- 天格、地格、人格、外格、总格计算
- 数理吉凶判定

### 九维测名
- 音维度、形维度、义维度
- 数维度、理维度、运维度
- 境维度、德维度、命维度

## 许可证

MIT License

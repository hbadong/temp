# 会员兑换系统

移动号码会员兑换平台，支持用户通过手机验证码或卡密方式兑换爱奇艺、优酷、腾讯视频等国内主流视频平台会员。

## 功能特性

### 用户端功能
- 手机号验证码登录/注册
- 套餐浏览与选择
- 手机验证码兑换会员
- 卡密充值兑换会员
- 订单查询与管理

### 管理后台功能
- 管理员登录与权限管理
- 仪表盘数据统计
- 套餐配置管理（CRUD、上下架）
- 卡密生成、查询、导出、作废
- 订单查询、筛选、重试
- 销售统计与财务报表

## 技术栈

### 后端
- Node.js 18+
- Express.js
- TypeScript
- MySQL 8.0
- Redis 6.0
- JWT Authentication

### 前端
- Vue 3
- Vite
- Pinia (状态管理)
- Vant (用户端 UI)
- Element Plus (管理后台 UI)

### 部署
- Docker & Docker Compose
- Nginx (反向代理)

## 项目结构

```
membership-redemption-system/
├── backend/                    # 后端服务
│   ├── src/
│   │   ├── config/           # 配置管理
│   │   ├── controllers/      # 控制器
│   │   ├── database/          # 数据库schema和迁移
│   │   ├── middleware/        # 中间件
│   │   ├── models/            # 数据模型
│   │   ├── routes/            # 路由
│   │   ├── services/          # 业务服务
│   │   └── utils/             # 工具函数
│   └── Dockerfile
├── user-frontend/            # 用户前端 H5
│   ├── src/
│   │   ├── api/              # API调用
│   │   ├── router/           # 路由配置
│   │   ├── store/             # 状态管理
│   │   └── views/             # 页面组件
│   └── Dockerfile
├── admin-backend/            # 管理后台 Web
│   ├── src/
│   │   ├── api/              # API调用
│   │   ├── router/           # 路由配置
│   │   ├── store/             # 状态管理
│   │   └── views/             # 页面组件
│   └── Dockerfile
├── nginx/                     # Nginx 配置
├── docker-compose.yml         # Docker Compose 配置
└── README.md
```

## 快速开始

### 环境要求
- Node.js 18+
- Docker & Docker Compose
- MySQL 8.0 (可选，使用 Docker 时不需要单独安装)
- Redis 6.0 (可选，使用 Docker 时不需要单独安装)

### 使用 Docker 部署

1. 克隆代码并进入目录
```bash
cd membership-redemption-system
```

2. 配置环境变量
```bash
cp backend/.env.example backend/.env
# 编辑 backend/.env 填入配置
```

3. 启动服务
```bash
docker-compose up -d
```

4. 初始化数据库
```bash
docker-compose exec backend npm run migration:run
```

5. 访问服务
- 用户端: http://localhost
- 管理后台: http://localhost/admin/
- 后端 API: http://localhost:8000

### 本地开发

1. 安装依赖
```bash
# 后端
cd backend
npm install

# 用户前端
cd ../user-frontend
npm install

# 管理后台
cd ../admin-backend
npm install
```

2. 配置环境变量
```bash
cd backend
cp .env.example .env
# 编辑 .env 填入配置
```

3. 启动开发服务器
```bash
# 后端 (端口 8000)
cd backend
npm run dev

# 用户前端 (端口 3000)
cd user-frontend
npm run dev

# 管理后台 (端口 3001)
cd admin-backend
npm run dev
```

## API 接口

### 用户端 API

| 接口 | 方法 | 描述 |
|------|------|------|
| /api/v1/user/send-code | POST | 发送验证码 |
| /api/v1/user/login | POST | 手机号登录 |
| /api/v1/user/logout | POST | 退出登录 |
| /api/v1/user/info | GET | 获取用户信息 |
| /api/v1/products | GET | 获取套餐列表 |
| /api/v1/products/:id | GET | 获取套餐详情 |
| /api/v1/exchange/mobile | POST | 手机号兑换会员 |
| /api/v1/exchange/card | POST | 卡密充值 |
| /api/v1/orders | GET | 获取订单列表 |
| /api/v1/orders/:id | GET | 获取订单详情 |

### 管理后台 API

| 接口 | 方法 | 描述 |
|------|------|------|
| /api/v1/admin/login | POST | 管理员登录 |
| /api/v1/admin/logout | POST | 管理员退出 |
| /api/v1/admin/products | GET/POST | 套餐管理 |
| /api/v1/admin/products/:id | PUT/DELETE | 套餐 CRUD |
| /api/v1/admin/cards/batch | POST | 生成卡密批次 |
| /api/v1/admin/cards | GET | 查询卡密列表 |
| /api/v1/admin/cards/:id/disable | POST | 作废卡密 |
| /api/v1/admin/orders | GET | 查询订单列表 |
| /api/v1/admin/orders/:id/retry | POST | 重试充值 |
| /api/v1/admin/stats/dashboard | GET | 仪表盘数据 |

详细 API 文档请参考 `docs/api/` 目录。

## 数据库

系统使用 MySQL 8.0，主要数据表包括：

- `users` - 用户表
- `admins` - 管理员表
- `products` - 套餐表
- `card_batches` - 卡密批次表
- `cards` - 卡密表
- `orders` - 订单表
- `sms_logs` - 短信记录表
- `operation_logs` - 操作日志表

数据库 schema 文件位于 `backend/src/database/schema.sql`。

## 测试

```bash
# 运行单元测试
cd backend
npm test

# 运行测试覆盖率
npm run test:cov
```

## 配置说明

### 后端环境变量

| 变量名 | 描述 | 示例 |
|--------|------|------|
| DB_HOST | 数据库主机 | localhost |
| DB_PORT | 数据库端口 | 3306 |
| DB_USER | 数据库用户名 | root |
| DB_PASSWORD | 数据库密码 | your_password |
| DB_NAME | 数据库名称 | membership_redemption |
| REDIS_HOST | Redis 主机 | localhost |
| REDIS_PORT | Redis 端口 | 6379 |
| JWT_SECRET | JWT 密钥 | your_jwt_secret |
| ALIYUN_ACCESS_KEY_ID | 阿里云 AccessKey ID | your_access_key |
| ALIYUN_ACCESS_KEY_SECRET | 阿里云 AccessKey Secret | your_access_secret |

## License

MIT

# API 接口文档

## 基础信息

- 基础路径: `/api/v1`
- 数据格式: JSON
- 认证方式: Bearer Token (JWT)
- 字符编码: UTF-8

## 通用响应格式

### 成功响应
```json
{
  "code": 0,
  "message": "操作成功",
  "data": {},
  "timestamp": 1700000000000
}
```

### 分页响应
```json
{
  "code": 0,
  "message": "查询成功",
  "data": {
    "list": [],
    "total": 100,
    "page": 1,
    "pageSize": 20
  },
  "timestamp": 1700000000000
}
```

### 错误响应
```json
{
  "code": 50001,
  "message": "系统繁忙",
  "timestamp": 1700000000000
}
```

## 错误码定义

| 错误码 | 描述 |
|--------|------|
| 0 | 操作成功 |
| 10001 | 参数验证失败 |
| 10002 | 验证码错误 |
| 10003 | 验证码已过期 |
| 10004 | 验证码验证已锁定 |
| 10005 | 认证失败 |
| 10006 | 令牌已过期 |
| 20001 | 订单创建失败 |
| 20002 | 订单支付失败 |
| 20003 | 第三方服务错误 |
| 20004 | 订单处理超时 |
| 30001 | 卡密无效 |
| 30002 | 卡密已使用 |
| 30003 | 卡密已过期 |
| 30004 | 卡密已作废 |
| 40001 | 套餐库存不足 |
| 40002 | 套餐不存在 |
| 50001 | 系统繁忙 |
| 50002 | 请求频率超限 |

---

## 用户端 API

### 认证模块

#### 发送验证码
```
POST /user/send-code
```

**请求参数**
```json
{
  "phone": "13800138000"
}
```

**响应示例**
```json
{
  "code": 0,
  "message": "验证码已发送",
  "data": {
    "message": "验证码已发送"
  }
}
```

#### 手机号登录
```
POST /user/login
```

**请求参数**
```json
{
  "phone": "13800138000",
  "code": "123456"
}
```

**响应示例**
```json
{
  "code": 0,
  "message": "操作成功",
  "data": {
    "user": {
      "id": 1,
      "phone": "13800138000",
      "status": 1
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

#### 获取用户信息
```
GET /user/info
Authorization: Bearer <token>
```

**响应示例**
```json
{
  "code": 0,
  "message": "操作成功",
  "data": {
    "id": 1,
    "phone": "13800138000",
    "status": 1,
    "createdAt": "2024-01-01T00:00:00.000Z"
  }
}
```

#### 退出登录
```
POST /user/logout
Authorization: Bearer <token>
```

---

### 套餐模块

#### 获取套餐列表
```
GET /products
Authorization: Bearer <token>
```

**查询参数**
| 参数名 | 类型 | 描述 |
|--------|------|------|
| platform | string | 平台 (iqiyi/youku/tencent) |
| status | number | 状态 (1-上架) |

**响应示例**
```json
{
  "code": 0,
  "message": "操作成功",
  "data": [
    {
      "id": 1,
      "platform": "iqiyi",
      "name": "月度会员",
      "description": "爱奇艺月度会员",
      "durationDays": 30,
      "price": 20.00,
      "stock": 100,
      "status": 1
    }
  ]
}
```

#### 获取套餐详情
```
GET /products/:id
Authorization: Bearer <token>
```

---

### 兑换模块

#### 手机号兑换会员
```
POST /exchange/mobile
Authorization: Bearer <token>
```

**请求参数**
```json
{
  "productId": 1,
  "targetAccount": "13800138000",
  "code": "123456"
}
```

**响应示例**
```json
{
  "code": 0,
  "message": "兑换成功",
  "data": {
    "orderId": 1,
    "orderNo": "O2024010112345678"
  }
}
```

#### 卡密充值
```
POST /exchange/card
Authorization: Bearer <token>
```

**请求参数**
```json
{
  "cardNo": "CRS1234567890123",
  "targetAccount": "13800138000"
}
```

**响应示例**
```json
{
  "code": 0,
  "message": "充值成功",
  "data": {
    "orderId": 2,
    "orderNo": "O2024010112345679"
  }
}
```

---

### 订单模块

#### 获取订单列表
```
GET /orders
Authorization: Bearer <token>
```

**查询参数**
| 参数名 | 类型 | 描述 |
|--------|------|------|
| page | number | 页码 (默认1) |
| pageSize | number | 每页数量 (默认20) |

**响应示例**
```json
{
  "code": 0,
  "message": "查询成功",
  "data": {
    "list": [
      {
        "id": 1,
        "orderNo": "O2024010112345678",
        "productId": 1,
        "productName": "月度会员",
        "platform": "iqiyi",
        "type": 1,
        "amount": 20.00,
        "status": 2,
        "createdAt": "2024-01-01T00:00:00.000Z"
      }
    ],
    "total": 100,
    "page": 1,
    "pageSize": 20
  }
}
```

#### 获取订单详情
```
GET /orders/:id
Authorization: Bearer <token>
```

---

## 管理后台 API

### 管理员认证

#### 管理员登录
```
POST /admin/login
```

**请求参数**
```json
{
  "username": "admin",
  "password": "your_password"
}
```

**响应示例**
```json
{
  "code": 0,
  "message": "操作成功",
  "data": {
    "admin": {
      "id": 1,
      "username": "admin",
      "role": "super_admin",
      "lastLoginAt": "2024-01-01T00:00:00.000Z"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}
```

---

### 套餐管理

#### 获取套餐列表
```
GET /admin/products
Authorization: Bearer <token>
```

#### 创建套餐
```
POST /admin/products
Authorization: Bearer <token>
```

**请求参数**
```json
{
  "platform": "iqiyi",
  "name": "月度会员",
  "description": "爱奇艺月度会员",
  "durationDays": 30,
  "price": 20.00,
  "stock": 100,
  "status": 1
}
```

#### 更新套餐
```
PUT /admin/products/:id
Authorization: Bearer <token>
```

#### 删除套餐
```
DELETE /admin/products/:id
Authorization: Bearer <token>
```

#### 更新套餐状态
```
PUT /admin/products/:id/status
Authorization: Bearer <token>
```

**请求参数**
```json
{
  "status": 0
}
```

---

### 卡密管理

#### 生成卡密批次
```
POST /admin/cards/batch
Authorization: Bearer <token>
```

**请求参数**
```json
{
  "productId": 1,
  "count": 100,
  "prefix": "CRS",
  "validFrom": "2024-01-01T00:00:00.000Z",
  "validUntil": "2025-12-31T23:59:59.000Z"
}
```

#### 查询卡密列表
```
GET /admin/cards
Authorization: Bearer <token>
```

**查询参数**
| 参数名 | 类型 | 描述 |
|--------|------|------|
| batchId | number | 批次ID |
| status | number | 状态 (0-未使用,1-已使用,2-已作废) |
| cardNo | string | 卡密号 |
| page | number | 页码 |
| pageSize | number | 每页数量 |

#### 导出卡密
```
GET /admin/cards/export
Authorization: Bearer <token>
```

#### 作废卡密
```
POST /admin/cards/:id/disable
Authorization: Bearer <token>
```

---

### 订单管理

#### 查询订单列表
```
GET /admin/orders
Authorization: Bearer <token>
```

**查询参数**
| 参数名 | 类型 | 描述 |
|--------|------|------|
| orderNo | string | 订单号 |
| userPhone | string | 用户手机 |
| platform | string | 平台 |
| status | number | 状态 |
| startDate | string | 开始日期 |
| endDate | string | 结束日期 |
| page | number | 页码 |
| pageSize | number | 每页数量 |

#### 重试充值
```
POST /admin/orders/:id/retry
Authorization: Bearer <token>
```

---

### 统计报表

#### 获取仪表盘数据
```
GET /admin/stats/dashboard
Authorization: Bearer <token>
```

**响应示例**
```json
{
  "code": 0,
  "message": "操作成功",
  "data": {
    "todayOrders": 100,
    "todaySales": 2000.00,
    "totalUsers": 5000,
    "totalCards": 10000,
    "orderSuccessRate": "98.50",
    "recentOrders": []
  }
}
```

#### 获取销售统计
```
GET /admin/stats/sales
Authorization: Bearer <token>
```

**查询参数**
| 参数名 | 类型 | 描述 |
|--------|------|------|
| startDate | string | 开始日期 |
| endDate | string | 结束日期 |
| platform | string | 平台 |
| groupBy | string | 分组 (day/week/month) |

#### 导出统计数据
```
GET /admin/stats/export
Authorization: Bearer <token>
```

---

## 渠道商 API

### 卡密充值
```
POST /partner/card/redeem
```

**请求参数**
```json
{
  "cardNo": "CRS1234567890123",
  "targetAccount": "13800138000",
  "partnerId": "PARTNER001",
  "sign": "md5签名"
}
```

### 订单查询
```
POST /partner/query
```

**请求参数**
```json
{
  "orderNo": "O2024010112345678",
  "partnerId": "PARTNER001",
  "sign": "md5签名"
}
```

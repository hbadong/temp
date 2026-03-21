#!/bin/bash

echo "===== 起名平台系统安装脚本 ====="

# 检查Node.js版本
if ! command -v node &> /dev/null; then
    echo "错误: Node.js 未安装"
    echo "请先安装 Node.js 18+"
    exit 1
fi

NODE_VERSION=$(node -v | cut -d'v' -f2 | cut -d'.' -f1)
if [ "$NODE_VERSION" -lt 18 ]; then
    echo "错误: Node.js 版本过低 (当前: $(node -v), 需要: 18+)"
    exit 1
fi

# 检查MySQL
if ! command -v mysql &> /dev/null; then
    echo "警告: MySQL 客户端未安装"
fi

echo "安装根目录依赖..."
cd "$(dirname "$0")/.." || exit
npm install

echo ""
echo "安装前端依赖..."
cd frontend && npm install
cd ..

echo ""
echo "安装后端依赖..."
cd backend && npm install
cd ..

echo ""
echo "安装管理后台依赖..."
cd admin && npm install
cd ..

echo ""
echo "===== 安装完成 ====="
echo ""
echo "下一步:"
echo "1. 复制环境变量文件: cp backend/.env.example backend/.env"
echo "2. 修改 backend/.env 中的数据库配置"
echo "3. 初始化数据库: cd backend && npm run init:db"
echo "4. 启动开发服务器: npm run dev"

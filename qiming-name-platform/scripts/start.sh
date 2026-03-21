#!/bin/bash

echo "===== 起名平台系统启动脚本 ====="

# 设置工作目录
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

cd "$PROJECT_DIR" || exit

# 启动后端
echo "启动后端服务..."
cd backend && npm run dev &
BACKEND_PID=$!

# 启动前端
echo "启动前端服务..."
cd ../frontend && npm run dev &
FRONTEND_PID=$!

echo ""
echo "===== 服务已启动 ====="
echo "前端: http://localhost:3000"
echo "后端: http://localhost:8080"
echo "API文档: http://localhost:8080/api-docs"
echo ""
echo "按 Ctrl+C 停止所有服务"

# 等待信号
trap "kill $BACKEND_PID $FRONTEND_PID 2>/dev/null; exit" SIGINT SIGTERM

wait

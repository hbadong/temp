#!/bin/bash
# 网盘搜索系统一键启动脚本：后端 API (3001) + 前端 Vite (5173)

set -e

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"

command -v node >/dev/null || { echo "需要 Node.js 18+"; exit 1; }

if [ ! -d "$ROOT_DIR/server/node_modules" ]; then
  echo "[setup] 安装后端依赖..."
  (cd "$ROOT_DIR/server" && npm install --no-audit --no-fund)
fi

if [ ! -d "$ROOT_DIR/web/node_modules" ]; then
  echo "[setup] 安装前端依赖..."
  (cd "$ROOT_DIR/web" && npm install --no-audit --no-fund)
fi

cleanup() {
  [ -n "$BACKEND_PID" ] && kill "$BACKEND_PID" 2>/dev/null
}
trap cleanup EXIT

echo "[start] 启动后端 API :3001"
(cd "$ROOT_DIR/server" && npm start) &
BACKEND_PID=$!

sleep 1.5

echo "[start] 启动前端 :5173（对外访问入口）"
cd "$ROOT_DIR/web" && exec npm run dev

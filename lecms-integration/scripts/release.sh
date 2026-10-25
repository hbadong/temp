#!/usr/bin/env bash
# ============================================================
# 好名网发布流程脚本（SEO 第二十三轮纳入 checklist）
# ------------------------------------------------------------
# 用法：
#   ./release.sh            # 完整流程：清缓存 → 版本审计 → 回归验证 → 同步副本
#   ./release.sh --verify   # 只跑回归验证
#   ./release.sh --cache    # 只清 prerender 缓存
#   ./release.sh --audit    # 只做静态资源版本号审计
#   ./release.sh --sync     # 只同步 theme 副本
#
# 铁律：
#   1. 主题（view/haoiming）有任何变更 → 必须清 prerender 缓存
#      （缓存 TTL 30 天，仅靠 ?v= 版本号规避 HTML 内引用，模板结构变更不清缓存会陈旧）
#   2. 回归验证（verify_seo + verify_m2 + check_mobile）不过 → 不发布
#   3. git 提交由人工执行（脚本不自动 commit/push，避免误提交）
# ============================================================
set -euo pipefail

SRC="/var/www/lecms/view/haoiming"
DST="/workspace/lecms-integration/theme/haoiming"
CACHE="/var/www/lecms/prerender/cache"
SCRIPTS="/workspace/lecms-integration/scripts"
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'

log()  { echo -e "${GREEN}[release]${NC} $*"; }
warn() { echo -e "${YELLOW}[warn]${NC} $*"; }
die()  { echo -e "${RED}[FAIL]${NC} $*"; exit 1; }

clean_cache() {
  log "清理 prerender 缓存（主题变更后陈旧 HTML 的最大风险点）..."
  local n
  n=$(ls -1 "$CACHE"/*.html 2>/dev/null | wc -l || echo 0)
  rm -f "$CACHE"/*.html
  log "已删除 $n 个缓存文件（下次爬虫请求实时重建，X-Prerendered: 1）"
}

audit_versions() {
  log "审计静态资源版本号引用一致性..."
  local problems=0
  # 1. main.7.js 在页面中的引用必须带 v=（防 5 分钟 HTML 缓存内的旧引用）
  local refs
  refs=$(grep -rho 'main\.7\.js[^"]*' "$SRC" --include='*.htm' --include='*.html' | sort -u)
  echo "$refs" | while read -r r; do
    case "$r" in
      *"?v="*) : ;;
      *) warn "main.7.js 引用缺少版本号: $r"; echo "x" >> /tmp/rel_audit_flag ;;
    esac
  done
  [ -f /tmp/rel_audit_flag ] && problems=1 && rm -f /tmp/rel_audit_flag
  # 2. 被引用资源必须存在
  for res in "js/main.7.js" "js/data.7.js" "js/engine.7.js" "js/shuowen.7.js" "css/style.7.css"; do
    [ -f "$SRC/$res" ] || { warn "缺失资源: $res"; problems=1; }
  done
  # 3. 主题内 JS 修改时间 vs 最近提交（提醒人工决定是否升版本号）
  local newest
  newest=$(find "$SRC/js" "$SRC/css" -name '*.7.*' -newer "$SRC/inc-header.htm" 2>/dev/null | wc -l)
  [ "$newest" -gt 0 ] && warn "$newest 个 .7.* 资源比 inc-header.htm 新——若内容已变，记得升 ?v= 版本号"
  [ "$problems" -eq 0 ] && log "版本号审计通过" || warn "版本号审计有问题（见上方警告，人工确认后继续）"
}

run_verify() {
  log "回归 1/3：SEO 全量验证（TDK/noindex/sitemap/预渲染）..."
  (cd "$SCRIPTS" && python3 verify_seo.py > /tmp/rel_verify_seo.log 2>&1) \
    && log "verify_seo 通过" || { cat /tmp/rel_verify_seo.log; die "verify_seo 失败"; }
  log "回归 2/3：M2 渲染验证（JS 错误/异步 CSS/截图）..."
  (cd "$SCRIPTS" && python3 verify_m2.py > /tmp/rel_verify_m2.log 2>&1) \
    && log "verify_m2 通过" || { cat /tmp/rel_verify_m2.log; die "verify_m2 失败"; }
  log "回归 3/3：移动端溢出检查..."
  (cd "$SCRIPTS" && python3 check_mobile.py > /tmp/rel_mobile.log 2>&1) \
    && log "check_mobile 通过" || { cat /tmp/rel_mobile.log; die "check_mobile 失败"; }
}

sync_theme() {
  log "同步主题副本（/var/www/lecms/view/haoiming → lecms-integration/theme/haoiming）..."
  rsync -a --delete "$SRC/" "$DST/"
  local diff
  diff=$(cd /workspace && git status --porcelain lecms-integration/theme | wc -l)
  log "同步完成，git 检测到 $diff 个变更文件"
}

git_hint() {
  echo
  log "===== 以下步骤由人工执行 ====="
  echo "  cd /workspace"
  echo "  git add lecms-integration/ && git status"
  echo "  git commit -m '主题更新：<描述>；验证：verify_seo/verify_m2/check_mobile 全过'"
  echo "  git -c http.version=HTTP/1.1 push https://<PAT>@github.com/hbadong/temp.git haoiming"
}

case "${1:-all}" in
  --cache)  clean_cache ;;
  --audit)  audit_versions ;;
  --verify) run_verify ;;
  --sync)   sync_theme; git_hint ;;
  all|"")
    clean_cache
    audit_versions
    run_verify
    sync_theme
    git_hint
    log "===== 发布流程完成（git 提交待人工执行） ====="
    ;;
  *) die "未知参数: $1（支持 --cache/--audit/--verify/--sync/无参=all）" ;;
esac

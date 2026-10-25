#!/usr/bin/env bash
# ============================================================
# 上线验收脚本：对生产环境跑一遍核心检查
# 用法：./deploy_check.sh https://your-domain.com
# ============================================================
set -u
SITE="${1:-https://ad6acb5cb3aeb83e6.app.workbuddy.link}"
UA_BOT='Mozilla/5.0 (compatible; Baiduspider/2.0)'
UA_USER='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36'
PASS=0; FAIL=0

ok()   { echo -e "  \033[0;32m✅ $1\033[0m"; PASS=$((PASS+1)); }
bad()  { echo -e "  \033[0;31m❌ $1\033[0m"; FAIL=$((FAIL+1)); }

check() { # $1=描述 $2=实际 $3=期望（包含匹配）
    if echo "$2" | grep -q "$3"; then ok "$1"; else bad "$1 (got: $(echo "$2" | head -c 120))"; fi
}

echo "== 1. 基础可达性 =="
check "首页 200"            "$(curl -s -o /dev/null -w '%{http_code}' -A "$UA_USER" "$SITE/")" "200"
check "robots.txt 200"      "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/robots.txt")" "200"
check "sitemap.xml 200"     "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/sitemap.xml")" "200"
check "工具页静态 200"      "$(curl -s -o /dev/null -w '%{http_code}' -A "$UA_USER" "$SITE/view/haoiming/tool/kxzd.html")" "200"

echo "== 2. 安全响应头 =="
H=$(curl -sI -A "$UA_USER" "$SITE/")
check "X-Content-Type-Options" "$H" "X-Content-Type-Options: nosniff"
check "X-Frame-Options"        "$H" "X-Frame-Options: SAMEORIGIN"
check "Referrer-Policy"        "$H" "Referrer-Policy: strict-origin-when-cross-origin"

echo "== 3. 静态资源缓存与压缩 =="
J=$(curl -sI -H 'Accept-Encoding: gzip' "$SITE/view/haoiming/js/main.7.js?v=20261025d")
check "JS gzip"               "$J" "Content-Encoding: gzip"
check "JS 30天 immutable"     "$J" "max-age=2592000, immutable"
check "JS Vary"               "$J" "Vary: Accept-Encoding"
X=$(curl -sI "$SITE/sitemap.xml")
check "sitemap 1小时缓存"     "$X" "max-age=3600"
C=$(curl -sI "$SITE/view/haoiming/css/style.7.css?v=20261025e")
check "CSS 30天 immutable"    "$C" "max-age=2592000, immutable"

echo "== 4. robots 净化（C1 防回归） =="
R=$(curl -s "$SITE/robots.txt")
check "只声明本站 sitemap"    "$R" "Sitemap: $SITE/sitemap.xml"
if echo "$R" | grep -qi 'sitemap-dict'; then bad "robots 仍含外站 sitemap"; else ok "无 sitemap-dict 污染"; fi

echo "== 5. 预渲染管线（爬虫 UA） =="
B=$(curl -sI -A "$UA_BOT" "$SITE/view/haoiming/tool/char.html?c=%E6%9E%97")
check "char?c=林 200"         "$B" "200"
check "X-Prerendered 头"      "$B" "X-Prerendered: 1"
check "爬虫响应 gzip"         "$B" "Content-Encoding: gzip"
T=$(curl -s -A "$UA_BOT" "$SITE/view/haoiming/tool/char.html?c=%E6%9E%97")
check "爬虫拿到渲染后 TDK"    "$T" "<title>林字寓意"
check "爬虫拿到 canonical"    "$T" 'rel="canonical"'
N=$(curl -s -A "$UA_BOT" "$SITE/view/haoiming/tool/char.html?c=ZZ")
check "无效字符 noindex"      "$N" 'name="robots" content="noindex"'
S=$(curl -s -A "$UA_BOT" -G --data-urlencode 'c=王' --data-urlencode 'wx=木' "$SITE/view/haoiming/tool/surname.html")
check "矩阵页差异化 TDK"      "$S" "<title>王姓五行属木的名字"
B2=$(curl -s -A "$UA_BOT" "$SITE/view/haoiming/tool/baijiaxing.html")
check "百家姓预渲染内容"      "$B2" "bjx"

echo "== 6. 敏感目录 =="
for p in /lecms/ /install/ /log/ /runcache/ /__prerender_gateway; do
    check "$p 拒绝访问" "$(curl -s -o /dev/null -w '%{http_code}' "$SITE$p")" "403\|404"
done
check "JS 源文件禁止下载" "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/view/haoiming/js/main.7.js")" "403"
check "min JS 可正常访问"  "$(curl -s -o /dev/null -w '%{http_code}' "$SITE/view/haoiming/js/main.min.7.js")" "200"

echo "== 7. sitemap 规模 =="
U=$(curl -s "$SITE/sitemap.xml" | grep -c '<url>')
if [ "$U" -gt 0 ]; then ok "sitemap 含 $U 个 URL"; else bad "sitemap 为空"; fi

echo
echo "===== 结果：$PASS 通过 / $FAIL 失败 ====="
[ "$FAIL" -eq 0 ] && echo "🎉 上线验收全部通过" || echo "⚠️ 有问题项，按上方提示排查"

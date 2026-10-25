#!/usr/bin/env bash
# ============================================================
# JS 压缩流水线（前端代码保护 L1：去注释 + 压缩 + 混淆变量名）
# ------------------------------------------------------------
# 输入：view/haoiming/js/{data,shuowen,engine,main}.7.js（源文件保留在 git）
# 输出：同目录 *.min.7.js（部署引用此文件）
# 随后自动把全站页面引用切换到 .min.7.js
#
# 说明：
#   - terser 保留字符串字面量（中文文案/字库数据零损耗），仅删注释+压缩+混淆
#   - 不上传 source map（.map）——那等于把源码再送一遍
#   - 改完必须跑：release.sh --verify（三连回归）+ test_final.py（E2E）
# ============================================================
set -euo pipefail

SRC="/var/www/lecms/view/haoiming/js"
FILES="data.7.js shuowen.7.js engine.7.js main.7.js"

echo "== 1/3 压缩 =="
for f in $FILES; do
  base="${f%.7.js}"
  npx --yes terser "$SRC/$f" \
    --compress --mangle \
    --format comments=false,beautify=false \
    -o "$SRC/${base}.min.7.js" 2>/dev/null
  before=$(wc -c < "$SRC/$f"); after=$(wc -c < "$SRC/${base}.min.7.js")
  pct=$(( 100 - after * 100 / before ))
  printf '  %-14s %7dB → %7dB  (-%s%%)\n' "$f" "$before" "$after" "$pct"
done

echo "== 2/3 切换全站引用 =="
cd /var/www/lecms/view/haoiming
changed=0
for f in $(grep -rl 'js/data\.7\.js\|js/shuowen\.7\.js\|js/engine\.7\.js\|js/main\.7\.js' . --include='*.htm' --include='*.html'); do
  sed -i -E 's%(js/(data|shuowen|engine|main))\.7\.js%\1.min.7.js%g' "$f"
  changed=$((changed+1))
done
echo "  已切换 $changed 个页面文件"

echo "== 3/3 语法与挂载验证 =="
node -e "
const fs = require('fs');
for (const b of ['data','shuowen','engine','main']) {
  const code = fs.readFileSync('$SRC/'+b+'.min.7.js','utf8');
  new Function(code);   // 语法校验
  console.log('  '+b+'.min.7.js 语法 OK');
}
// 挂载校验：data/engine 必须仍挂全局
global.window = global; global.HM_DATA = {}; global.HM_ENGINE = {};
eval(fs.readFileSync('$SRC/data.min.7.js','utf8'));
eval(fs.readFileSync('$SRC/engine.min.7.js','utf8'));
if (!global.HM_DATA.CHARS || !global.HM_DATA.CHARS.length) throw new Error('HM_DATA.CHARS 丢失');
if (!global.HM_ENGINE.bazi || !global.HM_ENGINE.wuge) throw new Error('HM_ENGINE 方法丢失');
console.log('  全局挂载 OK：CHARS=' + global.HM_DATA.CHARS.length + '，engine.bazi/wuge 在位');
"
echo
echo "完成。源文件保留在 git（开发用），部署引用 .min.7.js。"
echo "⚠️ 未上传 .map 文件（source map 会还原源码，等于白压缩）"

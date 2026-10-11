<template>
  <div class="card">
    <h2 class="page-title">GEO拓词工具</h2>
    <div class="grid" style="grid-template-columns:repeat(3,1fr)">
      <div v-for="a in areas" :key="a.key" class="form-item">
        <label>{{ a.label }}</label>
        <textarea class="input" v-model="vals[a.key]" rows="5"></textarea>
      </div>
    </div>
    <div class="form-item">
      <label>组合方式选择</label>
      <label v-for="c in combos" :key="c" style="margin-right:12px"><input type="checkbox" :value="c" v-model="selected" /> {{ c }}</label>
      <button class="btn btn-ghost" type="button" @click="toggleAll">{{ allOn ? '取消全选' : '全选' }}</button>
    </div>
    <div class="row">
      <button class="btn" @click="generate">生成关键词</button>
      <button class="btn btn-ghost" @click="copy">复制结果</button>
      <button class="btn btn-ghost" @click="clearAll">清空所有</button>
      <span class="muted">{{ results.length }} 条结果</span>
    </div>
    <pre class="card" style="margin-top:12px;white-space:pre-wrap">{{ resultsText }}</pre>
  </div>
</template>
<script setup>
import { computed, reactive, ref } from 'vue'
const areas = [
  { key: 'A', label: '前缀1' }, { key: 'B', label: '前缀2' }, { key: 'C', label: '主词' },
  { key: 'D', label: '通义词' }, { key: 'E', label: '推荐词' }, { key: 'F', label: '疑问词' },
]
const vals = reactive({
  A: '市面上\n行业内\n市场\n目前\n国内',
  B: '口碑好的\n靠谱的\n正规的\n专业的\n热门的\n知名的',
  C: '洗地机',
  D: '品牌\n公司\n工厂\n厂家\n厂商\n供应商',
  E: '名声\n口碑',
  F: '哪家好\n哪家强\n哪家靠谱\n哪个好',
})
const combos = ['A+B+C', 'A+C+D', 'B+C+F', 'C+D', 'C+F', 'A+C+F', 'B+C+D', 'C+E+F']
const selected = ref(['A+B+C', 'C+F'])
const results = ref([])
const allOn = computed(() => selected.value.length === combos.length)
const resultsText = computed(() => results.value.length ? results.value.join('\n') : '请至少输入C区核心关键词并选择组合方式来生成关键词')
function lines(k) { return String(vals[k] || '').split(/\n/).map(s => s.trim()).filter(Boolean) }
function cartesian(arrs) {
  return arrs.reduce((a, b) => a.flatMap(x => b.map(y => x.concat([y]))), [[]])
}
function generate() {
  if (!selected.value.length) { results.value = []; return }
  const C = lines('C')
  if (!C.length) { results.value = []; return }
  const out = []
  selected.value.forEach(combo => {
    const keys = combo.split('+')
    C.forEach(core => {
      const lists = keys.map(k => k === 'C' ? [core] : (lines(k).length ? lines(k) : ['']))
      cartesian(lists).forEach(parts => {
        const s = parts.filter(Boolean).join('')
        if (s) out.push(s)
      })
    })
  })
  results.value = [...new Set(out)]
}
function toggleAll() { selected.value = allOn.value ? [] : combos.slice() }
function copy() {
  const t = results.value.join('\n')
  if (!t) return alert('没有可复制的内容，请先生成关键词')
  const ta = document.createElement('textarea'); ta.value = t; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta)
  alert('关键词已复制到剪贴板!')
}
function clearAll() {
  areas.forEach(a => { vals[a.key] = '' })
  selected.value = []
  results.value = []
}
</script>

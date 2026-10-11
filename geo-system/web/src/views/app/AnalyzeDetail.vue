<template>
  <div v-if="job">
    <div class="card" style="margin-bottom:14px">
      <div class="detail-head">
        <div class="dh-left">
          <router-link class="op" to="/app/analyze-reports">← 返回报告列表</router-link>
          <h2 class="page-title" style="margin:10px 0 8px">{{ job.brand }}</h2>
          <div class="row">
            <span class="tag" v-for="k in (job.industry || [])" :key="k">{{ k }}</span>
            <span class="tag" :class="statusClass(job.task_status)">状态：{{ job.task_status }}</span>
          </div>
        </div>
        <div class="dh-right">
          <button class="btn btn-ghost" @click="exp" :disabled="!(job.reports||[]).length">导出 Excel</button>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:14px" v-if="job.kind==='pro' && entities.length>1">
      <h3 style="font-size:15px;margin-bottom:12px">品牌与竞品可见度对比</h3>
      <table class="table">
        <thead><tr><th>实体</th><th v-for="p in job.platforms" :key="p">{{ platformName(p) }}</th></tr></thead>
        <tbody>
          <tr v-for="en in entities" :key="en">
            <td><b>{{ en }}</b></td>
            <td v-for="p in job.platforms" :key="p">{{ scoreOf(en, p) }} 分</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="grid" style="grid-template-columns:repeat(2,1fr)" v-if="(job.reports || []).length">
      <div class="card" v-for="r in job.reports" :key="r.id">
        <div class="rp-head">
          <span class="rp-platform">{{ r.entity || job.brand }}</span>
          <span class="rp-chip">{{ platformName(r.platform) }} · <span class="tag" :class="r.mentioned ? 'ok' : 'fail'">{{ r.mentioned ? '已提及' : '未提及' }}</span></span>
        </div>
        <div class="rp-score">
          <div class="rp-score-num">{{ r.score ?? '-' }}</div>
          <div class="rp-score-label">可见度得分</div>
        </div>
        <p class="muted" v-if="r.snippet">{{ r.snippet }}</p>
        <ul class="rp-advices" v-if="(r.advice || []).length">
          <li v-for="(a, i) in r.advice" :key="i">{{ a }}</li>
        </ul>
      </div>
    </div>
    <div class="card" v-else>
      <div class="empty">报告暂未生成，任务运行完成后自动展示</div>
    </div>
  </div>
</template>
<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../../api'
const route = useRoute()
const job = ref(null)
let timer = null
function statusClass(s) {
  if (s === 'done' || s === 'success') return 'ok'
  if (s === 'failed' || s === 'error') return 'fail'
  return 'warn'
}
const entities = computed(() => {
  const out = [job.value?.brand]
  ;(job.value?.competitors || []).forEach((c) => out.push(c))
  return [...new Set(out.filter(Boolean))]
})
function platformName(id) {
  const names = { doubao: '豆包', yuanbao: '元宝', tongyi: '通义', wenxin: '文心', nami: '纳米', zhipu: '智谱' }
  return names[id] || id
}
function scoreOf(entity, plat) {
  const r = (job.value?.reports || []).find((x) => (x.entity || job.value.brand) === entity && x.platform === plat)
  return r ? r.score : '-'
}
async function load() {
  job.value = await api.get('/api/analyze/' + route.params.id)
  const s = job.value.task_status
  if (s === 'queued' || s === 'running') {
    if (!timer) timer = setInterval(load, 2500)
  } else if (timer) {
    clearInterval(timer)
    timer = null
  }
}
onMounted(load)
onBeforeUnmount(() => clearInterval(timer))
function exp() {
  const rows = [['平台','提及','得分','摘要','建议']].concat((job.value.reports||[]).map(r => [r.platform, r.mentioned, r.score, r.snippet, (r.advice||[]).join('|')]))
  const csv = rows.map(r => r.map(x => `"${String(x).replace(/"/g,'""')}"`).join(',')).join('\n')
  const blob = new Blob(['\ufeff'+csv], { type: 'text/csv' })
  const a = document.createElement('a')
  a.href = URL.createObjectURL(blob)
  a.download = `report-${job.value.id}.csv`
  a.click()
}
</script>
<style scoped>
.detail-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.dh-right { padding-top: 6px; }
.rp-head { display: flex; align-items: center; justify-content: space-between; }
.rp-platform { font-size: 17px; font-weight: 700; }
.rp-chip { font-size: 12px; color: var(--muted); }
.rp-score { margin: 14px 0 4px; display: flex; align-items: baseline; gap: 8px; }
.rp-score-num { font-size: 34px; font-weight: 800; color: #407bff; line-height: 1; }
.rp-score-label { font-size: 12px; color: var(--muted); }
.rp-advices { margin: 10px 0 0; padding-left: 18px; font-size: 13px; color: #374151; line-height: 1.9; }
</style>
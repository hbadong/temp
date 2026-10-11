<template>
  <div class="card">
    <h2 class="page-title">诊断报告</h2>
    <table class="table">
      <thead><tr><th>品牌</th><th>行业词</th><th>平台</th><th>类型</th><th>建议</th><th>金额</th><th>状态</th><th>时间</th><th></th></tr></thead>
      <tbody>
        <tr v-for="r in list" :key="r.id">
          <td>{{ r.brand }}</td>
          <td>{{ (r.industry || []).join(', ') }}</td>
          <td>{{ (r.platforms || []).map(p => platformName(p)).join('、') }}</td>
          <td>{{ r.kind === 'pro' ? 'Pro' : '标准' }}</td>
          <td>{{ r.need_advice ? '是' : '否' }}</td>
          <td>{{ (r.price/100).toFixed(2) }} 元</td>
          <td><span class="tag" :class="tagClass(r.task_status)">{{ r.task_status }}</span></td>
          <td class="muted">{{ fmt(r.created_at) }}</td>
          <td><router-link class="op" :to="'/app/analyze-reports/'+r.id">详情</router-link></td>
        </tr>
        <tr v-if="list.length === 0"><td colspan="9"><div class="empty">暂无诊断记录</div></td></tr>
      </tbody>
    </table>
  </div>
</template>
<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { api } from '../../api'
const list = ref([])
let timer = null
function tagClass(s) {
  if (s === 'done' || s === 'success') return 'ok'
  if (s === 'failed' || s === 'error') return 'fail'
  return 'warn'
}
function platformName(id) {
  const names = { doubao: '豆包', yuanbao: '元宝', tongyi: '通义', wenxin: '文心', nami: '纳米', zhipu: '智谱' }
  return names[id] || id
}
async function load() {
  const d = await api.get('/api/analyze')
  list.value = d.list
  const running = list.value.some((r) => r.task_status === 'queued' || r.task_status === 'running')
  if (!running) {
    clearInterval(timer)
    timer = null
  } else if (!timer) {
    timer = setInterval(load, 2500)
  }
}
onMounted(load)
onBeforeUnmount(() => clearInterval(timer))
function fmt(t) { return t ? new Date(t*1000).toLocaleString() : '' }
</script>

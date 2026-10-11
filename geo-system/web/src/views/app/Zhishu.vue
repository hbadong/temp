<template>
  <div class="card">
    <h2 class="page-title">关键词指数查询</h2>
    <form @submit.prevent="query" class="row">
      <input class="input" style="flex:1" v-model="keyword" placeholder="输入关键词开始查询" />
      <button class="btn" :disabled="loading">{{ loading ? '查询中…' : '查询（10点数）' }}</button>
      <button class="btn btn-ghost" type="button" @click="exp" :disabled="!rows.length">导出表格</button>
    </form>
    <div v-if="rows.length" style="margin-top:16px">
      <div class="toolbar">
        <span class="muted">共 {{ rows.length }} 条结果</span>
      </div>
      <table class="table">
        <thead><tr><th>关键词</th><th>搜索指数</th><th>移动指数</th><th>SEM竞价</th><th>PC日搜索量</th></tr></thead>
        <tbody>
          <tr v-for="r in rows" :key="r.keyword">
            <td>{{ r.keyword }}</td><td>{{ r.index }}</td><td>{{ r.mobile_index }}</td><td>{{ r.sem_recommend_price_avg }}</td><td>{{ r.bidword_pcpv }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="empty" v-else-if="!rows.length && queried">暂无查询结果</div>
    <div class="error" v-if="err" style="margin-top:10px">{{ err }}</div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { api } from '../../api'
const keyword = ref('人工智能')
const rows = ref([])
const err = ref('')
const loading = ref(false)
const queried = ref(false)
async function query() {
  err.value = ''
  if (!keyword.value.trim()) { err.value = '请输入关键词'; return }
  loading.value = true
  try { rows.value = await api.post('/api/tools/index', { keyword: keyword.value }); queried.value = true }
  catch (e) { err.value = e.message }
  finally { loading.value = false }
}
function exp() {
  const data = [['关键词','搜索指数','移动指数','SEM竞价','PC日搜索量']].concat(rows.value.map(r => [r.keyword, r.index, r.mobile_index, r.sem_recommend_price_avg, r.bidword_pcpv]))
  const csv = data.map(r => r.join(',')).join('\n')
  const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob(['\ufeff'+csv], { type: 'text/csv' })); a.download = '关键词指数.csv'; a.click()
}
</script>

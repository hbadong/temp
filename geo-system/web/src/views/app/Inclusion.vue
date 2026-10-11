<template>
  <div class="card">
    <div class="toolbar">
      <h2 class="page-title" style="margin:0">收录查询</h2>
    </div>
    <div class="form-item">
      <div class="row">
        <input class="input" style="flex:1" v-model="query" placeholder="关键词或 URL" />
        <input class="input" style="width:200px" v-model="target" placeholder="目标平台（可选）" />
        <button class="btn" @click="submit" :disabled="submitting">{{ submitting ? '查询中…' : '提交查询（10点）' }}</button>
      </div>
    </div>
    <p class="muted">提交后自动生成查询任务，完成后展示是否收录与截图</p>
    <div class="error" v-if="err" style="margin-bottom:10px">{{ err }}</div>
    <div class="okmsg" v-if="msg" style="margin-bottom:10px">{{ msg }}</div>

    <h3 style="font-size:15px;margin:18px 0 10px">查询记录</h3>
    <table class="table">
      <thead><tr><th>查询</th><th>目标</th><th>是否收录</th><th>摘要</th><th>截图</th><th>时间</th></tr></thead>
      <tbody>
        <tr v-for="r in list" :key="r.id">
          <td>{{ r.query }}</td><td>{{ r.target || '-' }}</td>
          <td><span class="tag" :class="r.included ? 'ok' : 'fail'">{{ r.included ? '已收录' : '未收录' }}</span></td>
          <td class="muted">{{ r.snippet }}</td>
          <td>
            <img v-if="r.screenshot_path" :src="r.screenshot_path" class="shot" @click="preview = r.screenshot_path" alt="截图" />
            <span v-else class="muted">-</span>
          </td>
          <td class="muted">{{ fmt(r.created_at) }}</td>
        </tr>
        <tr v-if="!list.length && !loading"><td colspan="6"><div class="empty">暂无查询记录</div></td></tr>
      </tbody>
    </table>

    <div v-if="preview" class="modal-overlay" @click.self="preview=''">
      <div class="modal" style="max-width:720px;background:#fff;text-align:center">
        <div class="modal-head"><h3>截图预览</h3><button class="modal-close" @click="preview=''">×</button></div>
        <img :src="preview" style="max-width:100%;max-height:70vh" />
      </div>
    </div>
  </div>
</template>
<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../../api'
const list = ref([])
const query = ref('')
const target = ref('')
const err = ref('')
const msg = ref('')
const submitting = ref(false)
const loading = ref(false)
const preview = ref('')
function fmt(t) { return t ? new Date(t * 1000).toLocaleString() : '' }
async function load() {
  loading.value = true
  err.value = ''
  try {
    const d = await api.get('/api/inclusion')
    list.value = d.list || []
  } catch (e) { err.value = e.message } finally { loading.value = false }
}
async function submit() {
  err.value = ''
  if (!query.value.trim()) { err.value = '请填写查询词或 URL'; return }
  submitting.value = true
  try {
    await api.post('/api/inclusion', { query: query.value, target: target.value })
    msg.value = '已提交查询，可在记录中查看结果'
    setTimeout(() => { msg.value = '' }, 2200)
    query.value = ''
    target.value = ''
    await load()
  } catch (e) { err.value = e.message } finally { submitting.value = false }
}
onMounted(load)
</script>
<style scoped>
.row { gap: 8px; }
.shot { width: 90px; height: 52px; object-fit: cover; border-radius: 6px; cursor: pointer; border: 1px solid var(--border); display: block; }
</style>
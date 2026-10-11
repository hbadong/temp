<template>
  <div class="card">
    <h2 class="page-title">任务监控</h2>
    <table class="table">
      <thead><tr><th>ID</th><th>用户</th><th>类型</th><th>状态</th><th>错误</th><th>时间</th></tr></thead>
      <tbody>
        <tr v-for="t in list" :key="t.id">
          <td>{{ t.id }}</td><td>{{ t.username }}</td><td>{{ t.type }}</td>
          <td><span class="tag" :class="tagClass(t.status)">{{ t.status }}</span></td>
          <td class="muted">{{ t.error_message || '-' }}</td>
          <td class="muted">{{ new Date(t.created_at*1000).toLocaleString() }}</td>
        </tr>
        <tr v-if="!list.length"><td colspan="6"><div class="empty">暂无任务记录</div></td></tr>
      </tbody>
    </table>
  </div>
</template>
<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../../api'
const list = ref([])
function tagClass(s) {
  if (['success', 'done', 'succeeded', 'published'].some((x) => String(s).includes(x))) return 'ok'
  if (['queued', 'running', 'pending'].some((x) => String(s).includes(x))) return 'warn'
  if (['failed', 'error', 'cancelled', 'cancel'].some((x) => String(s).includes(x))) return 'fail'
  return ''
}
onMounted(async () => { list.value = (await api.get('/api/admin/tasks')).list })
</script>

<template>
  <div class="card">
    <h2 class="page-title">实名审核</h2>
    <table class="table">
      <thead><tr><th>用户</th><th>类型</th><th>状态</th><th>时间</th><th></th></tr></thead>
      <tbody>
        <tr v-for="r in list" :key="r.id">
          <td>{{ r.username }}</td><td>{{ r.kind === 'personal' ? '个人' : '企业' }}</td>
          <td><span class="tag" :class="tagClass(r.status)">{{ r.status }}</span></td>
          <td class="muted">{{ new Date(r.created_at*1000).toLocaleString() }}</td>
          <td>
            <button class="btn" @click="act(r,'approve')" :disabled="busy" style="height:30px;font-size:13px">通过</button>
            <button class="btn btn-danger" @click="act(r,'reject')" :disabled="busy" style="height:30px;font-size:13px">驳回</button>
          </td>
        </tr>
        <tr v-if="!list.length"><td colspan="5"><div class="empty">暂无待审核记录</div></td></tr>
      </tbody>
    </table>
    <div class="error" v-if="err" style="margin-top:10px">{{ err }}</div>
  </div>
</template>
<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../../api'
const list = ref([])
const err = ref('')
const busy = ref(false)
function tagClass(s) {
  if (s === 'approved') return 'ok'
  if (s === 'pending') return 'warn'
  return 'fail'
}
async function load() { list.value = (await api.get('/api/admin/kyc')).list }
onMounted(load)
async function act(r, action) {
  err.value = ''
  const reason = action === 'reject' ? (prompt('驳回原因') || '资料不完整') : ''
  busy.value = true
  try {
    await api.post('/api/admin/kyc/' + r.id, { action, reason })
    await load()
  } catch (e) { err.value = e.message }
  finally { busy.value = false }
}
</script>

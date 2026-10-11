<template>
  <div class="card" style="max-width:480px">
    <h2 class="page-title">调账</h2>
    <div class="form-item"><label>用户名</label><input class="input" v-model="username" /></div>
    <div class="form-item"><label>类型</label>
      <select v-model="kind"><option value="money">余额(分)</option><option value="score">点数</option></select>
    </div>
    <div class="form-item"><label>金额（正加负减）</label><input class="input" v-model="amount" /></div>
    <div class="form-item"><label>备注</label><input class="input" v-model="remark" /></div>
    <button class="btn" @click="save" :disabled="saving">{{ saving ? '提交中…' : '提交' }}</button>
    <div class="error" v-if="err">{{ err }}</div>
    <div class="okmsg" v-if="msg">{{ msg }}</div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { api } from '../../api'
const username = ref('chinaz51008')
const kind = ref('money')
const amount = ref('1000')
const remark = ref('管理员调账')
const err = ref('')
const msg = ref('')
const saving = ref(false)
async function save() {
  err.value = ''
  saving.value = true
  try {
    const d = await api.post('/api/admin/adjust', { username: username.value, kind: kind.value, amount: parseInt(amount.value, 10), remark: remark.value })
    msg.value = `完成，余额 ${d.balance} 点数 ${d.score}`
  } catch (e) { err.value = e.message }
  finally { saving.value = false }
}
</script>

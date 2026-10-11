<template>
  <div class="card" style="max-width:480px">
    <h2 class="page-title">修改密码</h2>
    <div class="form-item"><label>用户名</label><input class="input" :value="me.username" disabled /></div>
    <div class="form-item"><label>原密码</label><input class="input" type="password" v-model="old_password" /></div>
    <div class="form-item"><label>新密码</label><input class="input" type="password" v-model="new_password" /></div>
    <button class="btn" @click="save" :disabled="saving">{{ saving ? '提交中…' : '提交' }}</button>
    <div class="error" v-if="err">{{ err }}</div>
    <div class="okmsg" v-if="msg">{{ msg }}</div>
  </div>
</template>
<script setup>
import { inject, ref } from 'vue'
import { api } from '../../api'
const me = inject('me')
const old_password = ref('')
const new_password = ref('')
const err = ref('')
const msg = ref('')
const saving = ref(false)
async function save() {
  err.value = ''
  saving.value = true
  try { await api.put('/api/me/password', { old_password: old_password.value, new_password: new_password.value }); msg.value = '已更新' }
  catch (e) { err.value = e.message }
  finally { saving.value = false }
}
</script>

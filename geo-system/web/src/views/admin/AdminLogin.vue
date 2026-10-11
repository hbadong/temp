<template>
  <div class="wrap">
    <div class="box">
      <div class="left">
        <h2>GEO 管理端</h2>
        <p>AI可见度优化平台<br>后台管理中心</p>
      </div>
      <div class="right">
        <h3>管理员登录</h3>
        <form @submit.prevent="go">
          <div class="form-item"><input class="input" v-model="username" placeholder="请输入管理账号" required /></div>
          <div class="form-item"><input class="input" type="password" v-model="password" placeholder="请输入登录密码" required /></div>
          <button class="btn" style="width:100%;margin:16px 0" :disabled="loading">{{ loading ? '登录中…' : '立即登录' }}</button>
          <div class="error" v-if="err">{{ err }}</div>
        </form>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../../api'
const router = useRouter()
const username = ref('admin')
const password = ref('admin123')
const err = ref('')
const loading = ref(false)
async function go() {
  if (!username.value || !password.value) { err.value = '请输入账号和密码'; return }
  err.value = ''
  loading.value = true
  try {
    await api.post('/api/admin/login', { username: username.value, password: password.value })
    router.push('/admin/kyc')
  } catch (e) { err.value = e.message }
  finally { loading.value = false }
}
</script>
<style scoped>
.wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(168deg, #efefff, #c9e0ff, #719aff); }
.box { width: 720px; display: flex; background: #fff; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 50px rgba(70, 84, 150, .18); }
.left { width: 40%; background: linear-gradient(145deg, #97c2ff, #6e72ff, #43d2f7); color: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 30px; text-align: center; }
.right { flex: 1; padding: 44px 40px; }
h2 { margin: 0 0 10px; }
h3 { margin-top: 0; border-left: 4px solid #9b59f2; padding-left: 10px; }
</style>
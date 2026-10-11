<template>
  <div class="wrap">
    <div class="box">
      <div class="left">
        <h2 class="sys-title">站长之家GEO</h2>
        <p class="sys-desc">借助AI之力<br>轻松解决难题　畅享智慧之旅</p>
      </div>
      <div class="right">
        <h3 class="login-title">账号登录</h3>
        <form @submit.prevent="onSubmit">
          <div class="input-item"><input class="input-lg" v-model="username" placeholder="请输入账号" required /></div>
          <div class="input-item"><input class="input-lg" type="password" v-model="password" placeholder="请输入登录密码" required /></div>
          <div class="login-row">
            <label class="check-text"><input type="checkbox" v-model="keeplogin" /> 记住登录状态</label>
          </div>
          <button class="sub-btn" :disabled="loading">立即登录</button>
          <div class="agree-box">
            <label><input type="checkbox" v-model="agree" /> 登录即同意 <router-link to="/agreement">《用户使用协议》</router-link></label>
          </div>
          <div class="error" style="text-align:center;margin-top:10px" v-if="err">{{ err }}</div>
        </form>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api'
const router = useRouter()
const username = ref('chinaz51008')
const password = ref('chinaz51008')
const keeplogin = ref(false)
const agree = ref(true)
const err = ref('')
const loading = ref(false)
async function onSubmit() {
  err.value = ''
  if (!agree.value) {
    err.value = '请阅读并勾选用户协议'
    return
  }
  loading.value = true
  try {
    await api.post('/api/auth/login', { username: username.value, password: password.value, keeplogin: keeplogin.value })
    router.push('/app/dashboard')
  } catch (e) {
    err.value = e.message
  } finally {
    loading.value = false
  }
}
</script>
<style scoped>
.wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background: linear-gradient(168deg, #efefff, #c9e0ff, #719aff, #d8dcff); }
.box { width: 780px; display: flex; background: #fff; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 40px rgba(120, 80, 220, .18); }
.left { width: 42%; background: linear-gradient(145deg, #97c2ff, #6e72ff, #43d2f7); color: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px 25px; text-align: center; }
.sys-title { font-size: 26px; font-weight: 700; margin: 0 0 12px; }
.sys-desc { font-size: 14px; opacity: .92; line-height: 1.9; margin: 0; }
.right { flex: 1; padding: 48px 44px; }
.login-title { font-size: 24px; color: #222; font-weight: 600; margin: 0 0 32px; border-left: 4px solid #9b59f2; padding-left: 12px; }
.input-item { margin-bottom: 20px; }
.input-lg {
  width: 100%; height: 52px; padding: 0 18px; border: 1px solid #e8e8e8; border-radius: 12px;
  font-size: 15px; color: #333; background: #f9faff; transition: all .25s;
}
.input-lg:focus { outline: none; border-color: #7b61ff; box-shadow: 0 0 0 3px rgba(123, 97, 255, .12); background: #fff; }
.input-lg::placeholder { color: #b4b8c3; }
.login-row { display: flex; margin: 2px 0 22px; font-size: 14px; }
.check-text { color: #666; display: flex; align-items: center; gap: 6px; cursor: pointer; }
.check-text input, .agree-box input { accent-color: #407bff; }
.sub-btn {
  width: 100%; height: 52px; border: none; border-radius: 12px;
  background: linear-gradient(90deg, #407bff, #9b59f2); color: #fff; font-size: 16px; font-weight: 600; cursor: pointer;
  transition: all .25s;
}
.sub-btn:hover { opacity: .92; transform: translateY(-2px); box-shadow: 0 6px 18px rgba(123, 97, 255, .28); }
.sub-btn:disabled { opacity: .6; cursor: not-allowed; transform: none; box-shadow: none; }
.agree-box { text-align: center; margin-top: 20px; font-size: 13px; color: #777; }
.agree-box a { color: #407bff; }
.agree-box a:hover { text-decoration: underline; }
</style>

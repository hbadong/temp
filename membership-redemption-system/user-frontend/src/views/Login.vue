<template>
  <div class="login-container">
    <div class="login-header">
      <h1>会员兑换</h1>
      <p>移动号码兑换视频会员</p>
    </div>
    
    <div class="login-form">
      <div class="form-item">
        <input 
          v-model="phone" 
          type="tel" 
          placeholder="请输入手机号"
          maxlength="11"
        />
      </div>
      
      <div class="form-item code-item">
        <input 
          v-model="code" 
          type="text" 
          placeholder="验证码"
          maxlength="6"
        />
        <button 
          class="send-code-btn" 
          @click="sendCode"
          :disabled="countdown > 0"
        >
          {{ countdown > 0 ? `${countdown}s` : '获取验证码' }}
        </button>
      </div>
      
      <button class="login-btn" @click="handleLogin" :loading="loading">
        登录
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { userApi } from '../api/user'
import { showToast } from 'vant'

const router = useRouter()
const phone = ref('')
const code = ref('')
const countdown = ref(0)
const loading = ref(false)

const sendCode = async () => {
  if (!/^1[3-9]\d{9}$/.test(phone.value)) {
    showToast('请输入正确的手机号')
    return
  }
  
  try {
    await userApi.sendCode(phone.value)
    showToast('验证码已发送')
    countdown.value = 60
    const timer = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) {
        clearInterval(timer)
      }
    }, 1000)
  } catch (error: any) {
    showToast(error.message || '发送失败')
  }
}

const handleLogin = async () => {
  if (!/^1[3-9]\d{9}$/.test(phone.value)) {
    showToast('请输入正确的手机号')
    return
  }
  
  if (code.value.length !== 6) {
    showToast('请输入6位验证码')
    return
  }
  
  loading.value = true
  
  try {
    const res = await userApi.login(phone.value, code.value)
    localStorage.setItem('token', res.data.token)
    localStorage.setItem('user', JSON.stringify(res.data.user))
    showToast('登录成功')
    router.push('/home')
  } catch (error: any) {
    showToast(error.message || '登录失败')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

.login-header {
  text-align: center;
  color: white;
  margin-bottom: 40px;
}

.login-header h1 {
  font-size: 32px;
  margin-bottom: 10px;
}

.login-header p {
  font-size: 14px;
  opacity: 0.9;
}

.login-form {
  width: 100%;
  max-width: 320px;
  background: white;
  border-radius: 12px;
  padding: 30px 20px;
}

.form-item {
  margin-bottom: 20px;
}

.form-item input {
  width: 100%;
  height: 48px;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 0 15px;
  font-size: 16px;
}

.form-item input:focus {
  outline: none;
  border-color: #667eea;
}

.code-item {
  display: flex;
  gap: 10px;
}

.code-item input {
  flex: 1;
}

.send-code-btn {
  width: 110px;
  height: 48px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  white-space: nowrap;
}

.send-code-btn:disabled {
  background: #ccc;
}

.login-btn {
  width: 100%;
  height: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
}
</style>

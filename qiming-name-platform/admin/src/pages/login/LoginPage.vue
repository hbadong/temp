<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-header">
        <h1>起名网</h1>
        <p>管理后台</p>
      </div>
      
      <a-form
        :model="form"
        :rules="rules"
        @finish="onSubmit"
        class="login-form"
      >
        <a-form-item name="username">
          <a-input v-model:value="form.username" placeholder="用户名" size="large">
            <template #prefix>
              <user-outlined />
            </template>
          </a-input>
        </a-form-item>
        
        <a-form-item name="password">
          <a-input-password v-model:value="form.password" placeholder="密码" size="large">
            <template #prefix>
              <lock-outlined />
            </template>
          </a-input-password>
        </a-form-item>
        
        <a-form-item>
          <a-checkbox v-model:checked="form.remember">记住密码</a-checkbox>
        </a-form-item>
        
        <a-form-item>
          <a-button type="primary" html-type="submit" size="large" block :loading="loading">
            登录
          </a-button>
        </a-form-item>
      </a-form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { UserOutlined, LockOutlined } from '@ant-design/icons-vue'

const router = useRouter()
const loading = ref(false)

const form = reactive({
  username: '',
  password: '',
  remember: false
})

const rules = {
  username: [{ required: true, message: '请输入用户名' }],
  password: [{ required: true, message: '请输入密码' }]
}

const onSubmit = async () => {
  loading.value = true
  
  try {
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    if (form.username === 'admin' && form.password === 'admin123') {
      localStorage.setItem('adminToken', 'mock_token_12345')
      message.success('登录成功')
      router.push('/')
    } else {
      message.error('用户名或密码错误')
    }
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #d4380d 0%, #ff4d4f 100%);
}

.login-container {
  width: 400px;
  padding: 48px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.2);
}

.login-header {
  text-align: center;
  margin-bottom: 32px;

  h1 {
    font-size: 28px;
    color: #d4380d;
    margin-bottom: 8px;
  }

  p {
    color: #666;
  }
}

.login-form {
  .ant-btn {
    margin-top: 16px;
  }
}
</style>

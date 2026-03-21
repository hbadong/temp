<template>
  <div class="card-container">
    <div class="header">
      <span class="back" @click="goBack">&lt;</span>
      <h2>卡密充值</h2>
    </div>
    
    <div class="card-form">
      <div class="form-item">
        <input 
          v-model="cardNo" 
          type="text" 
          placeholder="请输入16位卡密"
          maxlength="20"
        />
      </div>
      
      <div class="form-item">
        <input 
          v-model="targetAccount" 
          type="text" 
          placeholder="请输入要充值的会员账号"
        />
      </div>
      
      <button class="submit-btn" @click="handleSubmit" :loading="loading">
        立即充值
      </button>
    </div>
    
    <div class="tips">
      <h4>充值说明</h4>
      <ul>
        <li>请输入您购买的卡密号码</li>
        <li>卡密一旦使用，无法退款</li>
        <li>充值成功后请妥善保管凭证</li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { userApi } from '../api/user'
import { showToast } from 'vant'

const router = useRouter()
const cardNo = ref('')
const targetAccount = ref('')
const loading = ref(false)

const goBack = () => {
  router.back()
}

const handleSubmit = async () => {
  if (cardNo.value.length < 12) {
    showToast('请输入正确的卡密')
    return
  }
  
  if (!targetAccount.value) {
    showToast('请输入要充值的会员账号')
    return
  }
  
  loading.value = true
  
  try {
    await userApi.exchangeCard(cardNo.value, targetAccount.value)
    showToast('充值成功')
    setTimeout(() => {
      router.push('/orders')
    }, 1500)
  } catch (error: any) {
    showToast(error.message || '充值失败')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.card-container {
  min-height: 100vh;
  background: #f5f5f5;
}

.header {
  background: white;
  padding: 15px 20px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.back {
  font-size: 24px;
  cursor: pointer;
}

.header h2 {
  font-size: 18px;
}

.card-form {
  background: white;
  margin: 10px;
  padding: 20px;
  border-radius: 12px;
}

.form-item {
  margin-bottom: 15px;
}

.form-item input {
  width: 100%;
  height: 48px;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 0 15px;
  font-size: 16px;
}

.submit-btn {
  width: 100%;
  height: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  margin-top: 10px;
}

.tips {
  background: white;
  margin: 10px;
  padding: 20px;
  border-radius: 12px;
}

.tips h4 {
  font-size: 15px;
  margin-bottom: 10px;
}

.tips ul {
  list-style: none;
  font-size: 13px;
  color: #666;
}

.tips li {
  margin-bottom: 8px;
  padding-left: 15px;
  position: relative;
}

.tips li::before {
  content: '•';
  position: absolute;
  left: 0;
  color: #999;
}
</style>

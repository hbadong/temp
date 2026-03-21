<template>
  <div class="exchange-container">
    <div class="header">
      <span class="back" @click="goBack">&lt;</span>
      <h2>手机兑换</h2>
    </div>
    
    <div class="products-section">
      <h3>选择套餐</h3>
      <div class="products-grid">
        <div 
          v-for="product in products" 
          :key="product.id"
          class="product-item"
          :class="{ selected: selectedProduct?.id === product.id }"
          @click="selectProduct(product)"
        >
          <div class="product-name">{{ product.name }}</div>
          <div class="product-price">¥{{ product.price }}</div>
        </div>
      </div>
    </div>
    
    <div class="form-section" v-if="selectedProduct">
      <div class="form-item">
        <label>充值手机号</label>
        <input 
          v-model="phone" 
          type="tel" 
          placeholder="请输入手机号"
          maxlength="11"
        />
      </div>
      
      <div class="form-item code-item">
        <label>验证码</label>
        <input 
          v-model="code" 
          type="text" 
          placeholder="验证码"
          maxlength="6"
        />
        <button @click="sendCode" :disabled="countdown > 0">
          {{ countdown > 0 ? `${countdown}s` : '获取验证码' }}
        </button>
      </div>
      
      <div class="order-summary">
        <div class="summary-item">
          <span>套餐</span>
          <span>{{ selectedProduct.name }}</span>
        </div>
        <div class="summary-item">
          <span>金额</span>
          <span class="amount">¥{{ selectedProduct.price }}</span>
        </div>
      </div>
      
      <button class="submit-btn" @click="handleSubmit" :loading="loading">
        确认兑换
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { userApi } from '../api/user'
import { showToast } from 'vant'

const router = useRouter()
const products = ref<any[]>([])
const selectedProduct = ref<any>(null)
const phone = ref('')
const code = ref('')
const countdown = ref(0)
const loading = ref(false)

onMounted(async () => {
  try {
    const res = await userApi.getProducts()
    products.value = res.data || []
  } catch (error) {
    showToast('加载失败')
  }
})

const goBack = () => {
  router.back()
}

const selectProduct = (product: any) => {
  selectedProduct.value = product
}

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
      if (countdown.value <= 0) clearInterval(timer)
    }, 1000)
  } catch (error: any) {
    showToast(error.message || '发送失败')
  }
}

const handleSubmit = async () => {
  if (!selectedProduct.value) {
    showToast('请选择套餐')
    return
  }
  
  if (!/^1[3-9]\d{9}$/.test(phone.value)) {
    showToast('请输入正确的手机号')
    return
  }
  
  if (code.value.length !== 6) {
    showToast('请输入验证码')
    return
  }
  
  loading.value = true
  
  try {
    await userApi.exchangeMobile(selectedProduct.value.id, phone.value, code.value)
    showToast('兑换成功')
    setTimeout(() => {
      router.push('/orders')
    }, 1500)
  } catch (error: any) {
    showToast(error.message || '兑换失败')
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.exchange-container {
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

.products-section {
  background: white;
  margin: 10px;
  padding: 15px;
  border-radius: 12px;
}

.products-section h3 {
  font-size: 15px;
  margin-bottom: 15px;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}

.product-item {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 12px;
  text-align: center;
  cursor: pointer;
}

.product-item.selected {
  border-color: #667eea;
  background: #f0f0ff;
}

.product-name {
  font-size: 14px;
  margin-bottom: 5px;
}

.product-price {
  font-size: 16px;
  color: #ff5500;
  font-weight: bold;
}

.form-section {
  background: white;
  margin: 10px;
  padding: 15px;
  border-radius: 12px;
}

.form-item {
  margin-bottom: 15px;
}

.form-item label {
  display: block;
  font-size: 14px;
  margin-bottom: 8px;
  color: #333;
}

.form-item input {
  width: 100%;
  height: 44px;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 0 15px;
}

.code-item {
  display: flex;
  gap: 10px;
  align-items: center;
}

.code-item input {
  flex: 1;
}

.code-item button {
  width: 100px;
  height: 44px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
}

.order-summary {
  background: #f5f5f5;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 15px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 14px;
}

.summary-item:last-child {
  margin-bottom: 0;
}

.amount {
  color: #ff5500;
  font-weight: bold;
}

.submit-btn {
  width: 100%;
  height: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
}
</style>

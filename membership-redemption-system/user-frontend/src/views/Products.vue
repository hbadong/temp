<template>
  <div class="products-container">
    <div class="header">
      <span class="back" @click="goBack">&lt;</span>
      <h2>{{ platformName }}会员</h2>
    </div>
    
    <div class="products-list">
      <div 
        v-for="product in products" 
        :key="product.id"
        class="product-card"
      >
        <div class="product-info">
          <h3>{{ product.name }}</h3>
          <p>{{ product.durationDays }}天</p>
        </div>
        <div class="product-price">
          <span class="price">¥{{ product.price }}</span>
          <button class="buy-btn" @click="handleBuy(product)">立即购买</button>
        </div>
      </div>
      
      <div v-if="loading" class="loading">加载中...</div>
      <div v-if="!loading && products.length === 0" class="empty">暂无套餐</div>
    </div>
    
    <van-popup v-model:show="showBuyModal" position="bottom" round>
      <div class="buy-modal">
        <h3>确认购买</h3>
        <div class="product-detail">
          <p>套餐：{{ selectedProduct?.name }}</p>
          <p>价格：¥{{ selectedProduct?.price }}</p>
        </div>
        <div class="form-item">
          <input 
            v-model="targetPhone" 
            type="tel" 
            placeholder="请输入充值手机号"
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
          <button @click="sendCode" :disabled="countdown > 0">
            {{ countdown > 0 ? `${countdown}s` : '获取验证码' }}
          </button>
        </div>
        <button class="confirm-btn" @click="confirmBuy">确认兑换</button>
      </div>
    </van-popup>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { userApi } from '../api/user'
import { showToast, showConfirmDialog } from 'vant'

const route = useRoute()
const router = useRouter()
const products = ref<any[]>([])
const loading = ref(false)
const showBuyModal = ref(false)
const selectedProduct = ref<any>(null)
const targetPhone = ref('')
const code = ref('')
const countdown = ref(0)

const platformName = ref('')

onMounted(async () => {
  const platform = route.query.platform as string
  await loadProducts(platform)
})

const loadProducts = async (platform: string) => {
  loading.value = true
  try {
    const res = await userApi.getProducts(platform)
    products.value = res.data || []
    const platformMap: Record<string, string> = {
      iqiyi: '爱奇艺',
      youku: '优酷',
      tencent: '腾讯视频'
    }
    platformName.value = platformMap[platform] || platform
  } catch (error) {
    showToast('加载失败')
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.back()
}

const handleBuy = (product: any) => {
  selectedProduct.value = product
  showBuyModal.value = true
}

const sendCode = async () => {
  if (!/^1[3-9]\d{9}$/.test(targetPhone.value)) {
    showToast('请输入正确的手机号')
    return
  }
  
  try {
    await userApi.sendCode(targetPhone.value)
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

const confirmBuy = async () => {
  if (!/^1[3-9]\d{9}$/.test(targetPhone.value)) {
    showToast('请输入正确的手机号')
    return
  }
  
  if (code.value.length !== 6) {
    showToast('请输入验证码')
    return
  }
  
  try {
    await userApi.exchangeMobile(selectedProduct.value.id, targetPhone.value, code.value)
    showToast('兑换成功')
    showBuyModal.value = false
    router.push('/orders')
  } catch (error: any) {
    showToast(error.message || '兑换失败')
  }
}
</script>

<style scoped>
.products-container {
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

.products-list {
  padding: 10px;
}

.product-card {
  background: white;
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.product-info h3 {
  font-size: 16px;
  margin-bottom: 5px;
}

.product-info p {
  font-size: 13px;
  color: #999;
}

.product-price {
  text-align: right;
}

.price {
  font-size: 20px;
  color: #ff5500;
  font-weight: bold;
  display: block;
  margin-bottom: 8px;
}

.buy-btn {
  background: #ff5500;
  color: white;
  border: none;
  padding: 6px 15px;
  border-radius: 15px;
  font-size: 13px;
}

.loading, .empty {
  text-align: center;
  padding: 40px;
  color: #999;
}

.buy-modal {
  padding: 20px;
}

.buy-modal h3 {
  text-align: center;
  margin-bottom: 20px;
}

.product-detail {
  background: #f5f5f5;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 15px;
}

.product-detail p {
  margin-bottom: 5px;
  font-size: 14px;
}

.form-item {
  margin-bottom: 15px;
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
}

.code-item input {
  flex: 1;
}

.code-item button {
  width: 100px;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 8px;
}

.confirm-btn {
  width: 100%;
  height: 44px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
}
</style>

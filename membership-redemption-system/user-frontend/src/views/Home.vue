<template>
  <div class="home-container">
    <div class="header">
      <div class="user-info">
        <span>您好，{{ userPhone }}</span>
      </div>
    </div>
    
    <div class="banner">
      <div class="banner-content">
        <h2>视频会员兑换</h2>
        <p>支持爱奇艺、优酷、腾讯视频</p>
      </div>
    </div>
    
    <div class="platforms">
      <div 
        v-for="platform in platforms" 
        :key="platform.id"
        class="platform-item"
        @click="goToProducts(platform.id)"
      >
        <div class="platform-icon" :style="{ background: platform.color }">
          {{ platform.name.charAt(0) }}
        </div>
        <span class="platform-name">{{ platform.name }}</span>
      </div>
    </div>
    
    <div class="quick-actions">
      <div class="action-item" @click="goToExchange">
        <div class="action-icon mobile-icon"></div>
        <span>手机兑换</span>
      </div>
      <div class="action-item" @click="goToCard">
        <div class="action-icon card-icon"></div>
        <span>卡密充值</span>
      </div>
      <div class="action-item" @click="goToOrders">
        <div class="action-icon order-icon"></div>
        <span>订单查询</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const user = ref<any>(null)

const platforms = [
  { id: 'iqiyi', name: '爱奇艺', color: '#00be06' },
  { id: 'youku', name: '优酷', color: '#00d1b2' },
  { id: 'tencent', name: '腾讯视频', color: '#ff5500' }
]

const userPhone = computed(() => {
  if (user.value?.phone) {
    return user.value.phone.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2')
  }
  return '用户'
})

onMounted(() => {
  const userStr = localStorage.getItem('user')
  if (userStr) {
    user.value = JSON.parse(userStr)
  }
})

const goToProducts = (platform: string) => {
  router.push({ path: '/products', query: { platform } })
}

const goToExchange = () => {
  router.push('/exchange')
}

const goToCard = () => {
  router.push('/card')
}

const goToOrders = () => {
  router.push('/orders')
}
</script>

<style scoped>
.home-container {
  min-height: 100vh;
  background: #f5f5f5;
}

.header {
  background: white;
  padding: 15px 20px;
  display: flex;
  justify-content: flex-end;
}

.user-info {
  font-size: 14px;
  color: #666;
}

.banner {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 30px 20px;
  margin: 10px;
  border-radius: 12px;
}

.banner h2 {
  font-size: 22px;
  margin-bottom: 8px;
}

.banner p {
  font-size: 14px;
  opacity: 0.9;
}

.platforms {
  display: flex;
  justify-content: space-around;
  padding: 20px;
  background: white;
  margin: 10px;
  border-radius: 12px;
}

.platform-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.platform-icon {
  width: 50px;
  height: 50px;
  border-radius: 25px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 24px;
  font-weight: bold;
}

.platform-name {
  font-size: 12px;
  color: #333;
}

.quick-actions {
  display: flex;
  justify-content: space-around;
  padding: 20px;
  background: white;
  margin: 10px;
  border-radius: 12px;
}

.action-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.action-icon {
  width: 45px;
  height: 45px;
  border-radius: 10px;
}

.mobile-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.card-icon {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.order-icon {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.action-item span {
  font-size: 12px;
  color: #333;
}
</style>

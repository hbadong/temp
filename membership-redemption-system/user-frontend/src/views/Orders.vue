<template>
  <div class="orders-container">
    <div class="header">
      <span class="back" @click="goBack">&lt;</span>
      <h2>我的订单</h2>
    </div>
    
    <div class="orders-list">
      <div 
        v-for="order in orders" 
        :key="order.id"
        class="order-card"
      >
        <div class="order-header">
          <span class="order-no">订单号：{{ order.orderNo }}</span>
          <span class="order-status" :class="getStatusClass(order.status)">
            {{ getStatusText(order.status) }}
          </span>
        </div>
        <div class="order-info">
          <div class="product-name">{{ order.productName || order.product_name }}</div>
          <div class="product-platform">{{ getPlatformText(order.platform) }}</div>
        </div>
        <div class="order-footer">
          <span class="order-amount">¥{{ order.amount }}</span>
          <span class="order-time">{{ formatTime(order.createdAt) }}</span>
        </div>
      </div>
      
      <div v-if="loading" class="loading">加载中...</div>
      <div v-if="!loading && orders.length === 0" class="empty">暂无订单</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { userApi } from '../api/user'
import dayjs from 'dayjs'

const router = useRouter()
const orders = ref<any[]>([])
const loading = ref(false)

onMounted(async () => {
  await loadOrders()
})

const loadOrders = async () => {
  loading.value = true
  try {
    const res = await userApi.getOrders()
    orders.value = res.data?.list || []
  } catch (error) {
    console.error('加载订单失败', error)
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.back()
}

const getStatusClass = (status: number) => {
  const classes: Record<number, string> = {
    0: 'status-pending',
    1: 'status-processing',
    2: 'status-success',
    3: 'status-failed'
  }
  return classes[status] || ''
}

const getStatusText = (status: number) => {
  const texts: Record<number, string> = {
    0: '待处理',
    1: '处理中',
    2: '成功',
    3: '失败'
  }
  return texts[status] || '未知'
}

const getPlatformText = (platform: string) => {
  const texts: Record<string, string> = {
    iqiyi: '爱奇艺',
    youku: '优酷',
    tencent: '腾讯视频'
  }
  return texts[platform] || platform
}

const formatTime = (time: string) => {
  return dayjs(time).format('YYYY-MM-DD HH:mm')
}
</script>

<style scoped>
.orders-container {
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

.orders-list {
  padding: 10px;
}

.order-card {
  background: white;
  border-radius: 12px;
  padding: 15px;
  margin-bottom: 10px;
}

.order-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.order-no {
  font-size: 12px;
  color: #999;
}

.order-status {
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 10px;
}

.status-pending {
  background: #fff7e6;
  color: #faad14;
}

.status-processing {
  background: #e6f7ff;
  color: #1890ff;
}

.status-success {
  background: #f6ffed;
  color: #52c41a;
}

.status-failed {
  background: #fff1f0;
  color: #ff4d4f;
}

.order-info {
  margin-bottom: 10px;
}

.product-name {
  font-size: 15px;
  font-weight: bold;
  margin-bottom: 5px;
}

.product-platform {
  font-size: 13px;
  color: #666;
}

.order-footer {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.order-amount {
  color: #ff5500;
  font-weight: bold;
}

.order-time {
  color: #999;
}

.loading, .empty {
  text-align: center;
  padding: 40px;
  color: #999;
}
</style>

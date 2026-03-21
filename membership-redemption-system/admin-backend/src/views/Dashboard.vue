<template>
  <div class="dashboard">
    <h2>仪表盘</h2>
    
    <el-row :gutter="20" class="stats-row">
      <el-col :span="6">
        <div class="stat-card">
          <div class="stat-icon orders-icon"></div>
          <div class="stat-info">
            <div class="stat-value">{{ stats.todayOrders }}</div>
            <div class="stat-label">今日订单</div>
          </div>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="stat-card">
          <div class="stat-icon sales-icon"></div>
          <div class="stat-info">
            <div class="stat-value">¥{{ stats.todaySales }}</div>
            <div class="stat-label">今日销售额</div>
          </div>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="stat-card">
          <div class="stat-icon users-icon"></div>
          <div class="stat-info">
            <div class="stat-value">{{ stats.totalUsers }}</div>
            <div class="stat-label">总用户数</div>
          </div>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="stat-card">
          <div class="stat-icon cards-icon"></div>
          <div class="stat-info">
            <div class="stat-value">{{ stats.totalCards }}</div>
            <div class="stat-label">可用卡密</div>
          </div>
        </div>
      </el-col>
    </el-row>
    
    <el-card class="recent-orders">
      <template #header>
        <span>最近订单</span>
      </template>
      <el-table :data="stats.recentOrders" style="width: 100%">
        <el-table-column prop="order_no" label="订单号" width="180" />
        <el-table-column prop="user_phone" label="用户手机" width="120" />
        <el-table-column prop="product_name" label="套餐" width="120" />
        <el-table-column prop="amount" label="金额" width="80">
          <template #default="{ row }">
            ¥{{ row.amount }}
          </template>
        </el-table-column>
        <el-table-column prop="status" label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间">
          <template #default="{ row }">
            {{ formatTime(row.created_at) }}
          </template>
        </el-table-column>
      </el-table>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import dayjs from 'dayjs'

const stats = ref<any>({
  todayOrders: 0,
  todaySales: 0,
  totalUsers: 0,
  totalCards: 0,
  recentOrders: []
})

onMounted(async () => {
  try {
    const res = await axios.get('/api/v1/admin/stats/dashboard')
    stats.value = res.data.data
  } catch (error) {
    console.error('加载失败', error)
  }
})

const getStatusType = (status: number) => {
  const types: Record<number, string> = {
    0: 'info',
    1: 'warning',
    2: 'success',
    3: 'danger'
  }
  return types[status] || 'info'
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

const formatTime = (time: string) => {
  return dayjs(time).format('YYYY-MM-DD HH:mm')
}
</script>

<style scoped>
.dashboard h2 {
  margin-bottom: 20px;
}

.stats-row {
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
}

.orders-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.sales-icon {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.users-icon {
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.cards-icon {
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-value {
  font-size: 24px;
  font-weight: bold;
  color: #333;
}

.stat-label {
  font-size: 14px;
  color: #999;
}

.recent-orders {
  margin-top: 20px;
}
</style>

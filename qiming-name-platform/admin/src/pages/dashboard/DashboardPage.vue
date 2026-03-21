<template>
  <div class="dashboard-page">
    <h1>仪表盘</h1>
    
    <a-row :gutter="[24, 24]" class="stats-row">
      <a-col :span="6">
        <a-card>
          <a-statistic
            title="总访问量"
            :value="stats.totalVisits"
            :prefix="h(GlobalOutlined, { style: { color: '#1890ff' } })"
          />
        </a-card>
      </a-col>
      <a-col :span="6">
        <a-card>
          <a-statistic
            title="注册用户"
            :value="stats.totalUsers"
            :prefix="h(UserOutlined, { style: { color: '#52c41a' } })"
          />
        </a-card>
      </a-col>
      <a-col :span="6">
        <a-card>
          <a-statistic
            title="今日订单"
            :value="stats.todayOrders"
            :prefix="h(ShoppingCartOutlined, { style: { color: '#faad14' } })"
          />
        </a-card>
      </a-col>
      <a-col :span="6">
        <a-card>
          <a-statistic
            title="总收入"
            :value="stats.totalRevenue"
            :prefix="h(DollarOutlined, { style: { color: '#f5222d' } })"
            :precision="2"
            prefix="¥"
          />
        </a-card>
      </a-col>
    </a-row>

    <a-row :gutter="[24, 24]" style="margin-top: 24px">
      <a-col :span="12">
        <a-card title="访问趋势">
          <div ref="visitChartRef" style="height: 300px"></div>
        </a-card>
      </a-col>
      <a-col :span="12">
        <a-card title="热门服务">
          <a-table :columns="serviceColumns" :data-source="hotServices" :pagination="false" size="small">
            <template #bodyCell="{ column, record }">
              <template v-if="column.key === 'service'">
                {{ record.service }}
              </template>
              <template v-else-if="column.key === 'count'">
                {{ record.count }}次
              </template>
            </template>
          </a-table>
        </a-card>
      </a-col>
    </a-row>

    <a-row :gutter="[24, 24]" style="margin-top: 24px">
      <a-col :span="12">
        <a-card title="最新订单">
          <a-table :columns="orderColumns" :data-source="recentOrders" :pagination="false" size="small">
            <template #bodyCell="{ column, record }">
              <template v-if="column.key === 'orderNo'">
                <a @click="viewOrder(record)">{{ record.orderNo }}</a>
              </template>
              <template v-else-if="column.key === 'status'">
                <a-tag :color="getStatusColor(record.status)">{{ getStatusText(record.status) }}</a-tag>
              </template>
            </template>
          </a-table>
        </a-card>
      </a-col>
      <a-col :span="12">
        <a-card title="最新用户">
          <a-table :columns="userColumns" :data-source="recentUsers" :pagination="false" size="small" />
        </a-card>
      </a-col>
    </a-row>
  </div>
</template>

<script setup>
import { ref, reactive, h, onMounted } from 'vue'
import {
  GlobalOutlined,
  UserOutlined,
  ShoppingCartOutlined,
  DollarOutlined
} from '@ant-design/icons-vue'

const visitChartRef = ref(null)

const stats = reactive({
  totalVisits: 50000000,
  totalUsers: 2000000,
  todayOrders: 156,
  totalRevenue: 8900000
})

const serviceColumns = [
  { title: '服务', dataIndex: 'service', key: 'service' },
  { title: '使用次数', dataIndex: 'count', key: 'count' }
]

const hotServices = [
  { service: '宝宝起名', count: 125600 },
  { service: '八字起名', count: 89600 },
  { service: '姓名测试', count: 76800 },
  { service: '诗词起名', count: 54300 },
  { service: '周易起名', count: 32100 }
]

const orderColumns = [
  { title: '订单号', dataIndex: 'orderNo', key: 'orderNo' },
  { title: '用户', dataIndex: 'username', key: 'username' },
  { title: '服务类型', dataIndex: 'serviceType', key: 'serviceType' },
  { title: '金额', dataIndex: 'amount', key: 'amount' },
  { title: '状态', dataIndex: 'status', key: 'status' }
]

const recentOrders = ref([
  { orderNo: 'ORD20260321001', username: '张三', serviceType: '宝宝起名', amount: 99, status: 2 },
  { orderNo: 'ORD20260321002', username: '李四', serviceType: '八字起名', amount: 199, status: 2 },
  { orderNo: 'ORD20260321003', username: '王五', serviceType: '姓名测试', amount: 29, status: 1 }
])

const userColumns = [
  { title: '用户名', dataIndex: 'username', key: 'username' },
  { title: '手机号', dataIndex: 'phone', key: 'phone' },
  { title: '注册时间', dataIndex: 'createdAt', key: 'createdAt' }
]

const recentUsers = ref([
  { username: 'user_001', phone: '138****1234', createdAt: '2026-03-21 10:30' },
  { username: 'user_002', phone: '139****5678', createdAt: '2026-03-21 09:15' },
  { username: 'user_003', phone: '136****9012', createdAt: '2026-03-21 08:45' }
])

const getStatusColor = (status) => {
  const colors = { 1: 'blue', 2: 'green', 3: 'orange', 4: 'green', 5: 'red' }
  return colors[status] || 'default'
}

const getStatusText = (status) => {
  const texts = { 1: '待付款', 2: '已付款', 3: '服务中', 4: '已完成', 5: '已取消' }
  return texts[status] || '未知'
}

const viewOrder = (order) => {
  console.log('查看订单:', order)
}
</script>

<style lang="scss" scoped>
.dashboard-page {
  h1 {
    margin-bottom: 24px;
    font-size: 24px;
  }
}

.stats-row {
  margin-bottom: 24px;
}
</style>

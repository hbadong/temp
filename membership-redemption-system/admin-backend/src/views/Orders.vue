<template>
  <div class="orders-page">
    <el-card>
      <template #header>
        <span>订单管理</span>
      </template>
      
      <div class="filter-row">
        <el-input 
          v-model="filter.orderNo" 
          placeholder="订单号" 
          style="width: 180px"
          clearable
        />
        <el-input 
          v-model="filter.userPhone" 
          placeholder="用户手机" 
          style="width: 140px"
          clearable
        />
        <el-select v-model="filter.status" placeholder="状态" style="width: 120px" clearable>
          <el-option label="待处理" :value="0" />
          <el-option label="处理中" :value="1" />
          <el-option label="成功" :value="2" />
          <el-option label="失败" :value="3" />
        </el-select>
        <el-date-picker
          v-model="filter.dateRange"
          type="daterange"
          range-separator="至"
          start-placeholder="开始日期"
          end-placeholder="结束日期"
          style="width: 240px"
        />
        <el-button type="primary" @click="loadOrders">搜索</el-button>
      </div>
      
      <el-table :data="orders" v-loading="loading" style="margin-top: 15px">
        <el-table-column prop="order_no" label="订单号" width="180" />
        <el-table-column prop="user_phone" label="用户手机" width="120" />
        <el-table-column prop="product_name" label="套餐" width="120" />
        <el-table-column prop="platform" label="平台" width="80">
          <template #default="{ row }">
            {{ getPlatformText(row.platform) }}
          </template>
        </el-table-column>
        <el-table-column prop="type" label="类型" width="80">
          <template #default="{ row }">
            {{ row.type === 1 ? '手机兑换' : '卡密充值' }}
          </template>
        </el-table-column>
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
        <el-table-column prop="created_at" label="创建时间" width="160">
          <template #default="{ row }">
            {{ formatTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="100">
          <template #default="{ row }">
            <el-button 
              type="primary" 
              size="small" 
              @click="handleRetry(row)"
              :disabled="row.status !== 3"
            >
              重试
            </el-button>
          </template>
        </el-table-column>
      </el-table>
      
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.pageSize"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next"
        style="margin-top: 15px; justify-content: flex-end"
        @size-change="loadOrders"
        @current-change="loadOrders"
      />
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import axios from 'axios'
import dayjs from 'dayjs'

const orders = ref<any[]>([])
const loading = ref(false)

const filter = ref({
  orderNo: '',
  userPhone: '',
  status: undefined as number | undefined,
  dateRange: [] as Date[]
})

const pagination = ref({
  page: 1,
  pageSize: 20,
  total: 0
})

onMounted(() => {
  loadOrders()
})

const loadOrders = async () => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.value.page,
      pageSize: pagination.value.pageSize
    }
    if (filter.value.orderNo) params.orderNo = filter.value.orderNo
    if (filter.value.userPhone) params.userPhone = filter.value.userPhone
    if (filter.value.status !== undefined) params.status = filter.value.status
    if (filter.value.dateRange?.length === 2) {
      params.startDate = filter.value.dateRange[0]
      params.endDate = filter.value.dateRange[1]
    }
    
    const res = await axios.get('/api/v1/admin/orders', { params })
    orders.value = res.data.data?.list || []
    pagination.value.total = res.data.data?.total || 0
  } catch (error) {
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const getPlatformText = (platform: string) => {
  const texts: Record<string, string> = {
    iqiyi: '爱奇艺',
    youku: '优酷',
    tencent: '腾讯视频'
  }
  return texts[platform] || platform
}

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

const handleRetry = async (row: any) => {
  try {
    await axios.post(`/api/v1/admin/orders/${row.id}/retry`)
    ElMessage.success('重试已提交')
    loadOrders()
  } catch (error) {
    ElMessage.error('重试失败')
  }
}
</script>

<style scoped>
.filter-row {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}
</style>

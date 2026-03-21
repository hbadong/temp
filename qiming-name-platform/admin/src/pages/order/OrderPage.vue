<template>
  <div class="order-page">
    <div class="page-header">
      <h1>订单管理</h1>
    </div>

    <a-card>
      <a-form layout="inline" class="search-form">
        <a-form-item label="订单号">
          <a-input v-model:value="searchForm.orderNo" placeholder="请输入订单号" allow-clear />
        </a-form-item>
        <a-form-item label="服务类型">
          <a-select v-model:value="searchForm.serviceType" placeholder="请选择" allow-clear style="width: 120px">
            <a-select-option value="">全部</a-select-option>
            <a-select-option value="bazi">八字起名</a-select-option>
            <a-select-option value="shici">诗词起名</a-select-option>
            <a-select-option value="zhouyi">周易起名</a-select-option>
            <a-select-option value="normal">普通起名</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="订单状态">
          <a-select v-model:value="searchForm.status" placeholder="请选择" allow-clear style="width: 120px">
            <a-select-option value="">全部</a-select-option>
            <a-select-option value="1">待付款</a-select-option>
            <a-select-option value="2">已付款</a-select-option>
            <a-select-option value="3">服务中</a-select-option>
            <a-select-option value="4">已完成</a-select-option>
            <a-select-option value="5">已取消</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item>
          <a-button type="primary" @click="onSearch">搜索</a-button>
        </a-form-item>
      </a-form>

      <a-table :columns="columns" :data-source="dataSource" :loading="loading" row-key="id">
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'serviceType'">
            {{ getServiceTypeText(record.service_type) }}
          </template>
          <template v-else-if="column.key === 'status'">
            <a-tag :color="getStatusColor(record.status)">
              {{ getStatusText(record.status) }}
            </a-tag>
          </template>
          <template v-else-if="column.key === 'action'">
            <a-space>
              <a @click="viewOrder(record)">查看</a>
              <a-divider type="vertical" />
              <a @click="editOrder(record)">处理</a>
            </a-space>
          </template>
        </template>
      </a-table>
    </a-card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { message } from 'ant-design-vue'

const loading = ref(false)

const searchForm = reactive({
  orderNo: '',
  serviceType: '',
  status: ''
})

const columns = [
  { title: '订单号', dataIndex: 'order_no', key: 'order_no' },
  { title: '用户名', dataIndex: 'username', key: 'username' },
  { title: '服务类型', dataIndex: 'service_type', key: 'serviceType' },
  { title: '金额', dataIndex: 'actual_price', key: 'actual_price' },
  { title: '状态', dataIndex: 'status', key: 'status' },
  { title: '下单时间', dataIndex: 'created_at', key: 'created_at' },
  { title: '操作', key: 'action' }
]

const dataSource = ref([
  { id: 1, order_no: 'ORD20260321001', username: '张三', service_type: 'bazi', actual_price: 199, status: 2, created_at: '2026-03-21 10:30:00' },
  { id: 2, order_no: 'ORD20260321002', username: '李四', service_type: 'shici', actual_price: 99, status: 3, created_at: '2026-03-21 09:15:00' },
  { id: 3, order_no: 'ORD20260321003', username: '王五', service_type: 'normal', actual_price: 29, status: 1, created_at: '2026-03-21 08:00:00' },
  { id: 4, order_no: 'ORD20260321004', username: '赵六', service_type: 'zhouyi', actual_price: 299, status: 4, created_at: '2026-03-20 15:30:00' }
])

const getServiceTypeText = (type) => {
  const map = { bazi: '八字起名', shici: '诗词起名', zhouyi: '周易起名', normal: '普通起名' }
  return map[type] || type
}

const getStatusColor = (status) => {
  return { 1: 'blue', 2: 'green', 3: 'orange', 4: 'green', 5: 'red' }[status] || 'default'
}

const getStatusText = (status) => {
  return { 1: '待付款', 2: '已付款', 3: '服务中', 4: '已完成', 5: '已取消' }[status] || '未知'
}

const onSearch = () => {
  loading.value = true
  setTimeout(() => { loading.value = false }, 500)
}

const viewOrder = (record) => {
  message.info('查看订单: ' + record.order_no)
}

const editOrder = (record) => {
  message.info('处理订单: ' + record.order_no)
}
</script>

<style lang="scss" scoped>
.order-page {
  .page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;

    h1 {
      font-size: 20px;
      margin: 0;
    }
  }

  .search-form {
    margin-bottom: 16px;
  }
}
</style>

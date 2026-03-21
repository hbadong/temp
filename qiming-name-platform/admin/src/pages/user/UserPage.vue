<template>
  <div class="user-page">
    <div class="page-header">
      <h1>用户管理</h1>
      <a-space>
        <a-button @click="exportData">导出</a-button>
      </a-space>
    </div>

    <a-card>
      <a-form layout="inline" class="search-form">
        <a-form-item label="用户名">
          <a-input v-model:value="searchForm.username" placeholder="请输入用户名" allow-clear />
        </a-form-item>
        <a-form-item label="手机号">
          <a-input v-model:value="searchForm.phone" placeholder="请输入手机号" allow-clear />
        </a-form-item>
        <a-form-item label="状态">
          <a-select v-model:value="searchForm.status" placeholder="请选择状态" allow-clear style="width: 120px">
            <a-select-option value="">全部</a-select-option>
            <a-select-option value="1">正常</a-select-option>
            <a-select-option value="0">禁用</a-select-option>
            <a-select-option value="2">待验证</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item>
          <a-button type="primary" @click="onSearch">搜索</a-button>
          <a-button style="margin-left: 8px" @click="onReset">重置</a-button>
        </a-form-item>
      </a-form>

      <a-table
        :columns="columns"
        :data-source="dataSource"
        :loading="loading"
        :pagination="pagination"
        @change="handleTableChange"
        row-key="id"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'status'">
            <a-tag :color="getStatusColor(record.status)">
              {{ getStatusText(record.status) }}
            </a-tag>
          </template>
          <template v-else-if="column.key === 'gender'">
            {{ record.gender === 1 ? '男' : record.gender === 2 ? '女' : '未知' }}
          </template>
          <template v-else-if="column.key === 'action'">
            <a-space>
              <a @click="viewDetail(record)">查看</a>
              <a-divider type="vertical" />
              <a @click="editUser(record)">编辑</a>
              <a-divider type="vertical" />
              <a-dropdown>
                <a>更多</a>
                <template #overlay>
                  <a-menu>
                    <a-menu-item key="enable" v-if="record.status === 0" @click="changeStatus(record, 1)">启用</a-menu-item>
                    <a-menu-item key="disable" v-if="record.status === 1" @click="changeStatus(record, 0)">禁用</a-menu-item>
                    <a-menu-item key="reset" @click="resetPassword(record)">重置密码</a-menu-item>
                  </a-menu>
                </template>
              </a-dropdown>
            </a-space>
          </template>
        </template>
      </a-table>
    </a-card>

    <a-modal v-model:open="detailVisible" title="用户详情" :footer="null" width="600px">
      <a-descriptions :column="2" bordered>
        <a-descriptions-item label="用户ID">{{ currentUser?.id }}</a-descriptions-item>
        <a-descriptions-item label="用户名">{{ currentUser?.username }}</a-descriptions-item>
        <a-descriptions-item label="手机号">{{ currentUser?.phone }}</a-descriptions-item>
        <a-descriptions-item label="邮箱">{{ currentUser?.email }}</a-descriptions-item>
        <a-descriptions-item label="性别">{{ currentUser?.gender === 1 ? '男' : '女' }}</a-descriptions-item>
        <a-descriptions-item label="状态">{{ getStatusText(currentUser?.status) }}</a-descriptions-item>
        <a-descriptions-item label="注册时间">{{ currentUser?.created_at }}</a-descriptions-item>
        <a-descriptions-item label="最后登录">{{ currentUser?.last_login_time }}</a-descriptions-item>
      </a-descriptions>
    </a-modal>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { message } from 'ant-design-vue'

const loading = ref(false)
const detailVisible = ref(false)
const currentUser = ref(null)

const searchForm = reactive({
  username: '',
  phone: '',
  status: ''
})

const columns = [
  { title: 'ID', dataIndex: 'id', key: 'id', width: 80 },
  { title: '用户名', dataIndex: 'username', key: 'username' },
  { title: '手机号', dataIndex: 'phone', key: 'phone' },
  { title: '邮箱', dataIndex: 'email', key: 'email' },
  { title: '性别', dataIndex: 'gender', key: 'gender', width: 80 },
  { title: '状态', dataIndex: 'status', key: 'status', width: 100 },
  { title: '注册时间', dataIndex: 'created_at', key: 'created_at' },
  { title: '操作', key: 'action', width: 200 }
]

const dataSource = ref([
  { id: 1, username: 'user001', phone: '138****1234', email: 'user001@email.com', gender: 1, status: 1, created_at: '2026-03-01 10:00:00', last_login_time: '2026-03-21 09:30:00' },
  { id: 2, username: 'user002', phone: '139****5678', email: 'user002@email.com', gender: 2, status: 1, created_at: '2026-03-05 14:20:00', last_login_time: '2026-03-20 18:45:00' },
  { id: 3, username: 'user003', phone: '136****9012', email: 'user003@email.com', gender: 1, status: 0, created_at: '2026-03-10 08:00:00', last_login_time: '2026-03-15 20:00:00' }
])

const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 3
})

const getStatusColor = (status) => {
  return { 0: 'red', 1: 'green', 2: 'orange' }[status] || 'default'
}

const getStatusText = (status) => {
  return { 0: '禁用', 1: '正常', 2: '待验证' }[status] || '未知'
}

const onSearch = () => {
  loading.value = true
  setTimeout(() => {
    loading.value = false
  }, 500)
}

const onReset = () => {
  searchForm.username = ''
  searchForm.phone = ''
  searchForm.status = ''
  onSearch()
}

const handleTableChange = (pag) => {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  onSearch()
}

const viewDetail = (record) => {
  currentUser.value = record
  detailVisible.value = true
}

const editUser = (record) => {
  message.info('编辑用户: ' + record.username)
}

const changeStatus = (record, status) => {
  record.status = status
  message.success(status === 1 ? '已启用' : '已禁用')
}

const resetPassword = (record) => {
  message.success('密码已重置为: 123456')
}

const exportData = () => {
  message.success('导出成功')
}
</script>

<style lang="scss" scoped>
.user-page {
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

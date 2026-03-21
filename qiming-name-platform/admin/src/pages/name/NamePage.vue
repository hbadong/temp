<template>
  <div class="name-page">
    <div class="page-header">
      <h1>名字库管理</h1>
      <a-space>
        <a-button type="primary" @click="showAddModal">添加名字</a-button>
        <a-button @click="importNames">批量导入</a-button>
        <a-button @click="exportData">导出</a-button>
      </a-space>
    </div>

    <a-card>
      <a-form layout="inline" class="search-form">
        <a-form-item label="姓名">
          <a-input v-model:value="searchForm.name" placeholder="请输入姓名" allow-clear />
        </a-form-item>
        <a-form-item label="性别">
          <a-select v-model:value="searchForm.gender" placeholder="请选择" allow-clear style="width: 100px">
            <a-select-option value="">全部</a-select-option>
            <a-select-option value="1">男</a-select-option>
            <a-select-option value="2">女</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="五行">
          <a-select v-model:value="searchForm.fiveElement" placeholder="请选择" allow-clear style="width: 100px">
            <a-select-option value="">全部</a-select-option>
            <a-select-option value="金">金</a-select-option>
            <a-select-option value="木">木</a-select-option>
            <a-select-option value="水">水</a-select-option>
            <a-select-option value="火">火</a-select-option>
            <a-select-option value="土">土</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item>
          <a-button type="primary" @click="onSearch">搜索</a-button>
        </a-form-item>
      </a-form>

      <a-table :columns="columns" :data-source="dataSource" :loading="loading" row-key="id">
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'gender'">
            {{ record.gender === 1 ? '男' : '女' }}
          </template>
          <template v-else-if="column.key === 'fiveElement'">
            <a-tag :color="getElementColor(record.five_element)">{{ record.five_element }}</a-tag>
          </template>
          <template v-else-if="column.key === 'score'">
            <a-tag :color="getScoreColor(record.total_score)">{{ record.total_score }}分</a-tag>
          </template>
          <template v-else-if="column.key === 'status'">
            <a-switch :checked="record.status === 1" @change="(checked) => changeStatus(record, checked)" />
          </template>
          <template v-else-if="column.key === 'action'">
            <a-space>
              <a @click="editName(record)">编辑</a>
              <a-divider type="vertical" />
              <a @click="deleteName(record)">删除</a>
            </a-space>
          </template>
        </template>
      </a-table>
    </a-card>

    <a-modal v-model:open="addModalVisible" title="添加名字" @ok="handleAdd" width="600px">
      <a-form :model="addForm" layout="vertical">
        <a-form-item label="姓氏" required>
          <a-input v-model:value="addForm.surname" placeholder="请输入姓氏" />
        </a-form-item>
        <a-form-item label="名字" required>
          <a-input v-model:value="addForm.givenName" placeholder="请输入名字" />
        </a-form-item>
        <a-form-item label="性别" required>
          <a-radio-group v-model:value="addForm.gender">
            <a-radio :value="1">男</a-radio>
            <a-radio :value="2">女</a-radio>
          </a-radio-group>
        </a-form-item>
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="五行">
              <a-select v-model:value="addForm.fiveElement" placeholder="请选择">
                <a-select-option value="金">金</a-select-option>
                <a-select-option value="木">木</a-select-option>
                <a-select-option value="水">水</a-select-option>
                <a-select-option value="火">火</a-select-option>
                <a-select-option value="土">土</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="拼音">
              <a-input v-model:value="addForm.pinyin" placeholder="请输入拼音" />
            </a-form-item>
          </a-col>
        </a-row>
        <a-form-item label="寓意解释">
          <a-textarea v-model:value="addForm.meaning" :rows="3" placeholder="请输入寓意解释" />
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { message, Modal } from 'ant-design-vue'

const loading = ref(false)
const addModalVisible = ref(false)

const searchForm = reactive({
  name: '',
  gender: '',
  fiveElement: ''
})

const addForm = reactive({
  surname: '',
  givenName: '',
  gender: 1,
  fiveElement: '',
  pinyin: '',
  meaning: ''
})

const columns = [
  { title: 'ID', dataIndex: 'id', key: 'id', width: 80 },
  { title: '姓名', dataIndex: 'full_name', key: 'full_name' },
  { title: '拼音', dataIndex: 'pinyin', key: 'pinyin' },
  { title: '性别', dataIndex: 'gender', key: 'gender', width: 80 },
  { title: '五行', dataIndex: 'five_element', key: 'fiveElement', width: 80 },
  { title: '评分', dataIndex: 'total_score', key: 'score', width: 80 },
  { title: '使用次数', dataIndex: 'usage_count', key: 'usage_count', width: 100 },
  { title: '状态', dataIndex: 'status', key: 'status', width: 80 },
  { title: '操作', key: 'action', width: 150 }
]

const dataSource = ref([
  { id: 1, full_name: '李俊豪', pinyin: 'li jun hao', gender: 1, five_element: '火', total_score: 98, usage_count: 1560, status: 1 },
  { id: 2, full_name: '李欣怡', pinyin: 'li xin yi', gender: 2, five_element: '金', total_score: 96, usage_count: 1230, status: 1 },
  { id: 3, full_name: '李煜晨', pinyin: 'li yu chen', gender: 1, five_element: '火', total_score: 95, usage_count: 980, status: 1 }
])

const getElementColor = (element) => {
  return { '金': '#ffd700', '木': '#228b22', '水': '#4169e1', '火': '#ff4500', '土': '#8b4513' }[element] || 'default'
}

const getScoreColor = (score) => {
  if (score >= 90) return 'green'
  if (score >= 80) return 'blue'
  if (score >= 70) return 'orange'
  return 'red'
}

const onSearch = () => {
  loading.value = true
  setTimeout(() => { loading.value = false }, 500)
}

const showAddModal = () => {
  addModalVisible.value = true
}

const handleAdd = () => {
  message.success('添加成功')
  addModalVisible.value = false
}

const editName = (record) => {
  message.info('编辑: ' + record.full_name)
}

const deleteName = (record) => {
  Modal.confirm({
    title: '确认删除',
    content: `确定要删除名字"${record.full_name}"吗？`,
    onOk() {
      message.success('删除成功')
    }
  })
}

const changeStatus = (record, checked) => {
  record.status = checked ? 1 : 0
  message.success(checked ? '已启用' : '已禁用')
}

const importNames = () => {
  message.info('批量导入功能')
}

const exportData = () => {
  message.success('导出成功')
}
</script>

<style lang="scss" scoped>
.name-page {
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

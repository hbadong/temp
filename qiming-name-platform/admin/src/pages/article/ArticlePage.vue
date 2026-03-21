<template>
  <div class="article-page">
    <div class="page-header">
      <h1>文章管理</h1>
      <a-button type="primary" @click="showAddModal">写文章</a-button>
    </div>

    <a-card>
      <a-form layout="inline" class="search-form">
        <a-form-item label="标题">
          <a-input v-model:value="searchForm.title" placeholder="请输入标题" allow-clear />
        </a-form-item>
        <a-form-item label="分类">
          <a-select v-model:value="searchForm.category" placeholder="请选择" allow-clear style="width: 150px">
            <a-select-option value="">全部</a-select-option>
            <a-select-option value="1">起名常识</a-select-option>
            <a-select-option value="2">八字知识</a-select-option>
            <a-select-option value="3">诗词起名</a-select-option>
            <a-select-option value="4">周易起名</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="状态">
          <a-select v-model:value="searchForm.status" placeholder="请选择" allow-clear style="width: 120px">
            <a-select-option value="">全部</a-select-option>
            <a-select-option value="1">已发布</a-select-option>
            <a-select-option value="0">草稿</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item>
          <a-button type="primary" @click="onSearch">搜索</a-button>
        </a-form-item>
      </a-form>

      <a-table :columns="columns" :data-source="dataSource" :loading="loading" row-key="id">
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'status'">
            <a-tag :color="record.status === 1 ? 'green' : 'orange'">
              {{ record.status === 1 ? '已发布' : '草稿' }}
            </a-tag>
          </template>
          <template v-else-if="column.key === 'isTop'">
            <a-tag :color="record.is_top === 1 ? 'red' : 'default'">
              {{ record.is_top === 1 ? '置顶' : '否' }}
            </a-tag>
          </template>
          <template v-else-if="column.key === 'action'">
            <a-space>
              <a @click="editArticle(record)">编辑</a>
              <a-divider type="vertical" />
              <a @click="deleteArticle(record)">删除</a>
            </a-space>
          </template>
        </template>
      </a-table>
    </a-card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { message, Modal } from 'ant-design-vue'

const loading = ref(false)

const searchForm = reactive({
  title: '',
  category: '',
  status: ''
})

const columns = [
  { title: 'ID', dataIndex: 'id', key: 'id', width: 80 },
  { title: '标题', dataIndex: 'title', key: 'title' },
  { title: '分类', dataIndex: 'category', key: 'category' },
  { title: '作者', dataIndex: 'author', key: 'author', width: 100 },
  { title: '浏览量', dataIndex: 'view_count', key: 'view_count', width: 100 },
  { title: '状态', dataIndex: 'status', key: 'status', width: 100 },
  { title: '置顶', dataIndex: 'is_top', key: 'isTop', width: 80 },
  { title: '发布时间', dataIndex: 'published_at', key: 'published_at' },
  { title: '操作', key: 'action', width: 150 }
]

const dataSource = ref([
  { id: 1, title: '宝宝起名别跟风！8个独特技巧', category: '起名常识', author: '清飞扬', view_count: 1256, status: 1, is_top: 1, published_at: '2026-03-21' },
  { id: 2, title: '八字五行缺火的人应该怎么起名', category: '八字知识', author: '清飞扬', view_count: 896, status: 1, is_top: 0, published_at: '2026-03-20' },
  { id: 3, title: '如何起一个富含诗意的好名字', category: '诗词起名', author: '清飞扬', view_count: 543, status: 1, is_top: 0, published_at: '2026-03-19' }
])

const onSearch = () => {
  loading.value = true
  setTimeout(() => { loading.value = false }, 500)
}

const showAddModal = () => {
  message.info('写文章')
}

const editArticle = (record) => {
  message.info('编辑: ' + record.title)
}

const deleteArticle = (record) => {
  Modal.confirm({
    title: '确认删除',
    content: `确定要删除文章"${record.title}"吗？`,
    onOk() {
      message.success('删除成功')
    }
  })
}
</script>

<style lang="scss" scoped>
.article-page {
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

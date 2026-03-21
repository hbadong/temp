<template>
  <div class="products-page">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>套餐管理</span>
          <el-button type="primary" @click="showDialog = true">添加套餐</el-button>
        </div>
      </template>
      
      <el-table :data="products" v-loading="loading">
        <el-table-column prop="id" label="ID" width="60" />
        <el-table-column prop="platform" label="平台" width="100">
          <template #default="{ row }">
            {{ getPlatformText(row.platform) }}
          </template>
        </el-table-column>
        <el-table-column prop="name" label="套餐名称" />
        <el-table-column prop="durationDays" label="时长(天)" width="100" />
        <el-table-column prop="price" label="价格" width="100">
          <template #default="{ row }">
            ¥{{ row.price }}
          </template>
        </el-table-column>
        <el-table-column prop="stock" label="库存" width="100" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'info'">
              {{ row.status === 1 ? '上架' : '下架' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150">
          <template #default="{ row }">
            <el-button type="primary" size="small" @click="handleEdit(row)">编辑</el-button>
            <el-button 
              :type="row.status === 1 ? 'warning' : 'success'" 
              size="small"
              @click="toggleStatus(row)"
            >
              {{ row.status === 1 ? '下架' : '上架' }}
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>
    
    <el-dialog v-model="showDialog" :title="editingProduct ? '编辑套餐' : '添加套餐'" width="500px">
      <el-form :model="form" label-width="80px">
        <el-form-item label="平台">
          <el-select v-model="form.platform" placeholder="请选择平台">
            <el-option label="爱奇艺" value="iqiyi" />
            <el-option label="优酷" value="youku" />
            <el-option label="腾讯视频" value="tencent" />
          </el-select>
        </el-form-item>
        <el-form-item label="套餐名称">
          <el-input v-model="form.name" placeholder="请输入套餐名称" />
        </el-form-item>
        <el-form-item label="时长(天)">
          <el-input-number v-model="form.durationDays" :min="1" />
        </el-form-item>
        <el-form-item label="价格">
          <el-input-number v-model="form.price" :min="0" :precision="2" />
        </el-form-item>
        <el-form-item label="库存">
          <el-input-number v-model="form.stock" :min="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showDialog = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import axios from 'axios'

const products = ref<any[]>([])
const loading = ref(false)
const showDialog = ref(false)
const editingProduct = ref<any>(null)

const form = ref({
  platform: 'iqiyi',
  name: '',
  durationDays: 30,
  price: 0,
  stock: 0
})

onMounted(() => {
  loadProducts()
})

const loadProducts = async () => {
  loading.value = true
  try {
    const res = await axios.get('/api/v1/admin/products')
    products.value = res.data.data || []
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

const handleEdit = (row: any) => {
  editingProduct.value = row
  form.value = { ...row }
  showDialog.value = true
}

const toggleStatus = async (row: any) => {
  try {
    await axios.put(`/api/v1/admin/products/${row.id}/status`, {
      status: row.status === 1 ? 0 : 1
    })
    ElMessage.success('操作成功')
    loadProducts()
  } catch (error) {
    ElMessage.error('操作失败')
  }
}

const handleSubmit = async () => {
  try {
    if (editingProduct.value) {
      await axios.put(`/api/v1/admin/products/${editingProduct.value.id}`, form.value)
    } else {
      await axios.post('/api/v1/admin/products', form.value)
    }
    ElMessage.success('保存成功')
    showDialog.value = false
    loadProducts()
  } catch (error) {
    ElMessage.error('保存失败')
  }
}
</script>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>

<template>
  <div class="cards-page">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>卡密管理</span>
          <el-button type="primary" @click="showGenerateDialog = true">生成卡密</el-button>
        </div>
      </template>
      
      <div class="filter-row">
        <el-input 
          v-model="filter.cardNo" 
          placeholder="卡密号" 
          style="width: 200px"
          clearable
        />
        <el-select v-model="filter.status" placeholder="状态" style="width: 120px" clearable>
          <el-option label="未使用" :value="0" />
          <el-option label="已使用" :value="1" />
          <el-option label="已作废" :value="2" />
        </el-select>
        <el-button type="primary" @click="loadCards">搜索</el-button>
        <el-button type="success" @click="handleExport">导出</el-button>
      </div>
      
      <el-table :data="cards" v-loading="loading" style="margin-top: 15px">
        <el-table-column prop="card_no" label="卡密号" width="180" />
        <el-table-column prop="batch_no" label="批次号" width="150" />
        <el-table-column prop="status" label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="used_at" label="使用时间">
          <template #default="{ row }">
            {{ row.used_at ? formatTime(row.used_at) : '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间">
          <template #default="{ row }">
            {{ formatTime(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="100">
          <template #default="{ row }">
            <el-button 
              type="danger" 
              size="small" 
              @click="handleDisable(row)"
              :disabled="row.status !== 0"
            >
              作废
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
        @size-change="loadCards"
        @current-change="loadCards"
      />
    </el-card>
    
    <el-dialog v-model="showGenerateDialog" title="生成卡密" width="500px">
      <el-form :model="generateForm" label-width="100px">
        <el-form-item label="选择套餐">
          <el-select v-model="generateForm.productId" placeholder="请选择套餐">
            <el-option 
              v-for="p in products" 
              :key="p.id" 
              :label="`${p.name} - ¥${p.price}`" 
              :value="p.id" 
            />
          </el-select>
        </el-form-item>
        <el-form-item label="生成数量">
          <el-input-number v-model="generateForm.count" :min="1" :max="1000" />
        </el-form-item>
        <el-form-item label="卡密前缀">
          <el-input v-model="generateForm.prefix" placeholder="如: CRS" maxlength="10" />
        </el-form-item>
        <el-form-item label="有效期">
          <el-date-picker
            v-model="generateForm.validDates"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showGenerateDialog = false">取消</el-button>
        <el-button type="primary" @click="handleGenerate">生成</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import axios from 'axios'
import dayjs from 'dayjs'

const cards = ref<any[]>([])
const products = ref<any[]>([])
const loading = ref(false)
const showGenerateDialog = ref(false)

const filter = ref({
  cardNo: '',
  status: undefined as number | undefined
})

const pagination = ref({
  page: 1,
  pageSize: 20,
  total: 0
})

const generateForm = ref({
  productId: undefined as number | undefined,
  count: 100,
  prefix: 'CRS',
  validDates: [] as Date[]
})

onMounted(() => {
  loadProducts()
  loadCards()
})

const loadProducts = async () => {
  try {
    const res = await axios.get('/api/v1/admin/products')
    products.value = res.data.data || []
  } catch (error) {
    console.error('加载套餐失败', error)
  }
}

const loadCards = async () => {
  loading.value = true
  try {
    const params: any = {
      page: pagination.value.page,
      pageSize: pagination.value.pageSize
    }
    if (filter.value.cardNo) params.cardNo = filter.value.cardNo
    if (filter.value.status !== undefined) params.status = filter.value.status
    
    const res = await axios.get('/api/v1/admin/cards', { params })
    cards.value = res.data.data?.list || []
    pagination.value.total = res.data.data?.total || 0
  } catch (error) {
    ElMessage.error('加载失败')
  } finally {
    loading.value = false
  }
}

const getStatusType = (status: number) => {
  const types: Record<number, string> = {
    0: 'success',
    1: 'warning',
    2: 'danger'
  }
  return types[status] || 'info'
}

const getStatusText = (status: number) => {
  const texts: Record<number, string> = {
    0: '未使用',
    1: '已使用',
    2: '已作废'
  }
  return texts[status] || '未知'
}

const formatTime = (time: string) => {
  return dayjs(time).format('YYYY-MM-DD HH:mm')
}

const handleDisable = async (row: any) => {
  try {
    await ElMessageBox.confirm('确定要作废该卡密吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    })
    await axios.post(`/api/v1/admin/cards/${row.id}/disable`)
    ElMessage.success('操作成功')
    loadCards()
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error('操作失败')
    }
  }
}

const handleGenerate = async () => {
  if (!generateForm.value.productId) {
    ElMessage.warning('请选择套餐')
    return
  }
  
  try {
    await axios.post('/api/v1/admin/cards/batch', {
      productId: generateForm.value.productId,
      count: generateForm.value.count,
      prefix: generateForm.value.prefix,
      validFrom: generateForm.value.validDates[0],
      validUntil: generateForm.value.validDates[1]
    })
    ElMessage.success('生成成功')
    showGenerateDialog.value = false
    loadCards()
  } catch (error) {
    ElMessage.error('生成失败')
  }
}

const handleExport = async () => {
  ElMessage.info('导出功能开发中')
}
</script>

<style scoped>
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.filter-row {
  display: flex;
  gap: 10px;
}
</style>

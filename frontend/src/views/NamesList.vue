<template>
  <div class="names-list">
    <div class="header">
      <h1>名字大全</h1>
      <p>浏览精选好名，为宝宝选择一个吉祥如意的好名字</p>
    </div>
    
    <div class="nav">
      <router-link to="/" class="nav-link">首页</router-link>
      <router-link to="/analysis" class="nav-link">名字测试</router-link>
      <router-link to="/names" class="nav-link active">名字大全</router-link>
    </div>
    
    <div class="container">
      <div class="card">
        <div class="grid grid-3" style="margin-bottom: 20px;">
          <div class="form-group">
            <label class="form-label">性别</label>
            <select v-model="filters.gender" class="form-input" @change="loadNames">
              <option value="">全部</option>
              <option value="M">男宝宝</option>
              <option value="F">女宝宝</option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label">五行</label>
            <select v-model="filters.wuxing" class="form-input" @change="loadNames">
              <option value="">全部</option>
              <option value="金">金</option>
              <option value="木">木</option>
              <option value="水">水</option>
              <option value="火">火</option>
              <option value="土">土</option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label">显示数量</label>
            <select v-model="filters.limit" class="form-input" @change="loadNames">
              <option :value="10">10个</option>
              <option :value="20">20个</option>
              <option :value="50">50个</option>
            </select>
          </div>
        </div>
      </div>
      
      <div v-if="loading" class="loading">加载中...</div>
      
      <div v-else class="grid grid-4">
        <div 
          v-for="name in names" 
          :key="name.char" 
          class="recommend-card"
          @click="selectName(name)"
        >
          <div class="recommend-name">{{ name.char }}</div>
          <div class="recommend-pinyin">{{ name.pinyin }}</div>
          <div style="font-size: 14px; color: #666; margin-bottom: 8px;">
            {{ name.wuxing }} | {{ name.bihua }}画
          </div>
          <div class="recommend-score">{{ name.gender === 'M' ? '男' : '女' }}</div>
        </div>
      </div>
      
      <div v-if="!loading && names.length === 0" style="text-align: center; padding: 40px; color: #999;">
        暂无符合条件的名字
      </div>
    </div>
    
    <div v-if="selectedName" class="modal" @click.self="selectedName = null">
      <div class="card" style="max-width: 500px; margin: 100px auto;">
        <h3 style="text-align: center; margin-bottom: 20px;">{{ selectedName.char }}</h3>
        
        <div class="score-item">
          <span>拼音</span>
          <span>{{ selectedName.pinyin }}</span>
        </div>
        <div class="score-item">
          <span>笔画</span>
          <span>{{ selectedName.bihua }}画</span>
        </div>
        <div class="score-item">
          <span>五行</span>
          <span>{{ selectedName.wuxing }}</span>
        </div>
        <div class="score-item">
          <span>适用性别</span>
          <span>{{ selectedName.gender === 'M' ? '男宝宝' : '女宝宝' }}</span>
        </div>
        <div class="score-item">
          <span>含义</span>
          <span>{{ selectedName.meaning }}</span>
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
          <button @click="testName" class="btn btn-primary" style="margin-right: 10px;">
            测试此名字
          </button>
          <button @click="selectedName = null" class="btn btn-secondary">
            关闭
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { nameApi } from '../api'

const router = useRouter()

const filters = ref({
  gender: '',
  wuxing: '',
  limit: 20
})

const loading = ref(false)
const names = ref([])
const selectedName = ref(null)

onMounted(() => {
  loadNames()
})

const loadNames = async () => {
  loading.value = true
  try {
    const params = {}
    if (filters.value.gender) params.gender = filters.value.gender
    if (filters.value.wuxing) params.wuxing = filters.value.wuxing
    params.limit = filters.value.limit
    
    const response = await nameApi.getNames(params)
    names.value = response.data.names
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

const selectName = (name) => {
  selectedName.value = name
}

const testName = () => {
  const baziResult = localStorage.getItem('baziResult')
  if (!baziResult) {
    alert('请先在首页进行八字分析')
    router.push('/')
    return
  }
  
  localStorage.setItem('testSurname', '李')
  localStorage.setItem('testGivenName', selectedName.value.char)
  router.push('/analysis')
}
</script>

<style scoped>
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
}
</style>

<template>
  <div class="home">
    <div class="header">
      <h1>宝宝起名分析系统</h1>
      <p>根据生辰八字与中华传统姓名学，为宝宝起一个好名字</p>
    </div>
    
    <div class="nav">
      <router-link to="/" class="nav-link active">首页</router-link>
      <router-link to="/analysis" class="nav-link">名字测试</router-link>
      <router-link to="/names" class="nav-link">名字大全</router-link>
    </div>
    
    <div class="container">
      <div class="card">
        <h2 class="page-title">生辰八字分析</h2>
        
        <form @submit.prevent="analyzeBazi">
          <div class="grid grid-2">
            <div class="form-group">
              <label class="form-label">出生年份</label>
              <select v-model="form.year" class="form-input" required>
                <option value="">请选择年份</option>
                <option v-for="y in years" :key="y" :value="y">{{ y }}年</option>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label">出生月份</label>
              <select v-model="form.month" class="form-input" required>
                <option value="">请选择月份</option>
                <option v-for="m in 12" :key="m" :value="m">{{ m }}月</option>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label">出生日期</label>
              <select v-model="form.day" class="form-input" required>
                <option value="">请选择日期</option>
                <option v-for="d in 31" :key="d" :value="d">{{ d }}日</option>
              </select>
            </div>
            
            <div class="form-group">
              <label class="form-label">出生时辰</label>
              <select v-model="form.hour" class="form-input" required>
                <option value="">请选择时辰</option>
                <option v-for="(h, index) in hours" :key="index" :value="index">{{ h }}</option>
              </select>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">宝宝性别</label>
            <div style="display: flex; gap: 20px;">
              <label style="display: flex; align-items: center; gap: 8px;">
                <input type="radio" v-model="form.gender" value="male"> 男宝宝
              </label>
              <label style="display: flex; align-items: center; gap: 8px;">
                <input type="radio" v-model="form.gender" value="female"> 女宝宝
              </label>
            </div>
          </div>
          
          <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
            {{ loading ? '分析中...' : '开始分析' }}
          </button>
        </form>
        
        <div v-if="baziResult" class="result-box">
          <h3 style="text-align: center; margin-bottom: 20px;">八字分析结果</h3>
          
          <div style="text-align: center; margin-bottom: 20px;">
            <div style="display: inline-block; background: #f5f7fa; padding: 20px 40px; border-radius: 12px;">
              <div style="display: flex; gap: 20px;">
                <div>
                  <div style="color: #666; font-size: 14px;">年柱</div>
                  <div style="font-size: 24px; font-weight: bold; color: #4A90E2;">{{ baziResult.bazi.year }}</div>
                </div>
                <div>
                  <div style="color: #666; font-size: 14px;">月柱</div>
                  <div style="font-size: 24px; font-weight: bold; color: #67C23A;">{{ baziResult.bazi.month }}</div>
                </div>
                <div>
                  <div style="color: #666; font-size: 14px;">日柱</div>
                  <div style="font-size: 24px; font-weight: bold; color: #E6A23C;">{{ baziResult.bazi.day }}</div>
                </div>
                <div>
                  <div style="color: #666; font-size: 14px;">时柱</div>
                  <div style="font-size: 24px; font-weight: bold; color: #F56C6C;">{{ baziResult.bazi.hour }}</div>
                </div>
              </div>
            </div>
          </div>
          
          <h4 style="margin: 20px 0 10px;">五行分布</h4>
          <div class="wuxing-bar">
            <div 
              v-for="(item, index) in wuxingData" 
              :key="index"
              class="wuxing-segment wuxing-{{ item.name }}"
              :style="{ width: item.percent + '%' }"
            >
              {{ item.name }}{{ item.value }}
            </div>
          </div>
          
          <div v-if="baziResult.missing.length > 0" style="margin-top: 20px;">
            <h4 style="color: #F56C6C;">五行缺失</h4>
            <div>
              <span v-for="m in baziResult.missing" :key="m" class="tag tag-missing">{{ m }}</span>
            </div>
          </div>
          
          <div v-if="baziResult.suggestions.length > 0" style="margin-top: 20px;">
            <h4 style="color: #67C23A;">补足建议</h4>
            <div>
              <span v-for="s in baziResult.suggestions" :key="s" class="tag tag-success">{{ s }}</span>
            </div>
          </div>
          
          <div style="margin-top: 30px; text-align: center;">
            <button @click="goToAnalysis" class="btn btn-secondary">
              去测试名字吉凶
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { baziApi } from '../api'

const router = useRouter()

const form = ref({
  year: '',
  month: '',
  day: '',
  hour: '',
  gender: 'male'
})

const loading = ref(false)
const baziResult = ref(null)

const years = computed(() => {
  const currentYear = new Date().getFullYear()
  return Array.from({ length: 50 }, (_, i) => currentYear - 30 + i)
})

const hours = [
  '子时 (23:00-00:59)', '丑时 (01:00-02:59)', '寅时 (03:00-04:59)', '卯时 (05:00-06:59)',
  '辰时 (07:00-08:59)', '巳时 (09:00-10:59)', '午时 (11:00-12:59)', '未时 (13:00-14:59)',
  '申时 (15:00-16:59)', '酉时 (17:00-18:59)', '戌时 (19:00-20:59)', '亥时 (21:00-22:59)'
]

const wuxingData = computed(() => {
  if (!baziResult.value) return []
  const w = baziResult.value.wuxing
  const total = w.metal + w.wood + w.water + w.fire + w.earth
  const names = ['金', '木', '水', '火', '土']
  const values = [w.metal, w.wood, w.water, w.fire, w.earth]
  return names.map((name, i) => ({
    name,
    value: values[i],
    percent: total > 0 ? Math.round(values[i] / total * 100) : 20
  }))
})

const analyzeBazi = async () => {
  loading.value = true
  try {
    const response = await baziApi.analyze({
      birth_year: parseInt(form.value.year),
      birth_month: parseInt(form.value.month),
      birth_day: parseInt(form.value.day),
      birth_hour: parseInt(form.value.hour),
      gender: form.value.gender
    })
    baziResult.value = response.data
    
    localStorage.setItem('baziResult', JSON.stringify(response.data))
    localStorage.setItem('babyGender', form.value.gender)
  } catch (error) {
    alert('分析失败，请重试')
    console.error(error)
  } finally {
    loading.value = false
  }
}

const goToAnalysis = () => {
  router.push('/analysis')
}
</script>

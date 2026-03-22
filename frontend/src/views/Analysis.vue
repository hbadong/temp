<template>
  <div class="analysis">
    <div class="header">
      <h1>名字测试</h1>
      <p>输入您想测试的名字，获取详细的姓名学分析</p>
    </div>
    
    <div class="nav">
      <router-link to="/" class="nav-link">首页</router-link>
      <router-link to="/analysis" class="nav-link active">名字测试</router-link>
      <router-link to="/names" class="nav-link">名字大全</router-link>
    </div>
    
    <div class="container">
      <div class="card">
        <h2 class="page-title">名字分析</h2>
        
        <form @submit.prevent="analyzeName">
          <div class="grid grid-2">
            <div class="form-group">
              <label class="form-label">姓氏</label>
              <input 
                v-model="form.surname" 
                type="text" 
                class="form-input" 
                placeholder="请输入姓氏"
                maxlength="2"
                required
              >
            </div>
            
            <div class="form-group">
              <label class="form-label">名字</label>
              <input 
                v-model="form.givenName" 
                type="text" 
                class="form-input" 
                placeholder="请输入名字（1-2个汉字）"
                maxlength="2"
                required
              >
            </div>
          </div>
          
          <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
            {{ loading ? '分析中...' : '开始分析' }}
          </button>
        </form>
      </div>
      
      <div v-if="nameResult" class="card">
        <h3 style="text-align: center; margin-bottom: 20px;">
          {{ form.surname }}{{ form.givenName }} - 综合评分
        </h3>
        
        <div :class="['score-circle', scoreClass]">
          {{ nameResult.final_score }}
        </div>
        
        <div style="text-align: center; margin-top: 10px;">
          <span class="tag" :class="gradeClass">{{ nameResult.grade }}</span>
        </div>
        
        <div class="evaluation-box">
          <h4>综合评价</h4>
          <p>{{ nameResult.evaluation }}</p>
        </div>
        
        <div style="margin-top: 30px;">
          <h4 style="margin-bottom: 16px;">三才五格分析</h4>
          <table class="wuge-table">
            <thead>
              <tr>
                <th>天格</th>
                <th>人格</th>
                <th>地格</th>
                <th>外格</th>
                <th>总格</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  {{ nameResult.wuge.tiange.value }}
                  <br>
                  <span :class="['jixiong-' + nameResult.wuge.tiange.jixiong]">
                    {{ nameResult.wuge.tiange.jixiong }}
                  </span>
                </td>
                <td>
                  {{ nameResult.wuge.renge.value }}
                  <br>
                  <span :class="['jixiong-' + nameResult.wuge.renge.jixiong]">
                    {{ nameResult.wuge.renge.jixiong }}
                  </span>
                </td>
                <td>
                  {{ nameResult.wuge.dige.value }}
                  <br>
                  <span :class="['jixiong-' + nameResult.wuge.dige.jixiong]">
                    {{ nameResult.wuge.dige.jixiong }}
                  </span>
                </td>
                <td>
                  {{ nameResult.wuge.waige.value }}
                  <br>
                  <span :class="['jixiong-' + nameResult.wuge.waige.jixiong]">
                    {{ nameResult.wuge.waige.jixiong }}
                  </span>
                </td>
                <td>
                  {{ nameResult.wuge.zongge.value }}
                  <br>
                  <span :class="['jixiong-' + nameResult.wuge.zongge.jixiong]">
                    {{ nameResult.wuge.zongge.jixiong }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
          
          <div style="margin-top: 16px; padding: 12px; background: #f5f7fa; border-radius: 8px;">
            <strong>三才配置：</strong>{{ nameResult.sancai_description }}
            <br>
            <strong>三才评分：</strong>{{ nameResult.sancai_score }}分
          </div>
        </div>
        
        <div style="margin-top: 30px;">
          <h4 style="margin-bottom: 16px;">详细评分</h4>
          <div class="score-item">
            <span>音韵评分</span>
            <span>{{ nameResult.yinyun_score }}分</span>
          </div>
          <div class="score-item">
            <span>字形评分</span>
            <span>{{ nameResult.zixing_score }}分</span>
          </div>
          <div class="score-item">
            <span>内涵评分</span>
            <span>{{ nameResult.hanyi_score }}分</span>
          </div>
          <div class="score-item" v-if="nameResult.wuxing_bonus !== 0">
            <span>五行加成</span>
            <span :style="{ color: nameResult.wuxing_bonus > 0 ? '#67C23A' : '#F56C6C' }">
              {{ nameResult.wuxing_bonus > 0 ? '+' : '' }}{{ nameResult.wuxing_bonus }}分
            </span>
          </div>
        </div>
        
        <div style="margin-top: 30px;">
          <h4 style="margin-bottom: 16px;">名字含义</h4>
          <div v-for="h in nameResult.hanzi_meanings" :key="h.char" class="score-item">
            <span>{{ h.char }} ({{ h.pinyin }})</span>
            <span>{{ h.meaning }}</span>
          </div>
        </div>
        
        <div style="margin-top: 30px;">
          <h4 style="margin-bottom: 16px;">五行属性</h4>
          <div>
            <span v-for="(wx, i) in nameResult.wuxing" :key="i" class="tag tag-success" style="margin-right: 10px;">
              {{ wx }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { nameApi } from '../api'

const form = ref({
  surname: '',
  givenName: ''
})

const loading = ref(false)
const nameResult = ref(null)
const baziData = ref(null)

onMounted(() => {
  const saved = localStorage.getItem('baziResult')
  if (saved) {
    baziData.value = JSON.parse(saved)
  }
})

const scoreClass = computed(() => {
  if (!nameResult.value) return ''
  const score = nameResult.value.final_score
  if (score >= 90) return 'score-excellent'
  if (score >= 75) return 'score-good'
  if (score >= 60) return 'score-average'
  return 'score-poor'
})

const gradeClass = computed(() => {
  if (!nameResult.value) return ''
  const grade = nameResult.value.grade
  if (grade === '优') return 'tag-success'
  if (grade === '良') return 'tag-primary'
  if (grade === '中') return 'tag-warning'
  return 'tag-danger'
})

const analyzeName = async () => {
  loading.value = true
  try {
    const data = {
      surname: form.value.surname,
      given_name: form.value.givenName
    }
    
    if (baziData.value && baziData.value.missing.length > 0) {
      data.bazi = {
        missing: baziData.value.missing
      }
    }
    
    const response = await nameApi.analyze(data)
    nameResult.value = response.data
  } catch (error) {
    alert('分析失败，请重试')
    console.error(error)
  } finally {
    loading.value = false
  }
}
</script>

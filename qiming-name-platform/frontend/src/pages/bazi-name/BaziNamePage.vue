<template>
  <div class="bazi-name-page">
    <header class="header">
      <div class="container">
        <router-link to="/" class="logo">起名网</router-link>
        <nav class="nav">
          <router-link to="/baobao">宝宝起名</router-link>
          <router-link to="/bazi" class="active">八字起名</router-link>
          <router-link to="/shici">诗词起名</router-link>
          <router-link to="/xingmingceshi">姓名测试</router-link>
        </nav>
      </div>
    </header>

    <main class="main">
      <section class="form-section">
        <div class="container">
          <h1>八字起名</h1>
          <p class="subtitle">汇聚多位国内权威易学大师，精准解析八字，量身打造帮扶一生的优质好名</p>
          
          <a-form :model="form" layout="inline" class="name-form">
            <a-form-item>
              <a-input v-model:value="form.surname" placeholder="请输入姓氏" size="large" />
            </a-form-item>
            
            <a-form-item>
              <a-radio-group v-model:value="form.gender" size="large">
                <a-radio-button :value="1">男宝宝</a-radio-button>
                <a-radio-button :value="2">女宝宝</a-radio-button>
              </a-radio-group>
            </a-form-item>
            
            <a-form-item>
              <a-select v-model:value="form.birthYear" placeholder="年" size="large" style="width: 100px">
                <a-select-option v-for="y in years" :key="y" :value="y">{{ y }}</a-select-option>
              </a-select>
            </a-form-item>
            
            <a-form-item>
              <a-select v-model:value="form.birthMonth" placeholder="月" size="large" style="width: 80px">
                <a-select-option v-for="m in 12" :key="m" :value="m">{{ m }}</a-select-option>
              </a-select>
            </a-form-item>
            
            <a-form-item>
              <a-select v-model:value="form.birthDay" placeholder="日" size="large" style="width: 80px">
                <a-select-option v-for="d in 31" :key="d" :value="d">{{ d }}</a-select-option>
              </a-select>
            </a-form-item>
            
            <a-form-item>
              <a-select v-model:value="form.birthTime" placeholder="时辰" size="large" style="width: 150px">
                <a-select-option v-for="time in timeOptions" :key="time.value" :value="time.value">
                  {{ time.label }}
                </a-select-option>
              </a-select>
            </a-form-item>
            
            <a-form-item>
              <a-button type="primary" size="large" :loading="loading" @click="onCalculate">
                计算八字
              </a-button>
            </a-form-item>
          </a-form>
        </div>
      </section>

      <section class="bazi-result" v-if="baziResult">
        <div class="container">
          <a-row :gutter="24">
            <a-col :span="12">
              <a-card title="八字命盘">
                <a-descriptions :column="2">
                  <a-descriptions-item label="年柱">{{ baziResult.bazi?.year?.branch }}</a-descriptions-item>
                  <a-descriptions-item label="月柱">{{ baziResult.bazi?.month?.branch }}</a-descriptions-item>
                  <a-descriptions-item label="日柱">{{ baziResult.bazi?.day?.branch }}</a-descriptions-item>
                  <a-descriptions-item label="时柱">{{ baziResult.bazi?.hour?.branch }}</a-descriptions-item>
                </a-descriptions>
                
                <a-divider>五行分布</a-divider>
                <a-row :gutter="16">
                  <a-col :span="4" v-for="(count, element) in baziResult.fiveElements" :key="element">
                    <div class="element-item">
                      <span class="element-name">{{ element }}</span>
                      <span class="element-count">{{ count }}</span>
                    </div>
                  </a-col>
                </a-row>
              </a-card>
            </a-col>
            
            <a-col :span="12">
              <a-card title="命理分析">
                <a-descriptions :column="1">
                  <a-descriptions-item label="日主">{{ baziResult.dayMaster }}</a-descriptions-item>
                  <a-descriptions-item label="日主旺衰">{{ baziResult.strengthLevel }} ({{ baziResult.dayMasterStrength }}分)</a-descriptions-item>
                  <a-descriptions-item label="喜用神">
                    <a-tag color="green">{{ baziResult.xiYongSheng }}</a-tag>
                  </a-descriptions-item>
                  <a-descriptions-item label="忌神">
                    <a-tag color="red">{{ baziResult.jiShen }}</a-tag>
                  </a-descriptions-item>
                </a-descriptions>
              </a-card>
            </a-col>
          </a-row>
        </div>
      </section>

      <section class="name-selection" v-if="baziResult">
        <div class="container">
          <h3>选择名字五行</h3>
          <a-form layout="inline">
            <a-form-item label="首字五行">
              <a-select v-model:value="form.firstElement" style="width: 120px">
                <a-select-option value="">不限</a-select-option>
                <a-select-option value="金">金</a-select-option>
                <a-select-option value="木">木</a-select-option>
                <a-select-option value="水">水</a-select-option>
                <a-select-option value="火">火</a-select-option>
                <a-select-option value="土">土</a-select-option>
              </a-select>
            </a-form-item>
            <a-form-item label="末字五行">
              <a-select v-model:value="form.lastElement" style="width: 120px">
                <a-select-option value="">不限</a-select-option>
                <a-select-option value="金">金</a-select-option>
                <a-select-option value="木">木</a-select-option>
                <a-select-option value="水">水</a-select-option>
                <a-select-option value="火">火</a-select-option>
                <a-select-option value="土">土</a-select-option>
              </a-select>
            </a-form-item>
            <a-form-item>
              <a-button type="primary" @click="onSearchNames">开始起名</a-button>
            </a-form-item>
          </a-form>
        </div>
      </section>

      <section class="results-section" v-if="results.length > 0">
        <div class="container">
          <h2>为您找到 {{ total }} 个符合八字的好名字</h2>
          
          <a-list :data-source="results" :loading="loading" :pagination="pagination">
            <template #renderItem="{ item }">
              <a-list-item>
                <a-list-item-meta>
                  <template #title>
                    {{ form.surname }}{{ item.given_name }}
                    <a-tag :color="getScoreColor(item.total_score)">{{ item.total_score }}分</a-tag>
                  </template>
                  <template #description>
                    {{ item.pinyin }} | 五行: {{ item.five_element }} | {{ item.meaning }}
                  </template>
                </a-list-item-meta>
                <template #actions>
                  <a-button type="link">查看详情</a-button>
                  <a-button type="link">收藏</a-button>
                </template>
              </a-list-item>
            </template>
          </a-list>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { message } from 'ant-design-vue'
import request from '@/utils/request'

const loading = ref(false)
const baziResult = ref(null)
const results = ref([])
const total = ref(0)

const currentYear = new Date().getFullYear()
const years = Array.from({ length: 100 }, (_, i) => currentYear - i)

const form = reactive({
  surname: '',
  gender: 1,
  birthYear: currentYear - 1,
  birthMonth: 1,
  birthDay: 1,
  birthTime: '子',
  firstElement: '',
  lastElement: ''
})

const timeOptions = [
  { value: '子', label: '子时' },
  { value: '丑', label: '丑时' },
  { value: '寅', label: '寅时' },
  { value: '卯', label: '卯时' },
  { value: '辰', label: '辰时' },
  { value: '巳', label: '巳时' },
  { value: '午', label: '午时' },
  { value: '未', label: '未时' },
  { value: '申', label: '申时' },
  { value: '酉', label: '酉时' },
  { value: '戌', label: '戌时' },
  { value: '亥', label: '亥时' }
]

const pagination = reactive({
  current: 1,
  pageSize: 20,
  total: 0,
  onChange: (page) => {
    pagination.current = page
    fetchNames()
  }
})

const onCalculate = async () => {
  if (!form.surname) {
    message.warning('请输入姓氏')
    return
  }

  loading.value = true

  try {
    const res = await request.post('/v1/bazi/calculate', {
      year: form.birthYear,
      month: form.birthMonth,
      day: form.birthDay,
      hour: getHourFromTime(form.birthTime),
      gender: form.gender
    })

    baziResult.value = res.data
    message.success('八字计算成功')
  } catch (error) {
    baziResult.value = generateMockBazi()
    message.success('八字计算成功(演示数据)')
  } finally {
    loading.value = false
  }
}

function generateMockBazi() {
  const elements = ['金', '木', '水', '火', '土']
  return {
    bazi: {
      year: { branch: '丙午', gan: '丙', zhi: '午', wuXing: '火' },
      month: { branch: '辛卯', gan: '辛', zhi: '卯', wuXing: '木' },
      day: { branch: '乙未', gan: '乙', zhi: '未', wuXing: '木' },
      hour: { branch: '戊子', gan: '戊', zhi: '子', wuXing: '水' }
    },
    fiveElements: { 木: 2, 火: 1, 土: 2, 金: 1, 水: 2 },
    dayMasterStrength: -15,
    xiYongSheng: '水',
    jiShen: '火',
    dayMaster: '乙',
    strengthLevel: '偏弱'
  }
}

const getHourFromTime = (time) => {
  const timeMap = {
    '子': 0, '丑': 2, '寅': 4, '卯': 6,
    '辰': 8, '巳': 10, '午': 12, '未': 14,
    '申': 16, '酉': 18, '戌': 20, '亥': 22
  }
  return timeMap[time] || 0
}

const onSearchNames = async () => {
  loading.value = true

  try {
    const res = await request.post('/v1/names/recommend', {
      surname: form.surname,
      gender: form.gender,
      birthDate: `${form.birthYear}-${form.birthMonth}-${form.birthDay}`,
      birthTime: getHourFromTime(form.birthTime),
      firstElement: form.firstElement || undefined,
      lastElement: form.lastElement || undefined,
      page: pagination.current,
      pageSize: pagination.pageSize
    })

    results.value = res.data.items
    total.value = res.data.total
    pagination.total = res.data.total
  } catch (error) {
    results.value = generateMockNames()
    total.value = results.value.length
    pagination.total = results.value.length
  } finally {
    loading.value = false
  }
}

function generateMockNames() {
  const boyNames = ['俊豪', '煜晨', '铭轩', '梓翔', '昊然']
  const girlNames = ['欣怡', '梓涵', '雨桐', '诗涵', '思琪']
  const names = form.gender === 1 ? boyNames : girlNames
  const elements = ['金', '木', '水', '火', '土']
  
  return names.map((givenName, index) => ({
    id: index + 1,
    given_name: givenName,
    pinyin: `${form.surname.toLowerCase()} ${givenName.split('').map(c => getPinyin(c)).join(' ')}`,
    five_element: elements[Math.floor(Math.random() * elements.length)],
    total_score: 85 + Math.floor(Math.random() * 15),
    meaning: '寓意美好，吉祥如意'
  }))
}

function getPinyin(char) {
  const map = { '俊': 'jun', '豪': 'hao', '煜': 'yu', '晨': 'chen', '铭': 'ming', '轩': 'xuan', '梓': 'zi', '翔': 'xiang', '昊': 'hao', '然': 'ran', '欣': 'xin', '怡': 'yi', '涵': 'han', '雨': 'yu', '桐': 'tong', '诗': 'shi', '思': 'si', '琪': 'qi' }
  return map[char] || 'yi'
}

const fetchNames = async () => {
  await onSearchNames()
}

const getScoreColor = (score) => {
  if (score >= 90) return 'green'
  if (score >= 80) return 'blue'
  if (score >= 70) return 'orange'
  return 'red'
}
</script>

<style lang="scss" scoped>
.bazi-name-page {
  min-height: 100vh;
  background: #f5f5f5;
}

.header {
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);

  .container {
    display: flex;
    align-items: center;
    height: 64px;
  }

  .logo {
    font-size: 20px;
    font-weight: bold;
    color: #d4380d;
    margin-right: 48px;
  }

  .nav {
    display: flex;
    gap: 32px;

    a {
      color: #333;
      text-decoration: none;

      &:hover, &.active {
        color: #d4380d;
      }
    }
  }
}

.main {
  padding-bottom: 48px;
}

.form-section {
  background: linear-gradient(135deg, #d4380d 0%, #ff4d4f 100%);
  padding: 48px 0;
  color: #fff;
  text-align: center;

  h1 {
    font-size: 32px;
    margin-bottom: 8px;
  }

  .subtitle {
    font-size: 16px;
    margin-bottom: 32px;
  }

  .name-form {
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    padding: 24px;
    border-radius: 8px;
  }
}

.bazi-result, .name-selection {
  padding: 32px 0;
}

.results-section {
  padding: 32px 0;
  
  h2 {
    margin-bottom: 24px;
  }
}

.element-item {
  text-align: center;
  
  .element-name {
    display: block;
    font-size: 16px;
  }
  
  .element-count {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #d4380d;
  }
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}
</style>

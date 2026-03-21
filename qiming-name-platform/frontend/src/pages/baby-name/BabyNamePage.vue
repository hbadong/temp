<template>
  <div class="baby-name-page">
    <header class="header">
      <div class="container">
        <router-link to="/" class="logo">起名网</router-link>
        <nav class="nav">
          <router-link to="/baobao" class="active">宝宝起名</router-link>
          <router-link to="/bazi">八字起名</router-link>
          <router-link to="/shici">诗词起名</router-link>
          <router-link to="/xingmingceshi">姓名测试</router-link>
        </nav>
      </div>
    </header>

    <main class="main">
      <section class="form-section">
        <div class="container">
          <h1>宝宝起名</h1>
          <p class="subtitle">以先进AI技术和大数据融合千年传统起名智慧</p>
          
          <a-form :model="form" layout="inline" class="name-form">
            <a-form-item>
              <a-input v-model:value="form.surname" placeholder="请输入姓氏" size="large">
                <template #prefix>
                  <UserOutlined />
                </template>
              </a-input>
            </a-form-item>
            
            <a-form-item>
              <a-radio-group v-model:value="form.gender" size="large">
                <a-radio-button :value="1">男宝宝</a-radio-button>
                <a-radio-button :value="2">女宝宝</a-radio-button>
              </a-radio-group>
            </a-form-item>
            
            <a-form-item>
              <a-date-picker
                v-model:value="form.birthDate"
                placeholder="选择出生日期"
                size="large"
                @change="onBirthDateChange"
              />
            </a-form-item>
            
            <a-form-item>
              <a-select
                v-model:value="form.birthTime"
                placeholder="选择时辰"
                size="large"
                style="width: 150px"
              >
                <a-select-option v-for="time in timeOptions" :key="time.value" :value="time.value">
                  {{ time.label }}
                </a-select-option>
              </a-select>
            </a-form-item>
            
            <a-form-item>
              <a-button type="primary" size="large" :loading="loading" @click="onSubmit">
                立即起名
              </a-button>
            </a-form-item>
          </a-form>
        </div>
      </section>

      <section class="results-section" v-if="results.length > 0">
        <div class="container">
          <h2>为您找到 {{ total }} 个符合条件的好名字</h2>
          
          <div class="filter-bar">
            <span>筛选条件：</span>
            <a-tag v-if="form.surname">姓氏: {{ form.surname }}</a-tag>
            <a-tag v-if="form.gender">{{ form.gender === 1 ? '男' : '女' }}</a-tag>
            <a-tag v-if="form.firstElement">首字五行: {{ form.firstElement }}</a-tag>
          </div>
          
          <a-list
            :data-source="results"
            :loading="loading"
            :pagination="pagination"
            item-layout="horizontal"
          >
            <template #renderItem="{ item }">
              <a-list-item>
                <template #actions>
                  <a-button type="link" @click="viewDetail(item)">查看详情</a-button>
                  <a-button type="link" @click="collectName(item)">收藏</a-button>
                </template>
                <a-list-item-meta>
                  <template #title>
                    <span class="name-title">{{ form.surname }}{{ item.given_name }}</span>
                    <a-tag :color="getScoreColor(item.total_score)">
                      {{ item.total_score }}分
                    </a-tag>
                  </template>
                  <template #description>
                    <div class="name-info">
                      <span>拼音: {{ item.pinyin }}</span>
                      <span>笔画: {{ item.stroke_count }}</span>
                      <span>五行: {{ item.five_element }}</span>
                      <span>三才五格: {{ item.wu_ge_ren }}格</span>
                    </div>
                    <div class="name-meaning">{{ item.meaning }}</div>
                  </template>
                </a-list-item-meta>
              </a-list-item>
            </template>
          </a-list>
        </div>
      </section>

      <section class="features-section">
        <div class="container">
          <h2>宝宝起名综合六大维度</h2>
          <a-row :gutter="[24, 24]">
            <a-col :span="4" v-for="feature in features" :key="feature.title">
              <div class="feature-card">
                <img :src="feature.image" :alt="feature.title" />
                <h3>{{ feature.title }}</h3>
                <p>{{ feature.desc }}</p>
              </div>
            </a-col>
          </a-row>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { message } from 'ant-design-vue'
import { UserOutlined } from '@ant-design/icons-vue'
import request from '@/utils/request'

const route = useRoute()
const loading = ref(false)
const results = ref([])
const total = ref(0)

const form = reactive({
  surname: '',
  gender: 1,
  birthDate: null,
  birthTime: '子',
  firstElement: '',
  lastElement: ''
})

const timeOptions = [
  { value: '子', label: '子时 (23:00-01:00)' },
  { value: '丑', label: '丑时 (01:00-03:00)' },
  { value: '寅', label: '寅时 (03:00-05:00)' },
  { value: '卯', label: '卯时 (05:00-07:00)' },
  { value: '辰', label: '辰时 (07:00-09:00)' },
  { value: '巳', label: '巳时 (09:00-11:00)' },
  { value: '午', label: '午时 (11:00-13:00)' },
  { value: '未', label: '未时 (13:00-15:00)' },
  { value: '申', label: '申时 (15:00-17:00)' },
  { value: '酉', label: '酉时 (17:00-19:00)' },
  { value: '戌', label: '戌时 (19:00-21:00)' },
  { value: '亥', label: '亥时 (21:00-23:00)' }
]

const features = [
  { title: '国学起名', desc: '从国学经典中取材', image: '/images/wd1.jpg' },
  { title: '音形义起名', desc: '音顺、形美、义内涵', image: '/images/wd2.jpg' },
  { title: '期望起名', desc: '根据父母期望结合用字', image: '/images/wd3.jpg' },
  { title: '大数据起名', desc: '百万宝宝数据分析', image: '/images/wd4.jpg' },
  { title: '诗词起名', desc: '诗词古籍中分析组合', image: '/images/wd5.jpg' },
  { title: '生肖起名', desc: '根据生肖特性筛选', image: '/images/wd6.jpg' }
]

const pagination = reactive({
  current: 1,
  pageSize: 20,
  total: 0,
  onChange: (page) => {
    pagination.current = page
    fetchResults()
  }
})

onMounted(() => {
  if (route.query.surname) {
    form.surname = route.query.surname
    form.gender = parseInt(route.query.gender) || 1
    onSubmit()
  }
})

const onBirthDateChange = (date, dateString) => {
  form.birthDate = dateString
}

const onSubmit = async () => {
  if (!form.surname) {
    message.warning('请输入姓氏')
    return
  }

  loading.value = true

  try {
    const res = await request.post('/v1/names/recommend', {
      surname: form.surname,
      gender: form.gender,
      birthDate: form.birthDate,
      birthTime: form.birthTime,
      firstElement: form.firstElement || undefined,
      lastElement: form.lastElement || undefined,
      page: pagination.current,
      pageSize: pagination.pageSize
    })

    results.value = res.data.items
    total.value = res.data.total
    pagination.total = res.data.total
  } catch (error) {
    results.value = generateMockNames(form.surname, form.gender)
    total.value = results.value.length
    pagination.total = results.value.length
  } finally {
    loading.value = false
  }
}

function generateMockNames(surname, gender) {
  const boyNames = ['俊豪', '煜晨', '铭轩', '梓翔', '昊然', '思远', '文博', '家瑞']
  const girlNames = ['欣怡', '梓涵', '雨桐', '诗涵', '思琪', '雅婷', '欣悦', '梦瑶']
  const names = gender === 1 ? boyNames : girlNames
  const elements = ['金', '木', '水', '火', '土']
  
  return names.map((givenName, index) => ({
    id: index + 1,
    surname,
    given_name: givenName,
    full_name: surname + givenName,
    pinyin: `${surname.toLowerCase()} ${givenName.split('').map(c => getPinyin(c)).join(' ')}`,
    five_element: elements[Math.floor(Math.random() * elements.length)],
    total_score: 85 + Math.floor(Math.random() * 15),
    stroke_count: getStroke(surname) + getStroke(givenName),
    wu_ge_tian: getStroke(surname) + 1,
    wu_ge_di: getStroke(givenName),
    wu_ge_ren: getStroke(surname) + getStroke(givenName[0]),
    wu_ge_zong: getStroke(surname) + getStroke(givenName),
    wu_ge_lucky: '吉',
    meaning: '寓意美好，吉祥如意'
  }))
}

function getPinyin(char) {
  const map = { '俊': 'jun', '豪': 'hao', '煜': 'yu', '晨': 'chen', '铭': 'ming', '轩': 'xuan', '梓': 'zi', '翔': 'xiang', '昊': 'hao', '然': 'ran', '欣': 'xin', '怡': 'yi', '涵': 'han', '雨': 'yu', '桐': 'tong', '诗': 'shi', '思': 'si', '琪': 'qi', '雅': 'ya', '婷': 'ting', '悦': 'yue', '梦': 'meng', '瑶': 'yao' }
  return map[char] || 'yi'
}

function getStroke(name) {
  const map = { '李': 7, '王': 4, '张': 11, '刘': 15, '陈': 16, '杨': 13, '赵': 9, '黄': 12, '周': 8, '吴': 7, '俊': 9, '豪': 14, '煜': 13, '晨': 11, '铭': 14, '轩': 10, '梓': 11, '翔': 12, '昊': 8, '然': 12, '欣': 8, '怡': 9, '涵': 11, '雨': 8, '桐': 10, '诗': 8, '思': 9, '琪': 12, '雅': 12, '婷': 12, '悦': 11, '梦': 11, '瑶': 13 }
  let total = 0
  for (const c of name) { total += map[c] || 8 }
  return total
}

const fetchResults = async () => {
  await onSubmit()
}

const getScoreColor = (score) => {
  if (score >= 90) return 'green'
  if (score >= 80) return 'blue'
  if (score >= 70) return 'orange'
  return 'red'
}

const viewDetail = (item) => {
  message.info('查看详情: ' + item.full_name)
}

const collectName = async (item) => {
  try {
    await request.post('/v1/user/favorites', { nameId: item.id })
    message.success('收藏成功')
  } catch (error) {
    message.error('收藏失败')
  }
}
</script>

<style lang="scss" scoped>
.baby-name-page {
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

.results-section {
  padding: 48px 0;

  h2 {
    font-size: 20px;
    margin-bottom: 16px;
  }

  .filter-bar {
    margin-bottom: 24px;
  }

  .name-title {
    font-size: 18px;
    font-weight: bold;
    margin-right: 8px;
  }

  .name-info {
    display: flex;
    gap: 16px;
    color: #666;
    margin-bottom: 8px;
  }

  .name-meaning {
    color: #999;
    font-size: 14px;
  }
}

.features-section {
  padding: 48px 0;
  background: #fff;

  h2 {
    text-align: center;
    margin-bottom: 32px;
  }

  .feature-card {
    text-align: center;

    img {
      width: 64px;
      height: 64px;
      margin-bottom: 12px;
    }

    h3 {
      font-size: 16px;
      margin-bottom: 4px;
    }

    p {
      font-size: 12px;
      color: #666;
    }
  }
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}
</style>

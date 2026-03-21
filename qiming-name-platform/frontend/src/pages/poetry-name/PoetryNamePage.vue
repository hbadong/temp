<template>
  <div class="poetry-name-page">
    <header class="header">
      <div class="container">
        <router-link to="/" class="logo">起名网</router-link>
        <nav class="nav">
          <router-link to="/baobao">宝宝起名</router-link>
          <router-link to="/bazi">八字起名</router-link>
          <router-link to="/shici" class="active">诗词起名</router-link>
          <router-link to="/xingmingceshi">姓名测试</router-link>
        </nav>
      </div>
    </header>

    <main class="main">
      <section class="form-section">
        <div class="container">
          <h1>诗词起名</h1>
          <p class="subtitle">从二十多万诗词古文中取字，确保每个名字意蕴优美、诗情画意</p>
          
          <a-form :model="form" layout="inline" class="name-form">
            <a-form-item>
              <a-input v-model:value="form.surname" placeholder="姓氏" size="large" />
            </a-form-item>
            
            <a-form-item>
              <a-radio-group v-model:value="form.gender" size="large">
                <a-radio-button :value="1">男宝宝</a-radio-button>
                <a-radio-button :value="2">女宝宝</a-radio-button>
              </a-radio-group>
            </a-form-item>
            
            <a-form-item label="诗词风格">
              <a-select v-model:value="form.style" placeholder="选择风格" size="large" style="width: 150px">
                <a-select-option value="">不限</a-select-option>
                <a-select-option value="tang">唐诗</a-select-option>
                <a-select-option value="song">宋词</a-select-option>
                <a-select-option value="shijing">诗经</a-select-option>
                <a-select-option value="chuci">楚辞</a-select-option>
              </a-select>
            </a-form-item>
            
            <a-form-item>
              <a-button type="primary" size="large" :loading="loading" @click="onSearch">
                开始起名
              </a-button>
            </a-form-item>
          </a-form>
        </div>
      </section>

      <section class="results-section" v-if="results.length > 0">
        <div class="container">
          <h2>为您找到 {{ total }} 个出自诗词的好名字</h2>
          
          <a-list :data-source="results" :loading="loading" :pagination="pagination" item-layout="horizontal">
            <template #renderItem="{ item }">
              <a-list-item>
                <a-list-item-meta>
                  <template #title>
                    <span class="name-title">{{ form.surname }}{{ item.given_name }}</span>
                    <a-tag :color="getScoreColor(item.total_score)">{{ item.total_score }}分</a-tag>
                  </template>
                  <template #description>
                    <div class="poetry-info" v-if="item.source_sentence">
                      <a-icon type="book" />
                      <span>{{ item.source_sentence }}</span>
                      <span class="source">—— {{ item.source_author }}</span>
                    </div>
                    <div class="name-meaning">{{ item.meaning }}</div>
                  </template>
                </a-list-item-meta>
                <template #actions>
                  <a-button type="link" @click="viewDetail(item)">查看详情</a-button>
                  <a-button type="link" @click="collectName(item)">收藏</a-button>
                </template>
              </a-list-item>
            </template>
          </a-list>
        </div>
      </section>

      <section class="features-section">
        <div class="container">
          <h2>唐诗宋词传承千年的诗意起名艺术</h2>
          <a-row :gutter="[24, 24]">
            <a-col :span="6" v-for="style in poetryStyles" :key="style.name">
              <router-link :to="`/shici?style=${style.value}`" class="style-card">
                <img :src="style.image" :alt="style.name" />
                <span>{{ style.name }}</span>
              </router-link>
            </a-col>
          </a-row>
        </div>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRoute } from 'vue-router'
import { message } from 'ant-design-vue'
import request from '@/utils/request'

const route = useRoute()
const loading = ref(false)
const results = ref([])
const total = ref(0)

const form = reactive({
  surname: '',
  gender: 1,
  style: ''
})

const poetryStyles = [
  { name: '唐诗起名', value: 'tang', image: '/images/tang.png' },
  { name: '诗经起名', value: 'shijing', image: '/images/shi.png' },
  { name: '宋词起名', value: 'song', image: '/images/song.png' },
  { name: '楚辞起名', value: 'chuci', image: '/images/ci.png' }
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

const getScoreColor = (score) => {
  if (score >= 90) return 'green'
  if (score >= 80) return 'blue'
  if (score >= 70) return 'orange'
  return 'red'
}

const onSearch = async () => {
  if (!form.surname) {
    message.warning('请输入姓氏')
    return
  }

  loading.value = true

  try {
    const res = await request.post('/v1/names/recommend', {
      surname: form.surname,
      gender: form.gender,
      poetryStyle: form.style || undefined,
      page: pagination.current,
      pageSize: pagination.pageSize
    })

    results.value = res.data.items
    total.value = res.data.total
    pagination.total = res.data.total
  } catch (error) {
    message.error('查询失败')
  } finally {
    loading.value = false
  }
}

const fetchResults = async () => {
  await onSearch()
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
.poetry-name-page { min-height: 100vh; background: #f5f5f5; }
.header { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.08); .container { display: flex; align-items: center; height: 64px; } .logo { font-size: 20px; font-weight: bold; color: #d4380d; margin-right: 48px; } .nav { display: flex; gap: 32px; a { color: #333; text-decoration: none; &:hover, &.active { color: #d4380d; } } } }
.main { padding-bottom: 48px; }
.form-section { background: linear-gradient(135deg, #d4380d 0%, #ff4d4f 100%); padding: 48px 0; color: #fff; text-align: center; h1 { font-size: 32px; margin-bottom: 8px; } .subtitle { font-size: 16px; margin-bottom: 32px; } .name-form { justify-content: center; background: rgba(255,255,255,0.1); padding: 24px; border-radius: 8px; } }
.results-section { padding: 32px 0; h2 { margin-bottom: 24px; } .name-title { font-size: 18px; font-weight: bold; margin-right: 8px; } .poetry-info { color: #666; margin-bottom: 8px; .source { margin-left: 8px; color: #999; } } }
.features-section { padding: 48px 0; background: #fff; h2 { text-align: center; margin-bottom: 32px; } .style-card { display: flex; flex-direction: column; align-items: center; padding: 24px; background: #fafafa; border-radius: 8px; text-decoration: none; color: #333; transition: transform 0.2s; &:hover { transform: translateY(-4px); } img { width: 64px; height: 64px; margin-bottom: 12px; } } }
.container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
</style>

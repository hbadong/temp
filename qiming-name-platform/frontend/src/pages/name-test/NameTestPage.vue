<template>
  <div class="name-test-page">
    <header class="header">
      <div class="container">
        <router-link to="/" class="logo">起名网</router-link>
        <nav class="nav">
          <router-link to="/baobao">宝宝起名</router-link>
          <router-link to="/bazi">八字起名</router-link>
          <router-link to="/shici">诗词起名</router-link>
          <router-link to="/xingmingceshi" class="active">姓名测试</router-link>
        </nav>
      </div>
    </header>

    <main class="main">
      <section class="form-section">
        <div class="container">
          <h1>姓名测试打分</h1>
          <p class="subtitle">基于九维测名法进行全方位姓名评测</p>
          
          <a-form :model="form" layout="inline" class="test-form">
            <a-form-item>
              <a-input v-model:value="form.name" placeholder="请输入姓名" size="large" />
            </a-form-item>
            
            <a-form-item>
              <a-radio-group v-model:value="form.gender" size="large">
                <a-radio-button :value="1">男</a-radio-button>
                <a-radio-button :value="2">女</a-radio-button>
              </a-radio-group>
            </a-form-item>
            
            <a-form-item>
              <a-button type="primary" size="large" :loading="loading" @click="onTest">
                立即测名打分
              </a-button>
            </a-form-item>
          </a-form>
        </div>
      </section>

      <section class="result-section" v-if="result">
        <div class="container">
          <a-row :gutter="24">
            <a-col :span="8">
              <a-card class="score-card">
                <div class="total-score">
                  <a-progress
                    type="circle"
                    :percent="result.total"
                    :width="160"
                    :stroke-color="getScoreColor(result.total)"
                  />
                  <div class="score-label">{{ result.level }}</div>
                </div>
                <div class="score-detail">
                  <p>三才五格：{{ result.wuGe?.ren?.value }}格 ({{ result.wuGe?.ren?.lucky }})</p>
                  <p>五行属性：{{ result.fiveElement }}</p>
                  <p>笔画数：{{ result.strokes?.join(' + ') }} = {{ result.strokes?.reduce((a, b) => a + b, 0) }}</p>
                </div>
              </a-card>
            </a-col>
            
            <a-col :span="16">
              <a-card title="九维评分">
                <a-row :gutter="[16, 16]">
                  <a-col :span="8" v-for="(score, key) in result.scores" :key="key">
                    <div class="dimension-item">
                      <span class="dimension-name">{{ getDimensionName(key) }}</span>
                      <a-progress :percent="score" :stroke-color="getScoreColor(score)" size="small" />
                    </div>
                  </a-col>
                </a-row>
              </a-card>
              
              <a-card title="三才五格详解" style="margin-top: 16px">
                <a-descriptions :column="5" size="small">
                  <a-descriptions-item label="天格">
                    {{ result.wuGe?.tian?.value }} ({{ result.wuGe?.tian?.lucky }})
                  </a-descriptions-item>
                  <a-descriptions-item label="地格">
                    {{ result.wuGe?.di?.value }} ({{ result.wuGe?.di?.lucky }})
                  </a-descriptions-item>
                  <a-descriptions-item label="人格">
                    {{ result.wuGe?.ren?.value }} ({{ result.wuGe?.ren?.lucky }})
                  </a-descriptions-item>
                  <a-descriptions-item label="外格">
                    {{ result.wuGe?.wai?.value }} ({{ result.wuGe?.wai?.lucky }})
                  </a-descriptions-item>
                  <a-descriptions-item label="总格">
                    {{ result.wuGe?.zong?.value }} ({{ result.wuGe?.zong?.lucky }})
                  </a-descriptions-item>
                </a-descriptions>
              </a-card>
            </a-col>
          </a-row>
        </div>
      </section>

      <section class="features-section">
        <div class="container">
          <h2>九维测名法介绍</h2>
          <a-row :gutter="[24, 24]">
            <a-col :span="8" v-for="dim in dimensions" :key="dim.name">
              <a-card>
                <h3>{{ dim.name }}</h3>
                <p>{{ dim.desc }}</p>
              </a-card>
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
const result = ref(null)

const form = reactive({
  name: '',
  gender: 1
})

const dimensions = [
  { name: '音维度', desc: '音韵和谐度、声调搭配分析' },
  { name: '形维度', desc: '字形结构美感、笔画均匀度' },
  { name: '义维度', desc: '寓意内涵深度、歧义分析' },
  { name: '数维度', desc: '三才五格数理吉凶分析' },
  { name: '理维度', desc: '五行相生相克平衡分析' },
  { name: '运维度', desc: '名字对运势的影响分析' },
  { name: '境维度', desc: '名字格局高低评判' },
  { name: '德维度', desc: '品德暗示寓意分析' },
  { name: '命维度', desc: '名字与命主契合度分析' }
]

const dimensionNames = {
  yin: '音维度',
  xing: '形维度',
  yi: '义维度',
  shu: '数维度',
  li: '理维度',
  yun: '运维度',
  jing: '境维度',
  de: '德维度',
  ming: '命维度'
}

const getDimensionName = (key) => dimensionNames[key] || key

const getScoreColor = (score) => {
  if (score >= 90) return '#52c41a'
  if (score >= 80) return '#1890ff'
  if (score >= 70) return '#faad14'
  return '#f5222d'
}

const onTest = async () => {
  if (!form.name) {
    message.warning('请输入姓名')
    return
  }

  loading.value = true

  try {
    const surname = form.name.charAt(0)
    const givenName = form.name.slice(1)

    const res = await request.post('/v1/names/test', {
      name: form.name,
      surname,
      givenName,
      gender: form.gender
    })

    result.value = res.data
  } catch (error) {
    message.error('测试失败，请重试')
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.name-test-page {
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

  .test-form {
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    padding: 24px;
    border-radius: 8px;
  }
}

.result-section {
  padding: 32px 0;

  .score-card {
    text-align: center;

    .total-score {
      padding: 24px 0;
    }

    .score-label {
      font-size: 20px;
      font-weight: bold;
      margin-top: 16px;
    }

    .score-detail {
      text-align: left;
      padding-top: 16px;
      border-top: 1px solid #f0f0f0;

      p {
        margin-bottom: 8px;
        color: #666;
      }
    }
  }

  .dimension-item {
    .dimension-name {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
    }
  }
}

.features-section {
  padding: 48px 0;
  background: #fff;

  h2 {
    text-align: center;
    margin-bottom: 32px;
  }
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}
</style>

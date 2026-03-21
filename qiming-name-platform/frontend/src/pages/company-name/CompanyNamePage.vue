<template>
  <div class="company-name-page">
    <header class="header">
      <div class="container">
        <router-link to="/" class="logo">起名网</router-link>
        <nav class="nav">
          <router-link to="/baobao">宝宝起名</router-link>
          <router-link to="/bazi">八字起名</router-link>
          <router-link to="/gongsiqiming" class="active">公司起名</router-link>
        </nav>
      </div>
    </header>

    <main class="main">
      <section class="form-section">
        <div class="container">
          <h1>公司起名</h1>
          <p class="subtitle">资深命名专家与品牌策划大师联手打造，为企业量身定制独特专属好名</p>
          
          <a-form :model="form" layout="inline" class="name-form">
            <a-form-item>
              <a-input v-model:value="form.industry" placeholder="所属行业" size="large" />
            </a-form-item>
            
            <a-form-item>
              <a-input v-model:value="form.founderName" placeholder="创始人姓名" size="large" />
            </a-form-item>
            
            <a-form-item>
              <a-button type="primary" size="large" :loading="loading" @click="onSearch">
                开始起名
              </a-button>
            </a-form-item>
          </a-form>
        </div>
      </section>

      <section class="features-section">
        <div class="container">
          <h2>公司起名优势</h2>
          <a-row :gutter="[24, 24]">
            <a-col :span="6" v-for="feature in features" :key="feature.title">
              <div class="feature-card">
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
import { ref, reactive } from 'vue'

const loading = ref(false)

const form = reactive({
  industry: '',
  founderName: ''
})

const features = [
  { title: '行业分析', desc: '深入了解行业特征与市场需求' },
  { title: '品牌定位', desc: '精准定位品牌调性与目标受众' },
  { title: '易经智慧', desc: '融汇易经八卦与五行相生相克' },
  { title: '法律合规', desc: '确保商标注册与名称合规' }
]

const onSearch = () => {
  loading.value = true
  setTimeout(() => {
    loading.value = false
  }, 1000)
}
</script>

<style lang="scss" scoped>
.company-name-page { min-height: 100vh; background: #f5f5f5; }
.header { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.08); .container { display: flex; align-items: center; height: 64px; } .logo { font-size: 20px; font-weight: bold; color: #d4380d; margin-right: 48px; } .nav { display: flex; gap: 32px; a { color: #333; text-decoration: none; &:hover, &.active { color: #d4380d; } } } }
.main { padding-bottom: 48px; }
.form-section { background: linear-gradient(135deg, #d4380d 0%, #ff4d4f 100%); padding: 48px 0; color: #fff; text-align: center; h1 { font-size: 32px; margin-bottom: 8px; } .subtitle { font-size: 16px; margin-bottom: 32px; } .name-form { justify-content: center; background: rgba(255,255,255,0.1); padding: 24px; border-radius: 8px; } }
.features-section { padding: 48px 0; background: #fff; h2 { text-align: center; margin-bottom: 32px; } .feature-card { text-align: center; h3 { font-size: 18px; margin-bottom: 8px; } p { color: #666; font-size: 14px; } } }
.container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
</style>

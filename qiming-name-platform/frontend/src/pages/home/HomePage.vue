<template>
  <div class="home-page">
    <header class="header">
      <div class="container">
        <div class="logo">
          <img src="@/assets/images/logo.png" alt="起名网" />
          <span>起名网</span>
        </div>
        <nav class="nav">
          <router-link to="/">首页</router-link>
          <router-link to="/baobao">宝宝起名</router-link>
          <router-link to="/bazi">八字起名</router-link>
          <router-link to="/shici">诗词起名</router-link>
          <router-link to="/xingmingceshi">姓名测试</router-link>
          <router-link to="/gongsiqiming">公司起名</router-link>
          <router-link to="/kxzd">康熙字典</router-link>
          <router-link to="/baijiaxing">百家姓</router-link>
        </nav>
        <div class="user-actions">
          <a-button type="text" @click="showLoginModal = true">登录</a-button>
          <a-button type="primary" @click="showRegisterModal = true">注册</a-button>
        </div>
      </div>
    </header>

    <main class="main">
      <section class="hero">
        <div class="hero-content">
          <h1>AI智能起名 · 大数据 · 国学智慧</h1>
          <p>专注科学智能宝宝起名，测名字打分平台</p>
          
          <div class="search-box">
            <a-input-search
              v-model:value="searchKeyword"
              placeholder="输入姓氏或名字搜索"
              size="large"
              @search="onSearch"
            >
              <template #prefix>
                <SearchOutlined />
              </template>
            </a-input-search>
          </div>
        </div>
      </section>

      <section class="services">
        <div class="container">
          <h2 class="section-title">起名服务</h2>
          <a-row :gutter="[24, 24]">
            <a-col :xs="12" :sm="8" :md="4" v-for="service in services" :key="service.path">
              <router-link :to="service.path" class="service-card">
                <img :src="service.image" :alt="service.title" />
                <span>{{ service.title }}</span>
              </router-link>
            </a-col>
          </a-row>
        </div>
      </section>

      <section class="tools">
        <div class="container">
          <a-row :gutter="[24, 24]">
            <a-col :xs="24" :md="12">
              <div class="tool-card baby-name">
                <h3>宝宝起名</h3>
                <p>以先进AI技术和大数据融合千年传统起名智慧</p>
                <div class="tool-form">
                  <a-input v-model:value="babyForm.surname" placeholder="姓氏" />
                  <a-radio-group v-model:value="babyForm.gender">
                    <a-radio :value="1">男</a-radio>
                    <a-radio :value="2">女</a-radio>
                  </a-radio-group>
                  <a-button type="primary" @click="onBabyName">立即起名</a-button>
                </div>
              </div>
            </a-col>
            <a-col :xs="24" :md="12">
              <div class="tool-card name-test">
                <h3>姓名测试</h3>
                <p>基于九维测名法进行全方位姓名评测</p>
                <div class="tool-form">
                  <a-input v-model:value="testForm.name" placeholder="姓名" />
                  <a-radio-group v-model:value="testForm.gender">
                    <a-radio :value="1">男</a-radio>
                    <a-radio :value="2">女</a-radio>
                  </a-radio-group>
                  <a-button type="primary" @click="onNameTest">立即测名打分</a-button>
                </div>
              </div>
            </a-col>
          </a-row>
        </div>
      </section>

      <section class="almanac">
        <div class="container">
          <h2 class="section-title">今日黄历</h2>
          <a-card>
            <a-row :gutter="24">
              <a-col :span="12">
                <h4>2026年3月22日 星期日 白羊座</h4>
                <p>农历二月初四 丙午年【马】辛卯月·乙未日</p>
              </a-col>
              <a-col :span="6">
                <h4>宜</h4>
                <p>嫁娶 祭祀 开光 祈福 求嗣 出行</p>
              </a-col>
              <a-col :span="6">
                <h4>忌</h4>
                <p>动土 伐木 安葬 行丧</p>
              </a-col>
            </a-row>
          </a-card>
        </div>
      </section>

      <section class="rankings">
        <div class="container">
          <a-row :gutter="[24, 24]">
            <a-col :xs="24" :md="12">
              <a-card title="男孩热门名字排行">
                <a-list :data-source="boyNames" :loading="loading">
                  <template #renderItem="{ item }">
                    <a-list-item>
                      <a-list-item-meta :title="item.name" :description="`评分: ${item.score}分`" />
                    </a-list-item>
                  </template>
                </a-list>
              </a-card>
            </a-col>
            <a-col :xs="24" :md="12">
              <a-card title="女孩热门名字排行">
                <a-list :data-source="girlNames" :loading="loading">
                  <template #renderItem="{ item }">
                    <a-list-item>
                      <a-list-item-meta :title="item.name" :description="`评分: ${item.score}分`" />
                    </a-list-item>
                  </template>
                </a-list>
              </a-card>
            </a-col>
          </a-row>
        </div>
      </section>
    </main>

    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-links">
            <h4>快捷导航</h4>
            <router-link to="/baobao">宝宝起名</router-link>
            <router-link to="/bazi">八字起名</router-link>
            <router-link to="/zhouyi">周易起名</router-link>
            <router-link to="/kxzd">康熙字典</router-link>
          </div>
          <div class="footer-links">
            <h4>关于我们</h4>
            <router-link to="/about">关于我们</router-link>
            <router-link to="/service">服务条款</router-link>
          </div>
        </div>
        <div class="footer-copyright">
          © 2005-2025 起名网 www.qiming.cn All rights reserved
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { SearchOutlined } from '@ant-design/icons-vue'
import { message } from 'ant-design-vue'
import axios from '@/utils/request'

const router = useRouter()
const searchKeyword = ref('')
const loading = ref(false)

const services = [
  { path: '/baobao', title: '宝宝起名', image: '/images/bbqm.jpg' },
  { path: '/bazi', title: '八字起名', image: '/images/bzqm.jpg' },
  { path: '/shici', title: '诗词起名', image: '/images/scqm.jpg' },
  { path: '/gaimingzi', title: '成人改名', image: '/images/crgm.jpg' },
  { path: '/xingmingceshi', title: '姓名测试', image: '/images/xmcs.jpg' },
  { path: '/gongsiqiming', title: '公司起名', image: '/images/gsqm.jpg' }
]

const babyForm = reactive({
  surname: '',
  gender: 1
})

const testForm = reactive({
  name: '',
  gender: 1
})

const boyNames = ref([
  { name: '李俊豪', score: 98 },
  { name: '李煜晨', score: 96 },
  { name: '李铭轩', score: 95 },
  { name: '李梓翔', score: 94 },
  { name: '李昊然', score: 93 }
])

const girlNames = ref([
  { name: '李欣怡', score: 98 },
  { name: '李梓涵', score: 96 },
  { name: '李雨桐', score: 95 },
  { name: '李诗涵', score: 94 },
  { name: '李思琪', score: 93 }
])

const onSearch = () => {
  if (searchKeyword.value) {
    router.push({ path: '/search', query: { q: searchKeyword.value } })
  }
}

const onBabyName = () => {
  if (!babyForm.surname) {
    message.warning('请输入姓氏')
    return
  }
  router.push({ path: '/baobao', query: { surname: babyForm.surname, gender: babyForm.gender } })
}

const onNameTest = () => {
  if (!testForm.name) {
    message.warning('请输入姓名')
    return
  }
  router.push({ path: '/xingmingceshi', query: { name: testForm.name, gender: testForm.gender } })
}
</script>

<style lang="scss" scoped>
.home-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

.header {
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  position: sticky;
  top: 0;
  z-index: 100;

  .container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
  }

  .logo {
    display: flex;
    align-items: center;
    font-size: 20px;
    font-weight: bold;
    color: #d4380d;

    img {
      height: 40px;
      margin-right: 8px;
    }
  }

  .nav {
    display: flex;
    gap: 24px;

    a {
      color: #333;
      text-decoration: none;

      &:hover {
        color: #d4380d;
      }

      &.router-link-active {
        color: #d4380d;
      }
    }
  }

  .user-actions {
    display: flex;
    gap: 8px;
  }
}

.main {
  flex: 1;
}

.hero {
  background: linear-gradient(135deg, #d4380d 0%, #ff4d4f 100%);
  padding: 60px 0;
  color: #fff;
  text-align: center;

  h1 {
    font-size: 36px;
    margin-bottom: 16px;
  }

  p {
    font-size: 18px;
    margin-bottom: 32px;
  }

  .search-box {
    max-width: 500px;
    margin: 0 auto;
  }
}

.services {
  padding: 48px 0;

  .section-title {
    text-align: center;
    font-size: 24px;
    margin-bottom: 32px;
  }

  .service-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 24px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s;

    &:hover {
      transform: translateY(-4px);
    }

    img {
      width: 80px;
      height: 80px;
      margin-bottom: 12px;
    }

    span {
      font-size: 14px;
      color: #333;
    }
  }
}

.tools {
  padding: 48px 0;
  background: #fafafa;

  .tool-card {
    background: #fff;
    padding: 32px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);

    h3 {
      font-size: 20px;
      margin-bottom: 8px;
    }

    p {
      color: #666;
      margin-bottom: 24px;
    }

    .tool-form {
      display: flex;
      gap: 12px;
      align-items: center;

      .ant-input {
        width: 200px;
      }
    }
  }
}

.almanac {
  padding: 48px 0;

  .section-title {
    text-align: center;
    font-size: 24px;
    margin-bottom: 32px;
  }

  h4 {
    margin-bottom: 8px;
  }
}

.rankings {
  padding: 48px 0;
  background: #fafafa;

  .ant-card {
    height: 100%;
  }
}

.footer {
  background: #333;
  color: #fff;
  padding: 48px 0 24px;

  .footer-content {
    display: flex;
    justify-content: space-around;
    margin-bottom: 32px;
  }

  .footer-links {
    h4 {
      margin-bottom: 16px;
    }

    a {
      display: block;
      color: #ccc;
      text-decoration: none;
      margin-bottom: 8px;

      &:hover {
        color: #fff;
      }
    }
  }

  .footer-copyright {
    text-align: center;
    color: #999;
    padding-top: 24px;
    border-top: 1px solid #444;
  }
}
</style>

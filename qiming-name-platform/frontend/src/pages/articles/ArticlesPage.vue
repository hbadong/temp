<template>
  <div class="articles-page">
    <header class="header">
      <div class="container">
        <router-link to="/" class="logo">起名网</router-link>
        <nav class="nav">
          <router-link to="/baobao">宝宝起名</router-link>
          <router-link to="/bazi">八字起名</router-link>
          <router-link to="/zhishi" class="active">起名知识</router-link>
        </nav>
      </div>
    </header>

    <main class="main">
      <div class="container">
        <a-row :gutter="[24, 24]">
          <a-col :span="6">
            <a-card title="文章分类">
              <a-menu v-model:selectedKeys="selectedKeys" mode="vertical">
                <a-menu-item key="all">全部文章</a-menu-item>
                <a-menu-item key="1">起名常识</a-menu-item>
                <a-menu-item key="2">八字知识</a-menu-item>
                <a-menu-item key="3">诗词起名</a-menu-item>
                <a-menu-item key="4">周易起名</a-menu-item>
                <a-menu-item key="5">姓名测试</a-menu-item>
              </a-menu>
            </a-card>
          </a-col>
          
          <a-col :span="18">
            <div class="article-list">
              <h2>起名知识</h2>
              
              <a-list :data-source="articles" :loading="loading" :pagination="pagination">
                <template #renderItem="{ item }">
                  <a-list-item>
                    <a-list-item-meta
                      :title="item.title"
                      :description="item.summary"
                      @click="viewArticle(item)"
                    >
                      <template #avatar>
                        <a-avatar :src="item.cover" shape="square" />
                      </template>
                    </a-list-item-meta>
                    <template #actions>
                      <span><eye-outlined /> {{ item.viewCount }}</span>
                      <span><calendar-outlined /> {{ item.date }}</span>
                    </template>
                  </a-list-item>
                </template>
              </a-list>
            </div>
          </a-col>
        </a-row>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { EyeOutlined, CalendarOutlined } from '@ant-design/icons-vue'

const loading = ref(false)
const selectedKeys = ref(['all'])

const articles = ref([
  {
    id: 1,
    title: '宝宝起名别跟风！8个独特技巧',
    summary: '大家有没有发现，每次去医院或者学校，总能碰到几个孩子叫子涵、梓涵、紫轩、宇轩这样的名字？',
    cover: 'https://static.qiming.cn/style/qiming/images/xbbqm.jpg',
    viewCount: 243,
    date: '2026-03-21'
  },
  {
    id: 2,
    title: '八字五行缺火的人应该怎么起名',
    summary: '理论上八字缺啥五行就补什么五行是错误的，但八字缺少某个五行，相对来说这个五行在整个命盘上面的力量会非常的弱',
    cover: 'https://static.qiming.cn/style/qiming/images/xbzqm.jpg',
    viewCount: 309,
    date: '2026-03-20'
  },
  {
    id: 3,
    title: '如何起一个富含诗意的好名字',
    summary: '名字是父母送给孩子的第一份礼物，一个有诗意的名字能让孩子在人群中脱颖而出',
    cover: 'https://static.qiming.cn/style/qiming/images/xscqm.jpg',
    viewCount: 156,
    date: '2026-03-19'
  }
])

const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 3
})

const viewArticle = (item) => {
  console.log('查看文章:', item)
}
</script>

<style lang="scss" scoped>
.articles-page {
  min-height: 100vh;
  background: #f5f5f5;
}

.header {
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);

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
  padding: 32px 0;
}

.article-list {
  background: #fff;
  padding: 24px;
  border-radius: 8px;

  h2 {
    margin-bottom: 24px;
    font-size: 20px;
  }
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}
</style>

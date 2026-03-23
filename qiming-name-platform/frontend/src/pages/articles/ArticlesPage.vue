<template>
  <div class="articles-page">
    <header class="header qiming-background-default">
      <div class="navbar">
        <router-link
          to="/"
          class="logo"
        >
          <div class="logobg" />
        </router-link>
        <nav class="nav">
          <ul>
            <li>
              <router-link to="/">
                首页
              </router-link>
            </li>
            <li>
              <router-link to="/baobao">
                宝宝起名
              </router-link>
            </li>
            <li>
              <router-link to="/bazi">
                八字起名
              </router-link>
            </li>
            <li>
              <router-link to="/shici">
                诗词起名
              </router-link>
            </li>
            <li>
              <router-link to="/gaimingzi">
                成人改名
              </router-link>
            </li>
            <li>
              <router-link to="/xingmingceshi">
                姓名测试
              </router-link>
            </li>
            <li>
              <router-link to="/gongsiqiming">
                公司起名
              </router-link>
            </li>
            <li>
              <router-link to="/zhouyi">
                周易起名
              </router-link>
            </li>
            <li class="current-menu-item">
              <router-link to="/zhishi">
                起名知识
              </router-link>
            </li>
            <li>
              <router-link to="/kxzd">
                康熙字典
              </router-link>
            </li>
            <li>
              <router-link to="/baijiaxing">
                百家姓
              </router-link>
            </li>
          </ul>
        </nav>
        <div class="header-info">
          <a
            href="javascript:;"
            @click="showSearch = true"
          >
            <i class="iconfont icon-search" />
          </a>
          <a
            href="javascript:;"
            class="mobile-menu-btn"
            @click="showMobileMenu = true"
          >
            <i class="iconfont icon-menu" />
          </a>
        </div>
      </div>
    </header>

    <div
      class="mobile-menu-overlay"
      :class="{ active: showMobileMenu }"
      @click="showMobileMenu = false"
    />
    <div
      class="mobile-menu"
      :class="{ active: showMobileMenu }"
    >
      <div class="mobile-menu-header">
        <img
          src="/images/logo.png"
          alt="起名网"
        >
        <a
          href="javascript:;"
          @click="showMobileMenu = false"
        >
          <i class="iconfont icon-close" />
        </a>
      </div>
      <ul class="mobile-menu-list">
        <li><router-link to="/">首页</router-link></li>
        <li><router-link to="/baobao">宝宝起名</router-link></li>
        <li><router-link to="/bazi">八字起名</router-link></li>
        <li><router-link to="/shici">诗词起名</router-link></li>
        <li><router-link to="/gaimingzi">成人改名</router-link></li>
        <li><router-link to="/xingmingceshi">姓名测试</router-link></li>
        <li><router-link to="/gongsiqiming">公司起名</router-link></li>
        <li><router-link to="/zhouyi">周易起名</router-link></li>
        <li><router-link to="/zhishi">起名知识</router-link></li>
        <li><router-link to="/kxzd">康熙字典</router-link></li>
        <li><router-link to="/baijiaxing">百家姓</router-link></li>
      </ul>
      <div class="mobile-menu-footer">
        <router-link to="/login">登录</router-link>
        <router-link to="/register">注册</router-link>
      </div>
    </div>

    <main class="main">
      <div class="container">
        <a-row :gutter="[24, 24]">
          <a-col
            :xs="24"
            :sm="24"
            :md="6"
          >
            <a-card title="文章分类">
              <a-menu
                v-model:selected-keys="selectedKeys"
                mode="vertical"
              >
                <a-menu-item key="all">
                  全部文章
                </a-menu-item>
                <a-menu-item key="1">
                  起名常识
                </a-menu-item>
                <a-menu-item key="2">
                  八字知识
                </a-menu-item>
                <a-menu-item key="3">
                  诗词起名
                </a-menu-item>
                <a-menu-item key="4">
                  周易起名
                </a-menu-item>
                <a-menu-item key="5">
                  姓名测试
                </a-menu-item>
              </a-menu>
            </a-card>
          </a-col>
          
          <a-col
            :xs="24"
            :sm="24"
            :md="18"
          >
            <div class="article-list">
              <h2>起名知识</h2>
              
              <a-list
                :data-source="articles"
                :loading="loading"
                :pagination="pagination"
              >
                <template #renderItem="{ item }">
                  <a-list-item>
                    <a-list-item-meta
                      :title="item.title"
                      :description="item.summary"
                      @click="viewArticle(item)"
                    >
                      <template #avatar>
                        <a-avatar
                          :src="item.cover"
                          shape="square"
                        />
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

    <footer class="footer qiming-background-secondary">
      <div class="foot qiming-container qiming-padding">
        <div
          class="qiming-grid"
          style="grid-template-columns: 1fr 2fr;"
        >
          <div class="foot-item foot-item-first qiming-position-relative qiming-flex">
            <router-link
              to="/"
              class="foot-logo qiming-display-block"
            >
              <img
                src="/images/logo_foot.png"
                alt="起名网"
              >
            </router-link>
            <p class="qiming-text-small">
              起名网专注科学智能宝宝起名，测名字打分平台，结合传统国学文化的智能起名系统研发和起名学术探索交流，以"只为一个好名字"为宗旨，潜心研发，百次升级，千万级大数据分析，助您轻松起好名。
            </p>
          </div>
          <div class="qiming-visible@s">
            <div
              class="qiming-grid"
              style="grid-template-columns: repeat(3, 1fr);"
            >
              <div class="foot-item">
                <div class="foot-item-title">
                  <i class="iconfont icon-security" />快捷导航
                </div>
                <ul class="qiming-padding-remove">
                  <li><router-link to="/baobao">宝宝起名</router-link></li>
                  <li><router-link to="/bazi">八字起名</router-link></li>
                  <li><router-link to="/zhouyi">周易起名</router-link></li>
                  <li><router-link to="/kxzd">康熙字典</router-link></li>
                  <li><router-link to="/gongsiqiming">公司起名</router-link></li>
                  <li><router-link to="/xingmingceshi">姓名测试</router-link></li>
                </ul>
              </div>
              <div class="foot-item">
                <div class="foot-item-title">
                  <i class="iconfont icon-security" />关于我们
                </div>
                <ul class="qiming-padding-remove">
                  <li><router-link to="/about">关于我们</router-link></li>
                  <li><router-link to="/service">服务条款</router-link></li>
                  <li><router-link to="/copyright">版权声明</router-link></li>
                  <li><router-link to="/busine">投诉建议</router-link></li>
                </ul>
              </div>
              <div class="foot-item">
                <div class="foot-item-title">
                  <i class="iconfont icon-security" />友情链接
                </div>
                <ul class="qiming-padding-remove">
                  <li><a
                    href="https://zidian.qiming.cn/"
                    target="_blank"
                  >汉语字典</a></li>
                  <li><a
                    href="https://cidian.qiming.cn/"
                    target="_blank"
                  >汉语词典</a></li>
                  <li><a
                    href="https://chengyu.qiming.cn/"
                    target="_blank"
                  >成语大全</a></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <div class="foot-cop">
          <div class="qiming-container qiming-padding-small qiming-clearfix">
            <div class="qiming-float-left">
              <span>© 2005-2025 <a
                href="https://www.qiming.cn/"
                target="_blank"
              >www.qiming.cn</a> &amp; All rights reserved</span>
              <span class="qiming-margin-small-right"><a
                href="https://beian.miit.gov.cn/"
                target="_blank"
                rel="noreferrer nofollow"
              >皖ICP备2024064902号</a></span>
            </div>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { EyeOutlined, CalendarOutlined } from '@ant-design/icons-vue';

const showSearch = ref(false);
const showMobileMenu = ref(false);
const loading = ref(false);
const selectedKeys = ref(['all']);

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
]);

const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 3
});

const viewArticle = (item) => {
  console.log('查看文章:', item);
};
</script>

<style lang="scss" scoped>
@import '@/assets/styles/qiming.scss';

.articles-page {
  font-family: 'PingFang SC', 'Microsoft YaHei', sans-serif;
  min-height: 100vh;
  background: #f0f2f5;
}

.logobg {
  width: 160px;
  height: 49px;
  background: url('/images/logo.png') no-repeat;
  background-size: 160px auto;
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

.mobile-menu-btn {
  display: none;
  margin-left: 15px;
  @media (max-width: 767px) {
    display: inline-block;
  }
}

.mobile-menu-overlay {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 998;
  opacity: 0;
  transition: opacity 0.3s;
  &.active {
    display: block;
    opacity: 1;
  }
}

.mobile-menu {
  display: none;
  position: fixed;
  top: 0;
  right: -280px;
  width: 280px;
  height: 100%;
  background: #fff;
  z-index: 999;
  transition: right 0.3s ease;
  overflow-y: auto;
  &.active {
    right: 0;
  }
  @media (max-width: 767px) {
    display: block;
  }
}

.mobile-menu-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px 20px;
  border-bottom: 1px solid #eee;
  img {
    height: 40px;
  }
  a {
    color: #666;
    font-size: 24px;
  }
}

.mobile-menu-list {
  list-style: none;
  padding: 0;
  margin: 0;
  li {
    border-bottom: 1px solid #f5f5f5;
    a {
      display: block;
      padding: 15px 20px;
      color: #333;
      font-size: 15px;
      &:hover {
        color: #a93121;
        background: #fafafa;
      }
    }
  }
}

.mobile-menu-footer {
  display: flex;
  padding: 20px;
  gap: 15px;
  a {
    flex: 1;
    text-align: center;
    padding: 10px;
    border-radius: 4px;
    font-size: 14px;
    &:first-child {
      background: #f0f0f0;
      color: #333;
    }
    &:last-child {
      background: linear-gradient(90deg, #c92009, #e6614f);
      color: #fff;
    }
  }
}
</style>

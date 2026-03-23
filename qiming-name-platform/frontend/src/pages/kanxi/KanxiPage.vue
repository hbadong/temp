<template>
  <div class="kanxi-page">
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
            <li>
              <router-link to="/zhishi">
                起名知识
              </router-link>
            </li>
            <li class="current-menu-item">
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
      <section class="search-section">
        <div class="container">
          <h1>康熙字典</h1>
          <p class="subtitle">
            查询汉字的拼音、五行、笔画、康熙字典解释
          </p>
          
          <a-form
            layout="inline"
            class="search-form"
          >
            <a-form-item>
              <a-input-search
                v-model:value="searchChar"
                placeholder="输入汉字查询"
                size="large"
                style="width: 300px"
                allow-clear
                @search="onSearch"
              >
                <template #prefix>
                  <SearchOutlined />
                </template>
              </a-input-search>
            </a-form-item>
            
            <a-form-item label="五行">
              <a-select
                v-model:value="filterElement"
                placeholder="选择五行"
                style="width: 120px"
                allow-clear
                @change="onFilterChange"
              >
                <a-select-option value="">
                  全部
                </a-select-option>
                <a-select-option value="金">
                  金
                </a-select-option>
                <a-select-option value="木">
                  木
                </a-select-option>
                <a-select-option value="水">
                  水
                </a-select-option>
                <a-select-option value="火">
                  火
                </a-select-option>
                <a-select-option value="土">
                  土
                </a-select-option>
              </a-select>
            </a-form-item>
            
            <a-form-item label="笔画">
              <a-input-number
                v-model:value="filterStroke"
                placeholder="笔画数"
                style="width: 100px"
                :min="1"
                :max="64"
                allow-clear
                @change="onFilterChange"
              />
            </a-form-item>
          </a-form>
        </div>
      </section>

      <section
        v-if="result"
        class="result-section"
      >
        <div class="container">
          <a-row :gutter="24">
            <a-col :span="8">
              <a-card class="char-card">
                <div class="big-char">
                  {{ result.character }}
                </div>
                <a-divider />
                <a-descriptions
                  :column="1"
                  size="small"
                >
                  <a-descriptions-item label="拼音">
                    {{ result.pinyin }}
                  </a-descriptions-item>
                  <a-descriptions-item label="声调">
                    {{ result.tone }}
                  </a-descriptions-item>
                  <a-descriptions-item label="部首">
                    {{ result.radical }}
                  </a-descriptions-item>
                  <a-descriptions-item label="笔画">
                    {{ result.total_stroke }}画
                  </a-descriptions-item>
                  <a-descriptions-item label="五行">
                    <a-tag :color="getElementColor(result.five_element)">
                      {{ result.five_element }}
                    </a-tag>
                  </a-descriptions-item>
                </a-descriptions>
              </a-card>
            </a-col>
            
            <a-col :span="16">
              <a-card title="基本解释">
                <p>{{ result.basic_meaning }}</p>
              </a-card>
              
              <a-card
                v-if="result.detail_meaning"
                title="详细解释"
                style="margin-top: 16px"
              >
                <p>{{ result.detail_meaning }}</p>
              </a-card>
            </a-col>
          </a-row>
        </div>
      </section>

      <section
        v-if="!searchChar"
        class="browse-section"
      >
        <div class="container">
          <h2>按五行浏览</h2>
          <a-row :gutter="[16, 16]">
            <a-col
              v-for="element in elements"
              :key="element.name"
              :span="4"
            >
              <router-link
                :to="`/kxzd/element/${element.name}`"
                class="element-card"
                :style="{ background: element.color }"
              >
                <span class="element-name">{{ element.name }}</span>
                <span class="element-label">{{ element.label }}</span>
              </router-link>
            </a-col>
          </a-row>
        </div>
      </section>
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
import { message } from 'ant-design-vue';
import { SearchOutlined } from '@ant-design/icons-vue';
import request from '@/utils/request';

const showSearch = ref(false);
const showMobileMenu = ref(false);
const loading = ref(false);
const searchChar = ref('');
const filterElement = ref('');
const filterStroke = ref(null);
const result = ref(null);

const elements = [
  { name: '金', label: '金属性', color: '#ffd700' },
  { name: '木', label: '木属性', color: '#228b22' },
  { name: '水', label: '水属性', color: '#4169e1' },
  { name: '火', label: '火属性', color: '#ff4500' },
  { name: '土', label: '土属性', color: '#8b4513' }
];

const getElementColor = (element) => {
  const colors = { '金': '#ffd700', '木': '#228b22', '水': '#4169e1', '火': '#ff4500', '土': '#8b4513' };
  return colors[element] || 'default';
};

const onSearch = async () => {
  if (!searchChar.value) {
    result.value = null;
    return;
  }

  if (searchChar.value.length === 1) {
    loading.value = true;
    try {
      const res = await request.get(`/v1/kanxi/detail/${searchChar.value}`);
      result.value = res.data;
    } catch (error) {
      result.value = null;
      message.error('未找到该汉字');
    } finally {
      loading.value = false;
    }
  }
};

const onFilterChange = async () => {
  result.value = null;
};
</script>

<style lang="scss" scoped>
@import '@/assets/styles/qiming.scss';

.kanxi-page {
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
  padding-bottom: 48px;
}

.search-section {
  background: linear-gradient(135deg, #a93121 0%, #c92009 100%);
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
}

.search-form {
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  padding: 24px;
  border-radius: 8px;
}

.result-section {
  padding: 32px 0;

  .big-char {
    font-size: 120px;
    text-align: center;
    font-family: 'KaiTi', serif;
    color: #333;
  }
}

.browse-section {
  padding: 32px 0;

  h2 {
    margin-bottom: 24px;
  }
}

.element-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100px;
  border-radius: 8px;
  color: #fff;
  text-decoration: none;
  transition: transform 0.2s;

  &:hover {
    transform: scale(1.05);
  }

  .element-name {
    font-size: 32px;
    font-weight: bold;
  }

  .element-label {
    font-size: 14px;
    margin-top: 8px;
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

<template>
  <div class="surname-page">
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
            <li>
              <router-link to="/kxzd">
                康熙字典
              </router-link>
            </li>
            <li class="current-menu-item">
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
          <h1>百家姓</h1>
          <p class="subtitle">
            查询姓氏来源、名字大全
          </p>
          
          <a-form
            layout="inline"
            class="search-form"
          >
            <a-form-item>
              <a-auto-complete
                v-model:value="searchSurname"
                placeholder="输入姓氏查询"
                :options="surnameOptions"
                size="large"
                style="width: 300px"
                allow-clear
                @select="onSearch"
              >
                <template #option="{ value }">
                  {{ value }}
                </template>
              </a-auto-complete>
            </a-form-item>
            
            <a-form-item label="性别">
              <a-radio-group
                v-model:value="filterGender"
                size="large"
                @change="onFilterChange"
              >
                <a-radio-button :value="1">
                  男孩
                </a-radio-button>
                <a-radio-button :value="2">
                  女孩
                </a-radio-button>
              </a-radio-group>
            </a-form-item>
          </a-form>
        </div>
      </section>

      <section
        v-if="surnameDetail"
        class="result-section"
      >
        <div class="container">
          <a-row :gutter="24">
            <a-col :span="12">
              <a-card :title="surnameDetail.surname + '姓'">
                <a-descriptions :column="2">
                  <a-descriptions-item label="拼音">
                    {{ surnameDetail.pinyin }}
                  </a-descriptions-item>
                  <a-descriptions-item label="人口排名">
                    第{{ surnameDetail.population_rank }}名
                  </a-descriptions-item>
                  <a-descriptions-item label="人口数量">
                    {{ surnameDetail.population_count }}
                  </a-descriptions-item>
                  <a-descriptions-item label="姓氏来源">
                    {{ surnameDetail.origin }}
                  </a-descriptions-item>
                </a-descriptions>
                <a-divider>姓氏含义</a-divider>
                <p>{{ surnameDetail.meaning }}</p>
              </a-card>
            </a-col>
            
            <a-col :span="12">
              <a-card title="历史名人">
                <p>{{ surnameDetail.historical_figures || '暂无数据' }}</p>
              </a-card>
            </a-col>
          </a-row>
          
          <a-row
            :gutter="24"
            style="margin-top: 24px"
          >
            <a-col :span="12">
              <a-card title="男孩名字大全">
                <a-list
                  :data-source="boyNames"
                  size="small"
                  :loading="loading"
                >
                  <template #renderItem="{ item }">
                    <a-list-item>
                      <router-link
                        :to="`/baobao?surname=${surname}&gender=1`"
                        class="name-link"
                      >
                        {{ item.full_name }}
                      </router-link>
                    </a-list-item>
                  </template>
                </a-list>
              </a-card>
            </a-col>
            
            <a-col :span="12">
              <a-card title="女孩名字大全">
                <a-list
                  :data-source="girlNames"
                  size="small"
                  :loading="loading"
                >
                  <template #renderItem="{ item }">
                    <a-list-item>
                      <router-link
                        :to="`/baobao?surname=${surname}&gender=2`"
                        class="name-link"
                      >
                        {{ item.full_name }}
                      </router-link>
                    </a-list-item>
                  </template>
                </a-list>
              </a-card>
            </a-col>
          </a-row>
        </div>
      </section>

      <section
        v-if="!surnameDetail"
        class="list-section"
      >
        <div class="container">
          <h2>姓氏排行榜</h2>
          <a-row :gutter="[16, 16]">
            <a-col
              v-for="surname in topSurnames"
              :key="surname"
              :span="4"
            >
              <a-card
                hoverable
                class="surname-card"
                @click="onSearch(surname)"
              >
                <div class="surname-char">
                  {{ surname }}
                </div>
                <div class="surname-name">
                  姓
                </div>
              </a-card>
            </a-col>
          </a-row>
          
          <h2 style="margin-top: 48px">
            全部姓氏
          </h2>
          <div class="surname-grid">
            <span
              v-for="surname in allSurnames"
              :key="surname"
              class="surname-item"
              @click="onSearch(surname)"
            >
              {{ surname }}
            </span>
          </div>
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
import request from '@/utils/request';

const showSearch = ref(false);
const showMobileMenu = ref(false);
const loading = ref(false);
const searchSurname = ref('');
const filterGender = ref(1);
const surnameDetail = ref(null);
const boyNames = ref([]);
const girlNames = ref([]);

const topSurnames = ['王', '李', '张', '刘', '陈', '杨', '黄', '赵', '吴', '周', '徐', '孙', '马', '朱', '胡', '郭', '何', '高', '林', '罗', '郑', '梁', '谢', '宋', '唐', '许', '韩', '冯', '邓', '曹', '彭', '曾', '萧', '蔡', '潘', '田', '董', '袁', '于', '余', '叶', '蒋', '杜', '苏', '魏', '程', '吕', '丁', '沈', '任', '姚', '卢', '传', '傅', '钟', '莹', '韦', '嘉'];

const allSurnames = ['王', '李', '张', '刘', '陈', '杨', '黄', '赵', '吴', '周', '徐', '孙', '马', '朱', '胡', '郭', '何', '高', '林', '罗', '郑', '梁', '谢', '宋', '唐', '许', '韩', '冯', '邓', '曹', '彭', '曾', '萧', '蔡', '潘', '田', '董', '袁', '于', '余', '叶', '蒋', '杜', '苏', '魏', '程', '吕', '丁', '沈', '任', '姚', '卢', '傅', '钟', '韦', '嘉'];

const surnameOptions = allSurnames.map(s => ({ value: s }));

const onSearch = async (value) => {
  const surname = value || searchSurname.value;
  if (!surname) return;

  loading.value = true;
  searchSurname.value = surname;

  try {
    const res = await request.get(`/v1/surnames/${surname}`);
    surnameDetail.value = res.data;
    
    const namesRes = await request.get(`/v1/surnames/${surname}/names`, {
      params: { gender: filterGender.value, pageSize: 20 }
    });
    
    boyNames.value = namesRes.data?.filter(n => n.gender === 1) || [];
    girlNames.value = namesRes.data?.filter(n => n.gender === 2) || [];
  } catch (error) {
    surnameDetail.value = null;
    message.error('未找到该姓氏');
  } finally {
    loading.value = false;
  }
};

const onFilterChange = async () => {
  if (surnameDetail.value) {
    await onSearch(surnameDetail.value.surname);
  }
};
</script>

<style lang="scss" scoped>
@import '@/assets/styles/qiming.scss';

.surname-page {
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
}

.list-section {
  padding: 32px 0;

  h2 {
    margin-bottom: 24px;
  }
}

.surname-card {
  text-align: center;
  cursor: pointer;

  .surname-char {
    font-size: 36px;
    font-weight: bold;
    color: #a93121;
  }

  .surname-name {
    font-size: 14px;
    color: #666;
  }
}

.surname-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;

  .surname-item {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: #fff;
    border: 1px solid #d9d9d9;
    border-radius: 4px;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.2s;

    &:hover {
      border-color: #a93121;
      color: #a93121;
      transform: scale(1.1);
    }
  }
}

.name-link {
  color: #333;
  text-decoration: none;

  &:hover {
    color: #a93121;
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

<template>
  <div class="adult-name-page">
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
            <li class="current-menu-item">
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
          </ul>
        </nav>
        <div class="header-info">
          <a
            href="#header-search"
            @click.prevent="showSearch = true"
          >
            <i class="iconfont icon-search" />
          </a>
        </div>
      </div>
    </header>

    <div id="page-content">
      <div class="qiming-container">
        <div class="page-header">
          <h1>成人改名</h1>
          <p>为您提供专业的成人改名咨询服务</p>
        </div>
        <div class="page-content-placeholder">
          <div class="placeholder-text">
            成人改名页面 - 正在开发中
          </div>
        </div>
      </div>
    </div>

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
                    <router-link to="/zhouyi">
                      周易起名
                    </router-link>
                  </li>
                  <li>
                    <router-link to="/kxzd">
                      康熙字典
                    </router-link>
                  </li>
                  <li>
                    <router-link to="/gongsiqiming">
                      公司起名
                    </router-link>
                  </li>
                  <li>
                    <router-link to="/xingmingceshi">
                      姓名测试
                    </router-link>
                  </li>
                </ul>
              </div>
              <div class="foot-item">
                <div class="foot-item-title">
                  <i class="iconfont icon-security" />关于我们
                </div>
                <ul class="qiming-padding-remove">
                  <li>
                    <router-link to="/about">
                      关于我们
                    </router-link>
                  </li>
                  <li>
                    <router-link to="/service">
                      服务条款
                    </router-link>
                  </li>
                  <li>
                    <router-link to="/copyright">
                      版权声明
                    </router-link>
                  </li>
                  <li>
                    <router-link to="/busine">
                      投诉建议
                    </router-link>
                  </li>
                </ul>
              </div>
              <div class="foot-item">
                <div class="foot-item-title">
                  <i class="iconfont icon-security" />友情链接
                </div>
                <ul class="qiming-padding-remove">
                  <li>
                    <a
                      href="https://zidian.qiming.cn/"
                      target="_blank"
                    >汉语字典</a>
                  </li>
                  <li>
                    <a
                      href="https://cidian.qiming.cn/"
                      target="_blank"
                    >汉语词典</a>
                  </li>
                  <li>
                    <a
                      href="https://chengyu.qiming.cn/"
                      target="_blank"
                    >成语大全</a>
                  </li>
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
                title="起名网"
                target="_blank"
              >www.qiming.cn</a> &amp; All rights reserved</span>
              <span class="qiming-margin-small-right qiming-gongan">
                <img
                  src="/images/qiming-110.png"
                  height="6"
                >皖公网安备34010402704531号
              </span>
              <span class="qiming-margin-small-right">
                <a
                  href="https://beian.miit.gov.cn/"
                  target="_blank"
                  rel="noreferrer nofollow"
                >皖ICP备2024064902号</a>
              </span>
            </div>
          </div>
        </div>
      </div>
    </footer>

    <div
      v-if="showSearch"
      id="header-search"
      class="search-modal"
      @click.self="showSearch = false"
    >
      <div class="qiming-tan">
        <h3>搜索</h3>
        <div class="search-navbar">
          <input
            v-model="searchKeyword"
            type="text"
            placeholder="立即搜索"
            @keyup.enter="handleSearch"
          >
          <i class="iconfont icon-search" />
        </div>
        <div class="header-btn-search">
          <a
            href="javascript:;"
            class="btn btn-search-all"
            @click="handleSearch"
          >搜索</a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();
const showSearch = ref(false);
const searchKeyword = ref('');

const handleSearch = () => {
  if (!searchKeyword.value.trim()) {
    return;
  }
  showSearch.value = false;
  router.push({ path: '/search', query: { keyword: searchKeyword.value } });
};
</script>

<style lang="scss">
@import '@/assets/styles/qiming.scss';

.adult-name-page {
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

.page-header {
  text-align: center;
  padding: 40px 0;
  h1 {
    font-size: 32px;
    color: #333;
    margin-bottom: 10px;
  }
  p {
    font-size: 16px;
    color: #666;
  }
}

.page-content-placeholder {
  background: #fff;
  border-radius: 8px;
  padding: 60px;
  text-align: center;
  margin-bottom: 40px;
}

.placeholder-text {
  font-size: 18px;
  color: #999;
}

.iconfont {
  font-family: "iconfont" !important;
  font-size: 16px;
  font-style: normal;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}
</style>

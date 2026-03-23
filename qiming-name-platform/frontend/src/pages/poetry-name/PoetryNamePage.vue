<template>
  <div class="poetry-name-page">
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
            <li class="current-menu-item">
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

    <div class="qiming_vipbgs">
      <img
        src="/images/scqmBanner.jpg"
        alt=""
      >
    </div>

    <div id="page-content">
      <div class="qiming-pages-vip">
        <div class="qiming_portal_block_summary">
          <div class="formGsQm">
            <h1>诗词起名</h1>
            <p>结合孩子出生信息和父母期盼，从二十多万诗词古文中取字，确保每个名字意蕴优美、诗情画意。</p>
            <form method="post">
              <div class="two_bd">
                <div class="list_bd">
                  <input
                    v-model="form.surname"
                    type="text"
                    placeholder="请输入您的姓氏"
                  >
                  <div class="sexSelect">
                    <span
                      :class="{ active: form.gender === 1 }"
                      @click="form.gender = 1"
                    >男</span>
                    <span
                      :class="{ active: form.gender === 2 }"
                      @click="form.gender = 2"
                    >女</span>
                    <span class="wcs">未知</span>
                  </div>
                </div>
                <div class="list_bd">
                  <input
                    v-model="form.birthday"
                    type="text"
                    class="J_datepicker"
                    placeholder="请选择出生日期"
                    readonly
                  >
                  <img
                    src="/images/xl.png"
                    alt=""
                  >
                </div>
              </div>
              <div
                class="list_bd"
                style="width:100%;margin-right:0;margin-bottom:20px;"
              >
                <input
                  v-model="form.birthAddress"
                  type="text"
                  placeholder="请选择出生地"
                  readonly
                >
                <img
                  src="/images/xl.png"
                  alt=""
                >
              </div>
              <a
                class="qmbtn"
                href="javascript:;"
                @click="handleSubmit"
              >立即诗词起名</a>
            </form>
          </div>
        </div>

        <section class="qiming-container qiming-margin-medium-bottom qiming-margin-top-20">
          <div class="qiming-background-default qiming-padding-app qiming-margin-medium-bottom qiming-margin-top-20">
            <h2 class="apply">
              诗词起名：从经典诗词中撷取雅致好名，赋予名字文化底蕴
            </h2>
            <blockquote>
              <p>诗词起名以中华经典诗词为灵感源泉，从《诗经》的温润、唐诗的豪迈、宋词的婉约中提炼适配起名的优美字词，为宝宝或个人打造兼具诗意与内涵的名字。</p>
              <p>起名时既注重字词的音律美感，确保名字读来朗朗上口，又深挖词句背后的文化寓意，如"清晏"取自"海宇清晏"，寄寓平安顺遂；"疏影"源自"疏影横斜水清浅"，彰显清雅气质。</p>
              <p>无论是追求古朴韵味，还是偏爱灵动意境，诗词起名都能满足不同需求，让名字不仅是标识，更成为承载文化传承与美好期许的载体，助力起出独特且富有底蕴的好名。</p>
            </blockquote>
          </div>
        </section>
      </div>

      <div class="yuvip_serve_father">
        <h3 class="yuvip_title">
          诗词起名综合六大维度
        </h3>
        <div class="yuvip_serve">
          <ul>
            <li>
              <div>
                <img
                  src="/images/wd1.jpg"
                  alt="国学起名"
                ><p>国学起名</p><em>从国学经典中取材，将汉语文化精华与当代文化融合，取个影响一生的好名字。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd2.jpg"
                  alt="音形义起名"
                ><p>音形义起名</p><em>着重考虑音顺，形美、义内涵的字。做到真正的音美、字美、意境美的好名字。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd3.jpg"
                  alt="期望起名"
                ><p>期望起名</p><em>根据父母期望，来结合用字含义，使名字更加有寓意，凸显期望特性的好名字</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd4.jpg"
                  alt="大数据起名"
                ><p>大数据起名</p><em>基于每年百万宝宝起名数据分析，通过智能起名分析系统，分析出好的方案。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd5.jpg"
                  alt="诗词起名"
                ><p>诗词起名</p><em>根据大量诗词古籍，分析好的名字，组合起更有诗意的名字。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd6.jpg"
                  alt="生肖起名"
                ><p>生肖起名</p><em>根据生肖特性，筛选出更加适合使用者的名字方案。</em>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <div class="qiming-pages-web-qa qiming-background-default qiming-mt-130">
        <div class="container">
          <div class="qiming-pages-web-qatitle">
            <span>起名网诗词起名优势</span>
          </div>
          <div class="qiming-pages-web-qa-box">
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>传统魅力</p>
              </div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con">
                  <li>诗词取名融入千年历史精华，指在诗意与寓意相得益彰，姓与名完美匹配，读之顺口，悦耳动人，亦使人心生美好，一眼难忘。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>文化内涵</p>
              </div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con">
                  <li>诗词取名以经典古诗词和古文为根基，凝聚精华设计为名，不仅具有丰富的文化内涵，更是古人智慧和思想之结晶，让孩子从中领略古人思想和文采，以此提升孩子的文化素养和内涵。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>精准适配</p>
              </div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con">
                  <li>精准臻选一些大气磅礴或抒发高远志向和情怀的名篇，为名主匹配佳名。经诗词文化熏陶，将名字的深刻意义印在心中，形成一种时刻提醒的作用，使其成为拥有这样美好品质的人。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>艺术美感</p>
              </div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con">
                  <li>诗词取名借助诗意的文字和美妙的意象，赋予名字以古色古香、文雅优美的韵味，让人通过名字联想起名主的形象和气质，同时也会潜移默化地促进孩子形成这种优雅美好的气质。</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <section class="blog">
        <div class="container qiming-margin-medium-top qiming-margin-bottom-40">
          <div class="section-title">
            <i class="iconfont icon-file-common" />
            <h3 class="qiming-display-inline-block">
              诗词起名推荐阅读
            </h3>
            <div class="sub-nav qiming-visible@s">
              <span class="all qiming-display-inline-block">
                <a
                  href="/scqm/3.html"
                  target="_blank"
                >查看更多<i class="iconfont icon-arrow-right-bold" /></a>
              </span>
            </div>
          </div>
        </div>
      </section>
    </div>

    <footer class="footer qiming-background-secondary">
      <div class="foot qiming-container qiming-padding">
        <div
          class="qiming-grid"
          style="grid-template-columns: 1fr 2fr;"
        >
          <div class="foot-item foot-item-first qiming-position-relative qiming-flex">
            <a
              href="/"
              class="foot-logo qiming-display-block"
            ><img
              src="/images/logo_foot.png"
              alt="起名网"
            ></a>
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
                  <li><a href="/baobao.html">宝宝起名</a></li>
                  <li><a href="/bazi.html">八字起名</a></li>
                  <li><a href="/zhouyiqiming.html">周易起名</a></li>
                  <li><a href="/kxzd/index.html">康熙字典</a></li>
                  <li><a href="/gongsiqiming.html">公司起名</a></li>
                  <li><a href="/xingmingceshi.html">姓名测试</a></li>
                </ul>
              </div>
              <div class="foot-item">
                <div class="foot-item-title">
                  <i class="iconfont icon-security" />关于我们
                </div>
                <ul class="qiming-padding-remove">
                  <li><a href="/about.html">关于我们</a></li>
                  <li><a href="/service.html">服务条款</a></li>
                  <li><a href="/copyright.html">版权声明</a></li>
                  <li><a href="/busine.html">投诉建议</a></li>
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
import { useRouter } from 'vue-router';
import { message } from 'ant-design-vue';

const router = useRouter();
const showSearch = ref(false);
const showMobileMenu = ref(false);

const form = reactive({
  surname: '',
  gender: 1,
  birthday: '',
  birthAddress: ''
});

const handleSubmit = () => {
  if (!form.surname) {
    message.warning('请输入姓氏');
    return;
  }
  router.push({ path: '/shici', query: { surname: form.surname, gender: form.gender } });
};
</script>

<style lang="scss">
@import '@/assets/styles/qiming.scss';

.poetry-name-page {
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

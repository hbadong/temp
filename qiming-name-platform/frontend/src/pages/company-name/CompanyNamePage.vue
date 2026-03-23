<template>
  <div class="company-name-page">
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
            <li class="current-menu-item">
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
        src="/images/gsqmBanner.jpg"
        alt=""
      >
    </div>

    <div id="page-content">
      <div class="qiming-pages-vip">
        <div class="qiming_portal_block_summary">
          <div class="formGsQm">
            <h1>公司起名</h1>
            <p>资深命名专家与品牌策划大师联手打造，为企业量身定制独特专属好名，助力企业成为行业龙头</p>
            <form method="post">
              <div class="two_bd">
                <div class="list_bd">
                  <input
                    v-model="form.city"
                    type="text"
                    placeholder="请输入公司所在地"
                  >
                </div>
                <div class="list_bd">
                  <input
                    v-model="form.industry"
                    type="text"
                    placeholder="请输入行业 如：服装"
                  >
                </div>
              </div>
              <div
                class="two_bd"
                style="margin-top:20px;"
              >
                <div class="list_bd">
                  <input
                    v-model="form.legalName"
                    type="text"
                    placeholder="请输入法人姓名"
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
              <a
                class="qmbtn"
                href="javascript:;"
                @click="handleSubmit"
              >立即起名</a>
            </form>
          </div>
        </div>

        <section class="qiming-container qiming-margin-medium-bottom qiming-margin-top-20">
          <div class="qiming-background-default qiming-padding-app qiming-margin-medium-bottom qiming-margin-top-20">
            <h2 class="apply">
              公司起名：贴合业务定位，打造有记忆点的品牌标识
            </h2>
            <blockquote>
              <p>公司起名是企业初创或品牌布局的关键环节，需紧密结合业务领域、核心优势与市场定位，从名称中传递企业价值与发展愿景。比如科技公司可选用凸显创新感的字词，彰显技术属性；文创企业可融入诗意元素，传递文化气质；服务类公司则侧重用大气简洁的用字，体现专业与可靠。</p>
              <p>起名时会综合考量名称的音律顺口度、用字独特性，规避生僻字与行业敏感词，同时确保符合工商注册规范，兼顾品牌传播性与客户记忆点。好的公司名称不仅是企业的身份符号，更能助力快速建立市场认知、拉近与目标客户的距离，为企业长期发展奠定坚实的品牌基础。</p>
            </blockquote>
          </div>
        </section>
      </div>

      <div class="qiming-pages-web-qa qiming-background-default qiming-mt-130">
        <div class="container">
          <div class="qiming-pages-web-qatitle">
            <span>起名网公司起名优势</span>
          </div>
          <div class="qiming-pages-web-qa-box">
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>树立良好形象</p>
              </div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con">
                  <li>公司起名是立足于公司的定位，结合企业文化、行业特性、消费者心理学，品牌营销学等方面，专门为企业量身打造优质好名，更好地体现企业的竞争实力，帮助树立良好品牌形象。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>成为行业龙头</p>
              </div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con">
                  <li>公司起名综合法人的生辰八字，公司经营方向，五行，以及汉字五行匹配，精准匹配适合企业发展的公司名称，挖掘企业巨大潜能，凸显优势，助力企业财运大升，前途无量。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>助力广泛传播</p>
              </div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con">
                  <li>公司起名基于全国企业大数据分析，从最具传播性的公司名字中，甄选出适合企业的汉字，借助专业传播学和心理学分析，进行1对1匹配组合，确保设计出来的名字能受到消费者青睐，更能被广泛传播。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>突破经营困境</p>
              </div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con">
                  <li>公司起名能根据企业行业性质、产品性能、服务对象以及经营范围，结合运程学理论统筹策划分析，对企业、产品品牌进行现状诊断与危机预测应对，精心打造既适合企业自身特点又具有传播力、吸引力，销售力的公司名称与产品品牌。</li>
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
              公司起名推荐阅读
            </h3>
            <div class="sub-nav qiming-visible@s">
              <span class="all qiming-display-inline-block">
                <a
                  href="/gsqm/6.html"
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
  city: '',
  industry: '',
  legalName: '',
  gender: 1,
  birthday: ''
});

const handleSubmit = () => {
  if (!form.industry) {
    message.warning('请输入行业');
    return;
  }
  router.push({ path: '/gongsiqiming', query: { industry: form.industry } });
};
</script>

<style lang="scss">
@import '@/assets/styles/qiming.scss';

.company-name-page {
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

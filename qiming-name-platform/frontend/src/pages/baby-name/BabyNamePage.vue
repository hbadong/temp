<template>
  <div class="baby-name-page">
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
            <li class="current-menu-item">
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
        <li>
          <router-link to="/">首页</router-link>
        </li>
        <li>
          <router-link to="/baobao">宝宝起名</router-link>
        </li>
        <li>
          <router-link to="/bazi">八字起名</router-link>
        </li>
        <li>
          <router-link to="/shici">诗词起名</router-link>
        </li>
        <li>
          <router-link to="/gaimingzi">成人改名</router-link>
        </li>
        <li>
          <router-link to="/xingmingceshi">姓名测试</router-link>
        </li>
        <li>
          <router-link to="/gongsiqiming">公司起名</router-link>
        </li>
        <li>
          <router-link to="/zhouyi">周易起名</router-link>
        </li>
        <li>
          <router-link to="/zhishi">起名知识</router-link>
        </li>
        <li>
          <router-link to="/kxzd">康熙字典</router-link>
        </li>
        <li>
          <router-link to="/baijiaxing">百家姓</router-link>
        </li>
      </ul>
      <div class="mobile-menu-footer">
        <router-link to="/login">登录</router-link>
        <router-link to="/register">注册</router-link>
      </div>
    </div>

    <div class="qiming_vipbgs">
      <img
        src="/images/bbqmBanner.jpg"
        alt=""
      >
    </div>

    <div id="page-content">
      <div class="qiming-pages-vip">
        <div class="qiming_portal_block_summary">
          <div class="formGsQm">
            <h1>宝宝起名</h1>
            <p>以先进AI技术和大数据融合千年传统起名智慧，为您提供独一无二、寓意深远的宝宝名字方案</p>
            <form method="post">
              <div class="two_bd">
                <div class="list_bd">
                  <input
                    v-model="form.surname"
                    type="text"
                    placeholder="请输入宝宝的姓氏"
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
              >立即起名</a>
            </form>
          </div>
        </div>

        <section class="qiming-container qiming-margin-medium-bottom qiming-margin-top-20">
          <div class="qiming-background-default qiming-padding-app qiming-margin-medium-bottom qiming-margin-top-20">
            <h2 class="apply">
              宝宝起名：承载爱意，定制专属成长印记
            </h2>
            <blockquote>
              <p>给宝宝起名是每个家庭迎接新生命的重要环节，既需凝聚父母对孩子的满心期许，又要兼顾名字的音律和谐与内涵深度，同时避开常见重名，让宝宝拥有独特的身份标识。</p>
              <p>起名时可从多方面汲取灵感：参考传统文化，从诗词典故中挑选雅致字词，赋予名字文化底蕴；结合自然意象，用"禾""沐""星"等字，传递对宝宝健康成长的美好祝愿；也可融入家庭情感，比如纪念特殊时刻或承载家族期许，让名字更具温度。</p>
              <p>好的宝宝名字，不仅读来顺口易记，更能成为陪伴孩子一生的温暖符号，在成长路上悄然传递正向力量，见证孩子每一步的美好蜕变。</p>
            </blockquote>
          </div>
        </section>
      </div>

      <div class="yuvip_serve_father">
        <h3 class="yuvip_title">
          宝宝起名综合六大维度
        </h3>
        <div class="yuvip_serve">
          <ul>
            <li>
              <div>
                <img
                  src="/images/wd1.jpg"
                  alt="国学起名"
                >
                <p>国学起名</p>
                <em>从国学经典中取材，将汉语文化精华与当代文化融合，取个影响一生的好名字。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd2.jpg"
                  alt="音形义起名"
                >
                <p>音形义起名</p>
                <em>着重考虑音顺，形美、义内涵的字。做到真正的音美、字美、意境美的好名字。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd3.jpg"
                  alt="期望起名"
                >
                <p>期望起名</p>
                <em>根据父母期望，来结合用字含义，使名字更加有寓意，凸显期望特性的好名字</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd4.jpg"
                  alt="大数据起名"
                >
                <p>大数据起名</p>
                <em>基于每年百万宝宝起名数据分析，通过智能起名分析系统，分析出好的方案。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd5.jpg"
                  alt="诗词起名"
                >
                <p>诗词起名</p>
                <em>根据大量诗词古籍，分析好的名字，组合起更有诗意的名字。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd6.jpg"
                  alt="生肖起名"
                >
                <p>生肖起名</p>
                <em>根据生肖特性，筛选出更加适合使用者的名字方案。</em>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <div class="qiming-pages-web-yw-box qiming-background-default">
        <div class="container">
          <div class="web-yw-box-title">
            <span>名字潜移默化的影响</span>
            <p>"培养宝宝成才，从取个好名字开始"</p>
          </div>
          <div
            class="qiming-grid"
            style="grid-template-columns: repeat(6, 1fr);"
          >
            <div class="web-yw-box-con-li qiming-dongtai">
              <span><img
                src="/images/mh7.jpg"
                alt="塑造气质"
              ></span>
              <p>塑造气质</p>
              <em>好的名字寓意包含家长的期盼指引宝宝成长的方向</em>
            </div>
            <div class="web-yw-box-con-li qiming-dongtai">
              <span><img
                src="/images/mh8.jpg"
                alt="培养自信"
              ></span>
              <p>培养自信</p>
              <em>好的名字是一个人的基本尊严给宝宝树立自信心</em>
            </div>
            <div class="web-yw-box-con-li qiming-dongtai">
              <span><img
                src="/images/mh9.jpg"
                alt="影响人际"
              ></span>
              <p>影响人际</p>
              <em>好的名字会给别人留下好的印象,容易脱颖而出</em>
            </div>
            <div class="web-yw-box-con-li qiming-dongtai">
              <span><img
                src="/images/mh10.jpg"
                alt="好运相持"
              ></span>
              <p>好运相持</p>
              <em>结合宝宝命里五行给宝宝一生健康平安，顺利幸福</em>
            </div>
            <div class="web-yw-box-con-li qiming-dongtai">
              <span><img
                src="/images/mh11.jpg"
                alt="美好祝福"
              ></span>
              <p>美好祝福</p>
              <em>引经据典，寄托了父母对孩子未来的美好祝福</em>
            </div>
            <div class="web-yw-box-con-li qiming-dongtai">
              <span><img
                src="/images/mh12.jpg"
                alt="非凡人生"
              ></span>
              <p>非凡人生</p>
              <em>好的名字为宝宝注入成长能量让孩子起点更高</em>
            </div>
          </div>
        </div>
      </div>

      <section class="blog">
        <div class="container qiming-margin-medium-top qiming-margin-bottom-40">
          <div class="section-title">
            <i class="iconfont icon-file-common" />
            <h3 class="qiming-display-inline-block">
              宝宝起名推荐阅读
            </h3>
            <div class="sub-nav qiming-visible@s">
              <span class="all qiming-display-inline-block">
                <a
                  href="/bbqm/1.html"
                  target="_blank"
                >查看更多<i class="iconfont icon-arrow-right-bold" /></a>
              </span>
            </div>
          </div>
          <div
            class="qiming-grid"
            style="grid-template-columns: repeat(2, 1fr);"
          >
            <div class="blog-item b-r-4 qiming-padding-small qiming-background-default qiming-overflow-hidden">
              <div class="qiming-grid-collapse">
                <div class="qiming-vip-icons">
                  <a
                    href="/a/1591321.html"
                    class="cover b-r-4 qiming-display-block qiming-overflow-hidden"
                  >
                    <img
                      src="https://static.qiming.cn/upload/upimg/0919/1758256277595861.jpg"
                      alt="宝宝起名别跟风"
                    >
                  </a>
                </div>
                <div class="qiming-cat-blog blog-item-content">
                  <div class="qiming-blog-items">
                    <h3 class="qiming-text-truncate">
                      <a
                        href="/a/1591321.html"
                        class="title qiming-h4"
                      >宝宝起名别跟风！8个独特技巧，让名字跳出"重名魔咒"</a>
                    </h3>
                    <p class="qiming-text-small">
                      大家有没有发现，每次去医院或者学校，总能碰到几个孩子叫子涵、梓涵、紫轩、宇轩这样的名字？
                    </p>
                  </div>
                  <div class="item-foot">
                    <div class="cat qiming-margin-bottom-10 qiming-margin-top-10 qiming-text-truncate">
                      <a href="/bbqm/1.html"><i class="iconfont icon-menu qiming-right-3 arttag" />宝宝起名</a>
                    </div>
                    <div class="qiming-flex qiming-flex-middle">
                      <div class="avatar qiming-flex-1 qiming-flex qiming-flex-middle">
                        <img
                          src="/images/avatar.jpg"
                          class="avatar avatar-20 photo"
                          height="20"
                          width="20"
                        >
                        <span class="qiming-text-small qiming-display-block qiming-margin-small-left">清飞扬</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
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
            >
              <img
                src="/images/logo_foot.png"
                alt="起名网"
              >
            </a>
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
  router.push({ path: '/baobao', query: { surname: form.surname, gender: form.gender } });
};
</script>

<style lang="scss">
@import '@/assets/styles/qiming.scss';

.baby-name-page {
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

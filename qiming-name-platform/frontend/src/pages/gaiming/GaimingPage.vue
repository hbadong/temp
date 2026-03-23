<template>
  <div class="gaiming-page">
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
            href="#header-search"
            @click.prevent="showSearch = true"
          >
            <i class="iconfont icon-search" />
          </a>
        </div>
      </div>
    </header>

    <div class="qiming_vipbgs">
      <img
        src="/images/crgm.jpg"
        alt=""
      >
    </div>

    <div id="page-content">
      <div class="qiming-pages-vip">
        <div class="qiming_portal_block_summary">
          <div class="formGsQm">
            <h1>成人改名</h1>
            <p>好名字成就好人生，专业改名服务为您量身定制最适合您的完美名字，开启人生新篇章</p>
            <form method="post">
              <div class="two_bd">
                <div class="list_bd">
                  <input
                    v-model="form.name"
                    type="text"
                    placeholder="请输入您的姓名"
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
              >立即改名</a>
            </form>
          </div>
        </div>

        <section class="qiming-container qiming-margin-medium-bottom qiming-margin-top-20">
          <div class="qiming-background-default qiming-padding-app qiming-margin-medium-bottom">
            <h2 class="apply">
              成人改名：开启人生新篇章
            </h2>
            <blockquote>
              <p>姓名不仅是一个符号，更是伴随一生的名片。一个好的名字可以改善人际关系、增强自信、带来好运。很多人在成长过程中发现自己的名字不够理想，这时选择改名就是一个明智的选择。</p>
              <p>成人改名需要考虑更多因素：与个人生辰八字的契合度、名字的音韵美感、寓意内涵、以及在社交场合的接受度等。我们的专业改名服务会综合考虑这些因素，为您量身定制最适合的名字。</p>
              <p>改名不仅是改变一个名字，更是为人生开启新的可能。一个寓意深远、朗朗上口的好名字，能够在无形中增强自信，帮助您在事业、感情、健康等方面获得更好的发展。</p>
            </blockquote>
          </div>
        </section>
      </div>

      <div class="yuvip_serve_father">
        <h3 class="yuvip_title">
          成人改名四大优势
        </h3>
        <div class="yuvip_serve">
          <ul>
            <li>
              <div>
                <img
                  src="/images/wd1.jpg"
                  alt="八字契合"
                >
                <p>八字契合</p>
                <em>根据您的生辰八字，精准分析五行喜忌，选取最助运的字。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd2.jpg"
                  alt="音韵优美"
                >
                <p>音韵优美</p>
                <em>注重名字的音律搭配，确保读来顺口、悦耳动听。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd3.jpg"
                  alt="寓意深远"
                >
                <p>寓意深远</p>
                <em>名字蕴含美好寓意，寄托对事业、感情、健康的期望。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd5.jpg"
                  alt="独特个性"
                >
                <p>独特个性</p>
                <em>避免重名困扰，打造专属于您的独特身份标识。</em>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <div class="qiming-pages-web-qa qiming-background-default qiming-mt-130">
        <div class="container">
          <div class="qiming-pages-web-qatitle">
            <span>为什么要改名</span>
          </div>
          <div class="qiming-pages-web-qa-box">
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>改善运势</p>
              </div>
              <div class="qa-box-each-detail-con">
                <ul class="qa-box-each-detail-con">
                  <li>名字与命理相合，可以弥补八字中的不足，增强事业运、财运、桃花运，让人生更加顺遂。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>增强自信</p>
              </div>
              <div class="qa-box-each-detail-con">
                <ul class="qa-box-each-detail-con">
                  <li>一个响亮好听的名字能够增强自信心，在社交场合给人留下深刻印象，有助于人际关系的发展。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>塑造形象</p>
              </div>
              <div class="qa-box-each-detail-con">
                <ul class="qa-box-each-detail-con">
                  <li>在职场和商业场合，一个专业、有内涵的名字能够帮助您塑造更好的个人品牌和职业形象。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>开启新篇</p>
              </div>
              <div class="qa-box-each-detail-con">
                <ul class="qa-box-each-detail-con">
                  <li>人生重要转折点（结婚、创业、出国等）改个好名字，寓意人生新篇章的美好开始。</li>
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
              改名知识推荐阅读
            </h3>
            <div class="sub-nav qiming-visible@s">
              <span class="all qiming-display-inline-block">
                <router-link to="/zhishi">
                  查看更多<i class="iconfont icon-arrow-right-bold" />
                </router-link>
              </span>
            </div>
          </div>
        </div>
      </section>
    </div>

    <div
      class="wapnone qiming_follow_service"
      style="top:75%"
    >
      <ul>
        <li class="qiming_follow_service_box qiming_follow_service_ax goTop qiming_footer_s">
          <a
            href="#header"
            class="qiming-display-block"
            @click.prevent="scrollTop"
          >
            <i class="iconfont icon-direction-up" />
            <div class="qiming_follow_service_ax_cont">
              <span class="qiming_follow_service_triangle" />
              <span>返回顶部</span>
            </div>
          </a>
        </li>
      </ul>
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
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { message } from 'ant-design-vue';

const router = useRouter();
const showSearch = ref(false);

const form = reactive({
  name: '',
  gender: 1,
  birthday: '',
  birthAddress: ''
});

const handleSubmit = () => {
  if (!form.name) {
    message.warning('请输入姓名');
    return;
  }
  message.info('改名功能开发中，敬请期待');
};

const scrollTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
};
</script>

<style lang="scss">
@import '@/assets/styles/qiming.scss';

.gaiming-page {
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

.yuvip_serve li {
  width: 25%;
}
</style>

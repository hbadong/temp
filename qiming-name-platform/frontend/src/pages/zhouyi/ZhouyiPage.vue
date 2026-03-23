<template>
  <div class="zhouyi-page">
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
            <li class="current-menu-item">
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
        src="/images/gsqm.jpg"
        alt=""
      >
    </div>

    <div id="page-content">
      <div class="qiming-pages-vip">
        <div class="qiming_portal_block_summary">
          <div class="formGsQm">
            <h1>周易起名</h1>
            <p>汲取千年国学智慧，融汇《周易》精髓，结合现代科学理念，为您提供文化深厚、寓意吉祥的好名字</p>
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
          <div class="qiming-background-default qiming-padding-app qiming-margin-medium-bottom">
            <h2 class="apply">
              周易起名：传承千年国学智慧
            </h2>
            <blockquote>
              <p>《周易》是中国古代最重要的典籍之一，被誉为"群经之首，大道之源"。它蕴含着深刻的哲学思想、宇宙规律和人生智慧，是中华民族文化的瑰宝。</p>
              <p>周易起名是在传统八字起名的基础上，进一步融入《周易》的象、数、理、占四大体系，通过卦象分析、五行流转、阴阳平衡等方式，为您选取一个既符合命理需求又具有深厚文化底蕴的好名字。</p>
              <p>一个好名字应当兼具形美、音美、意美，同时与个人的命理相契合。周易起名正是遵循这些原则，在浩瀚的汉字库中为您甄选最合适的字词组合，让您的名字成为伴随一生的吉祥符号。</p>
            </blockquote>
          </div>
        </section>
      </div>

      <div class="yuvip_serve_father">
        <h3 class="yuvip_title">
          周易起名五大特色
        </h3>
        <div class="yuvip_serve">
          <ul>
            <li>
              <div>
                <img
                  src="/images/wd1.jpg"
                  alt="周易卦象"
                >
                <p>周易卦象</p>
                <em>结合八卦意象，选取与命主相生的卦象用字，助运人生。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd2.jpg"
                  alt="五行流转"
                >
                <p>五行流转</p>
                <em>分析八字五行旺衰，选取喜用神字，补足命理不足。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd3.jpg"
                  alt="阴阳平衡"
                >
                <p>阴阳平衡</p>
                <em>注重名字的阴阳属性调和，达到身心平衡、和谐发展。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd4.jpg"
                  alt="数理学"
                >
                <p>数理学</p>
                <em>依据易数推算，选取天格、地格、人格、总格、外格最佳的配置。</em>
              </div>
            </li>
            <li>
              <div>
                <img
                  src="/images/wd5.jpg"
                  alt="文化内涵"
                >
                <p>文化内涵</p>
                <em>名字蕴含周易哲理，彰显文化底蕴，寓意深远悠长。</em>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <div class="qiming-pages-web-yw-box qiming-background-default">
        <div class="container">
          <div class="web-yw-box-title">
            <span>周易起名的价值</span>
            <p>"名字承载命运，智慧点亮人生"</p>
          </div>
          <div
            class="qiming-grid"
            style="grid-template-columns: repeat(4, 1fr);"
          >
            <div class="web-yw-box-con-li qiming-dongtai">
              <span><img
                src="/images/mh7.jpg"
                alt="文化传承"
              ></span>
              <p>文化传承</p>
              <em>周易是中华文化的源头，名字传承千年智慧</em>
            </div>
            <div class="web-yw-box-con-li qiming-dongtai">
              <span><img
                src="/images/mh8.jpg"
                alt="命运改善"
              ></span>
              <p>命运改善</p>
              <em>通过周易智慧调理气场，改善人生运势</em>
            </div>
            <div class="web-yw-box-con-li qiming-dongtai">
              <span><img
                src="/images/mh9.jpg"
                alt="事业助运"
              ></span>
              <p>事业助运</p>
              <em>好名字带来好人缘，职场发展更顺利</em>
            </div>
            <div class="web-yw-box-con-li qiming-dongtai">
              <span><img
                src="/images/mh10.jpg"
                alt="健康平安"
              ></span>
              <p>健康平安</p>
              <em>阴阳平衡调和，保障身心健康发展</em>
            </div>
          </div>
        </div>
      </div>

      <div class="qiming-pages-web-qa qiming-background-default qiming-mt-130">
        <div class="container">
          <div class="qiming-pages-web-qatitle">
            <span>周易起名常见问题</span>
          </div>
          <div class="qiming-pages-web-qa-box">
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>周易起名与八字起名有什么区别？</p>
              </div>
              <div class="qa-box-each-detail-con">
                <ul class="qa-box-each-detail-con">
                  <li>八字起名主要依据命主的生辰八字分析五行喜忌；而周易起名在此基础上增加了《周易》八卦、象数等更深层次的理论体系，可以说周易起名是八字起名的升级版，更加全面和深奥。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>周易起名需要提供什么信息？</p>
              </div>
              <div class="qa-box-each-detail-con">
                <ul class="qa-box-each-detail-con">
                  <li>需要提供：姓氏、性别、出生年月日时（精确到时辰）、出生地点。这些信息用于推算命主的八字、五行属性以及卦象，从而选取最合适的名字。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>周易起名的原理是什么？</p>
              </div>
              <div class="qa-box-each-detail-con">
                <ul class="qa-box-each-detail-con">
                  <li>周易起名的原理是通过分析个人的生辰信息，得出其八字命盘，然后根据《周易》的理论体系，选取与命主相合的汉字作为名字。这些汉字的五行属性、卦象含义与命主形成互补，从而起到助运的作用。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t">
                <p>周易起名需要多长时间？</p>
              </div>
              <div class="qa-box-each-detail-con">
                <ul class="qa-box-each-detail-con">
                  <li>根据不同的服务类型，周易起名需要1-3个工作日。我们的专家会进行多轮分析，确保为您选取的名字在各个层面都达到最佳状态。</li>
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
              周易起名推荐阅读
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
  message.info('周易起名功能开发中，敬请期待');
};

const scrollTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
};
</script>

<style lang="scss">
@import '@/assets/styles/qiming.scss';

.zhouyi-page {
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
  width: 20%;
}

@media (max-width: 768px) {
  .yuvip_serve li {
    width: 50%;
  }
}
</style>

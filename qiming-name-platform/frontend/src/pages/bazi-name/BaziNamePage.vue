<template>
  <div class="bazi-name-page">
    <header class="header qiming-background-default">
      <div class="navbar">
        <router-link to="/" class="logo">
          <div class="logobg"></div>
        </router-link>
        <nav class="nav">
          <ul>
            <li>
              <router-link to="/">首页</router-link>
            </li>
            <li>
              <router-link to="/baobao">宝宝起名</router-link>
            </li>
            <li class="current-menu-item">
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
        </nav>
        <div class="header-info">
          <a href="#header-search" @click.prevent>
            <i class="iconfont icon-search"></i>
          </a>
        </div>
      </div>
    </header>

    <div class="qiming_vipbgs">
      <img src="/images/bzqmBanner.jpg" alt="" />
    </div>

    <div id="page-content">
      <div class="qiming-pages-vip">
        <div class="qiming_portal_block_summary">
          <div class="formGsQm">
            <h1>八字起名</h1>
            <p>汇聚多位国内权威易学大师，以深厚经验精准解析八字，结合心理学，量身打造帮扶一生的优质好名</p>
            <form method="post">
              <div class="two_bd">
                <div class="list_bd">
                  <input type="text" v-model="form.surname" placeholder="请输入姓氏" />
                  <div class="sexSelect">
                    <span :class="{ active: form.gender === 1 }" @click="form.gender = 1">男</span>
                    <span :class="{ active: form.gender === 2 }" @click="form.gender = 2">女</span>
                  </div>
                </div>
                <div class="list_bd">
                  <input type="text" v-model="form.birthday" class="J_datepicker" placeholder="请选择出生日期" readonly />
                  <img src="/images/xl.png" alt="" />
                </div>
              </div>
              <div class="two_bd" style="margin-top:20px;">
                <div class="list_bd">
                  <select v-model="form.firstWuxing">
                    <option disabled value="">请选择首字五行</option>
                    <option value="金">金</option>
                    <option value="木">木</option>
                    <option value="水">水</option>
                    <option value="火">火</option>
                    <option value="土">土</option>
                  </select>
                  <img src="/images/xl.png" alt="" />
                </div>
                <div class="list_bd">
                  <select v-model="form.secondWuxing">
                    <option disabled value="">请选择尾字五行</option>
                    <option value="金">金</option>
                    <option value="木">木</option>
                    <option value="水">水</option>
                    <option value="火">火</option>
                    <option value="土">土</option>
                  </select>
                  <img src="/images/xl.png" alt="" />
                </div>
              </div>
              <a class="qmbtn" href="javascript:;" @click="handleSubmit">立即起名</a>
            </form>
          </div>
        </div>

        <section class="qiming-container qiming-margin-medium-bottom qiming-margin-top-20">
          <div class="qiming-background-default qiming-padding-app qiming-margin-medium-bottom qiming-margin-top-20">
            <h2 class="apply">八字起名：承传统智慧，调五行吉名</h2>
            <blockquote>
              <p>八字起名是流传千年的传统命名方式，以宝宝出生的年、月、日、时为基础，拆解为天干地支组合的"八字"，核心在于分析命局中五行（金、木、水、火、土）的旺衰与平衡状态。</p>
              <p>通过定位喜用神，选用适配五行属性的字词补益命理，而非简单"缺啥补啥"，同时兼顾音律美感、字形和谐与吉祥寓意。这种方式将"天人合一"哲学融入命名，既承载父母对孩子的期许，又让名字成为贴合命格的吉祥符号。</p>
              <p>专业的八字起名需结合生肖喜忌、三才五格等多重因素，让名字既有文化底蕴，又能助力成长，是传统智慧与人文关怀的生动结合。</p>
            </blockquote>
          </div>
        </section>
      </div>

      <div class="yuvip_serve_father">
        <h3 class="yuvip_title">八字起名综合六大维度</h3>
        <div class="yuvip_serve">
          <ul>
            <li><div><img src="/images/wd1.jpg" alt="国学起名" /><p>国学起名</p><em>从国学经典中取材，将汉语文化精华与当代文化融合，取个影响一生的好名字。</em></div></li>
            <li><div><img src="/images/wd2.jpg" alt="音形义起名" /><p>音形义起名</p><em>着重考虑音顺，形美、义内涵的字。做到真正的音美、字美、意境美的好名字。</em></div></li>
            <li><div><img src="/images/wd3.jpg" alt="期望起名" /><p>期望起名</p><em>根据父母期望，来结合用字含义，使名字更加有寓意，凸显期望特性的好名字</em></div></li>
            <li><div><img src="/images/wd4.jpg" alt="大数据起名" /><p>大数据起名</p><em>基于每年百万宝宝起名数据分析，通过智能起名分析系统，分析出好的方案。</em></div></li>
            <li><div><img src="/images/wd5.jpg" alt="诗词起名" /><p>诗词起名</p><em>根据大量诗词古籍，分析好的名字，组合起更有诗意的名字。</em></div></li>
            <li><div><img src="/images/wd6.jpg" alt="生肖起名" /><p>生肖起名</p><em>根据生肖特性，筛选出更加适合使用者的名字方案。</em></div></li>
          </ul>
        </div>
      </div>

      <div class="qiming-pages-web-qa qiming-background-default qiming-mt-130">
        <div class="container">
          <div class="qiming-pages-web-qatitle">
            <span>起名网八字起名优势</span>
          </div>
          <div class="qiming-pages-web-qa-box">
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t"><p>传承国学精髓</p></div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con">
                  <li>八字起名以传统国学结合现代科学进行综合起名，在了解个人先天命格的前提下，进行准确分析，借助心理学、汉语语言学、音律学等多门学科进行参考，最终匹配适合个人的名字。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t"><p>开启一生福运</p></div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con">
                  <li>八字起名通过五行八字分析出五行中的旺衰情况，选择相对应的字来进行平衡或压制，强则抑之，弱则补之，平衡五行，能够对人生运势起到一定的辅助作用。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t"><p>提高审美趣味</p></div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con">
                  <li>核心以生辰分析为依据，根据生命信息所反映出的规律，借助后天运行调整，综合韵律、义蕴、美学、心理等诸多方面进行综合设计，让名字更具创意性、艺术性和美感。</li>
                </ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t"><p>扭转人生格局</p></div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con">
                  <li>八字起名讲究顺势利导，借助生辰八字格局，以五行平衡来调解命局中的缺陷，让命局更趋于和谐，从而避开阻碍困境，助力人生更为畅顺。</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <section class="blog">
        <div class="container qiming-margin-medium-top qiming-margin-bottom-40">
          <div class="section-title">
            <i class="iconfont icon-file-common"></i>
            <h3 class="qiming-display-inline-block">八字起名推荐阅读</h3>
            <div class="sub-nav qiming-visible@s">
              <span class="all qiming-display-inline-block">
                <a href="/bzqm/2.html" target="_blank">查看更多<i class="iconfont icon-arrow-right-bold"></i></a>
              </span>
            </div>
          </div>
        </div>
      </section>
    </div>

    <footer class="footer qiming-background-secondary">
      <div class="foot qiming-container qiming-padding">
        <div class="qiming-grid" style="grid-template-columns: 1fr 2fr;">
          <div class="foot-item foot-item-first qiming-position-relative qiming-flex">
            <a href="/" class="foot-logo qiming-display-block">
              <img src="/images/logo_foot.png" alt="起名网" />
            </a>
            <p class="qiming-text-small">起名网专注科学智能宝宝起名，测名字打分平台，结合传统国学文化的智能起名系统研发和起名学术探索交流，以"只为一个好名字"为宗旨，潜心研发，百次升级，千万级大数据分析，助您轻松起好名。</p>
          </div>
          <div class="qiming-visible@s">
            <div class="qiming-grid" style="grid-template-columns: repeat(3, 1fr);">
              <div class="foot-item">
                <div class="foot-item-title"><i class="iconfont icon-security"></i>快捷导航</div>
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
                <div class="foot-item-title"><i class="iconfont icon-security"></i>关于我们</div>
                <ul class="qiming-padding-remove">
                  <li><a href="/about.html">关于我们</a></li>
                  <li><a href="/service.html">服务条款</a></li>
                  <li><a href="/copyright.html">版权声明</a></li>
                  <li><a href="/busine.html">投诉建议</a></li>
                </ul>
              </div>
              <div class="foot-item">
                <div class="foot-item-title"><i class="iconfont icon-security"></i>友情链接</div>
                <ul class="qiming-padding-remove">
                  <li><a href="https://zidian.qiming.cn/" target="_blank">汉语字典</a></li>
                  <li><a href="https://cidian.qiming.cn/" target="_blank">汉语词典</a></li>
                  <li><a href="https://chengyu.qiming.cn/" target="_blank">成语大全</a></li>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <div class="foot-cop">
          <div class="qiming-container qiming-padding-small qiming-clearfix">
            <div class="qiming-float-left">
              <span>© 2005-2025 <a href="https://www.qiming.cn/" target="_blank">www.qiming.cn</a> &amp; All rights reserved</span>
              <span class="qiming-margin-small-right"><a href="https://beian.miit.gov.cn/" target="_blank" rel="noreferrer nofollow">皖ICP备2024064902号</a></span>
            </div>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'

const router = useRouter()

const form = reactive({
  surname: '',
  gender: 1,
  birthday: '',
  firstWuxing: '',
  secondWuxing: ''
})

const handleSubmit = () => {
  if (!form.surname) {
    message.warning('请输入姓氏')
    return
  }
  router.push({ path: '/bazi', query: { surname: form.surname, gender: form.gender } })
}
</script>

<style lang="scss">
@import '@/assets/styles/qiming.scss';

.bazi-name-page {
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

select {
  width: 90%;
  height: 80%;
  border: 0;
  font-size: 1rem;
  background: none;
  color: #696969;
}

.qiming-mt-130 {
  margin-top: 30px;
}
</style>

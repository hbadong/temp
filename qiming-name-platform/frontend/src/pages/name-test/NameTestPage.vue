<template>
  <div class="name-test-page">
    <header class="header qiming-background-default">
      <div class="navbar">
        <router-link to="/" class="logo"><div class="logobg"></div></router-link>
        <nav class="nav">
          <ul>
            <li><router-link to="/">首页</router-link></li>
            <li><router-link to="/baobao">宝宝起名</router-link></li>
            <li><router-link to="/bazi">八字起名</router-link></li>
            <li><router-link to="/shici">诗词起名</router-link></li>
            <li><router-link to="/gaimingzi">成人改名</router-link></li>
            <li class="current-menu-item"><router-link to="/xingmingceshi">姓名测试</router-link></li>
            <li><router-link to="/gongsiqiming">公司起名</router-link></li>
            <li><router-link to="/zhouyi">周易起名</router-link></li>
            <li><router-link to="/zhishi">起名知识</router-link></li>
            <li><router-link to="/kxzd">康熙字典</router-link></li>
            <li><router-link to="/baijiaxing">百家姓</router-link></li>
          </ul>
        </nav>
        <div class="header-info">
          <a href="#header-search" @click.prevent><i class="iconfont icon-search"></i></a>
        </div>
      </div>
    </header>

    <div class="qiming_vipbgs">
      <img src="/images/xmcsBanner.jpg" alt="" />
    </div>

    <div id="page-content">
      <div class="qiming-pages-vip">
        <div class="qiming_portal_block_summary">
          <div class="formGsQm">
            <h1>姓名测试打分</h1>
            <p>姓名测试打分通过姓名学五格数理，并且结合周易五行相生相克的理念，根据五格数理吉凶及相生相克情况，最后得出您的姓名评分测试打分。</p>
            <form method="post">
              <div class="two_bd">
                <div class="list_bd">
                  <input type="text" v-model="form.name" placeholder="请输入您的姓名" />
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
              <div class="list_bd" style="width:100%;margin-right:0;margin-bottom:20px;">
                <input type="text" v-model="form.birthAddress" placeholder="请选择出生地" readonly />
                <img src="/images/xl.png" alt="" />
              </div>
              <a class="qmbtn" href="javascript:;" @click="handleSubmit">立即测名打分</a>
            </form>
          </div>
        </div>

        <section class="qiming-container qiming-margin-medium-bottom qiming-margin-top-20">
          <div class="qiming-background-default qiming-xmcs qiming-padding-app qiming-margin-medium-bottom">
            <h2 class="apply">大数据分析</h2>
            <blockquote>
              <span>姓名使用情况</span>
              <span>男女使用比例</span>
              <span>单字使用性别排名</span>
              <p>基于全网亿万姓名大数据分析，通过全网独家智能测名系统、大数据流行度分析系统，分析名字的使用频率与流行度趋势。多维度名字解析系统，让名字数据可视化，为用户提供更专业、更科学、更可靠的分析结果。</p>
            </blockquote>
            <h2 class="apply">音形义详解</h2>
            <blockquote>
              <span>字形解构</span>
              <span>音律搭配</span>
              <span>字义解析</span>
              <p>基于字形、字义、音律一大分析系统，解析名字的意蕴内涵，名字的结构搭配，名字的音律组合，全面剖析名字综合属性、名与姓的搭配、名字来源，让名字指引未来的人生方向。</p>
            </blockquote>
            <h2 class="apply">星座分析</h2>
            <blockquote>
              <span>星座特性</span>
              <span>性格标签</span>
              <span>匹配指数</span>
              <p>基于星座特质、星座性格优缺点，分析名字与星座性格的匹配度，通过名字的暗示寓意与星座性格标签解析，分析名字能否补足星座性格缺陷。</p>
            </blockquote>
            <h2 class="apply">生肖分析</h2>
            <blockquote>
              <span>生肖解析</span>
              <span>生肖特质</span>
              <span>匹配指数</span>
              <p>以传统生肖姓名学理论为依据，根据生肖解析，生肖特质，生肖性格标签，解析名字与名主生肖属性的匹配度，从属相方面提升个人运气。</p>
            </blockquote>
            <h2 class="apply">姓名学分析</h2>
            <blockquote>
              <span>数理学</span>
              <span>姓名学配置</span>
              <span>心理学解密</span>
              <p>根据《数理学》的"象"、"数"理论，将人的姓名来解析名字，分析名字对名主人生多个方面的运程影响，知名方能知命。</p>
            </blockquote>
          </div>
        </section>
      </div>

      <div class="qiming-pages-web-qa qiming-background-default qiming-mt-130">
        <div class="container">
          <div class="qiming-pages-web-qatitle"><span>起名网测名打分优势</span></div>
          <div class="qiming-pages-web-qa-box">
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t"><p>传统与科学结合</p></div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con"><li>测名打分基于姓名学理论，通过AI智能分析系统，大数据流行度分析系统，名字使用的频率与流行趋势，多维度名字解析系统，让名字可视化，为您提供专业，科学，可靠的分析结果。</li></ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t"><p>全面综合评估</p></div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con"><li>测名打分能根据五行数理、寓意解析、音韵评分、心理暗示、汉字潮流等多方面，全方位综合评估名字好坏，并解析名字与个人匹配情况，让您知晓自身名字不足之处，及时作出调整。</li></ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t"><p>提供精准参考</p></div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con"><li>测名打分通过个人名字，分析个人的三才配置，五格吉凶分析，命格简批，姓名与性格，基础运，成功运等相关内容，为您提供最精准、最详尽的参考。</li></ul>
              </div>
            </div>
            <div class="qiming-pages-web-qa-box-each">
              <div class="qiming-pages-web-qa-box-t"><p>有效避免重名</p></div>
              <div class="qiming-pages-web-qa-box-each-detail">
                <ul class="qa-box-each-detail-con"><li>基于大数据分析，测名打分会综合考量每个名字的重复使用情况，为您提供专业有效数据，帮助您避开重名烦恼，保障名字的合理性与独特性。</li></ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <section class="blog">
        <div class="container qiming-margin-medium-top qiming-margin-bottom-40">
          <div class="section-title">
            <i class="iconfont icon-file-common"></i>
            <h3 class="qiming-display-inline-block">姓名测试推荐阅读</h3>
            <div class="sub-nav qiming-visible@s">
              <span class="all qiming-display-inline-block">
                <a href="/xmcs/5.html" target="_blank">查看更多<i class="iconfont icon-arrow-right-bold"></i></a>
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
            <a href="/" class="foot-logo qiming-display-block"><img src="/images/logo_foot.png" alt="起名网" /></a>
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
  name: '',
  gender: 1,
  birthday: '',
  birthAddress: ''
})

const handleSubmit = () => {
  if (!form.name) {
    message.warning('请输入姓名')
    return
  }
  router.push({ path: '/xingmingceshi', query: { name: form.name, gender: form.gender } })
}
</script>

<style lang="scss">
@import '@/assets/styles/qiming.scss';

.name-test-page {
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

.qiming-xmcs span {
  display: inline-block;
  margin-right: 20px;
  margin-top: 13px;
  border-radius: 3px;
  padding: 5px 45px;
  color: #fff;
  font-weight: 400;
  font-size: 15px;
  background-color: #a93121;
  background-image: linear-gradient(90deg,#b54807,#e8241a);
  box-shadow: 0 5px 10px 0 rgba(166,95,32,0.3);
  margin-bottom: 10px;
}
</style>

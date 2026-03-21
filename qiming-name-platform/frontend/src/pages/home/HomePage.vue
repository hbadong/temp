<template>
  <div class="home-page">
    <header class="header">
      <div class="container">
        <router-link to="/" class="logo">
          <img src="/images/logo.png" alt="起名网" />
          <span>起名网</span>
        </router-link>
        <nav class="nav">
          <router-link to="/">首页</router-link>
          <router-link to="/baobao">宝宝起名</router-link>
          <router-link to="/bazi">八字起名</router-link>
          <router-link to="/shici">诗词起名</router-link>
          <router-link to="/gaimingzi">成人改名</router-link>
          <router-link to="/xingmingceshi">姓名测试</router-link>
          <router-link to="/gongsiqiming">公司起名</router-link>
          <router-link to="/zhouyi">周易起名</router-link>
          <router-link to="/zhishi">起名知识</router-link>
          <router-link to="/kxzd">康熙字典</router-link>
          <router-link to="/baijiaxing">百家姓</router-link>
        </nav>
      </div>
    </header>

    <main class="main">
      <section class="hero">
        <div class="container">
          <h1>起名网-专注宝宝起名取名测名字平台</h1>
          <div class="search-bar">
            <a-input-search v-model:value="searchKeyword" placeholder="输入姓氏或名字搜索" size="large" @search="onSearch" />
          </div>
          <div class="hot-search">
            <span>搜索最多的：</span>
            <router-link v-for="item in hotSearches" :key="item" :to="`/kxzd/${item.path}`">{{ item.name }}</router-link>
          </div>
        </div>
      </section>

      <section class="quick-tools">
        <div class="container">
          <router-link to="/baobao" class="tool-btn">宝宝起名</router-link>
          <router-link to="/xingmingceshi" class="tool-btn">姓名测试</router-link>
          <router-link to="/baobao" class="tool-btn">姓名打分</router-link>
        </div>
      </section>

      <section class="luban-section">
        <div class="container">
          <div class="luban-form">
            <h3>宝宝起名</h3>
            <p>AI智能起名 · 大数据 · 国学智慧</p>
            <div class="form-row">
              <a-input v-model:value="babyForm.surname" placeholder="姓氏" />
              <a-radio-group v-model:value="babyForm.gender">
                <a-radio :value="1">男</a-radio>
                <a-radio :value="2">女</a-radio>
              </a-radio-group>
              <a-button type="primary" @click="goBabyName">立即宝宝起名</a-button>
            </div>
          </div>
          <div class="luban-icons">
            <img src="/images/luopan.png" alt="" />
            <img src="/images/zhizheng.png" alt="" />
          </div>
          <div class="test-form">
            <h3>姓名测试</h3>
            <p>九维测名法全方位评测</p>
            <div class="form-row">
              <a-input v-model:value="testForm.name" placeholder="姓名" />
              <a-radio-group v-model:value="testForm.gender">
                <a-radio :value="1">男</a-radio>
                <a-radio :value="2">女</a-radio>
              </a-radio-group>
              <a-button type="primary" @click="goNameTest">立即测名打分</a-button>
            </div>
          </div>
        </div>
      </section>

      <section class="services-grid">
        <div class="container">
          <router-link v-for="svc in mainServices" :key="svc.path" :to="svc.path" class="service-card">
            <img :src="svc.image" :alt="svc.title" />
            <span>{{ svc.title }}</span>
          </router-link>
        </div>
      </section>

      <section class="poetry-section">
        <div class="container">
          <h2>唐诗宋词传承千年的诗意起名艺术</h2>
          <router-link v-for="p in poetryServices" :key="p.path" :to="p.path" class="poetry-card">
            <img :src="p.image" :alt="p.title" />
            <span>{{ p.title }}</span>
          </router-link>
        </div>
      </section>

      <section class="hot-names-section">
        <div class="container">
          <h2>别人正在查的姓名<span>专业的国学起名网站</span></h2>
          <div class="hot-names-list">
            <router-link v-for="name in hotNames" :key="name.path" :to="name.path" :title="name.full">
              {{ name.name }}
            </router-link>
          </div>
        </div>
      </section>

      <section class="latest-articles">
        <div class="container">
          <h2>最新更新起名</h2>
          <ul>
            <li v-for="article in latestArticles" :key="article.path">
              <router-link :to="article.path">{{ article.title }}</router-link>
            </li>
          </ul>
        </div>
      </section>

      <section class="service-intro">
        <div class="container">
          <router-link to="/baobao" class="intro-card main">
            <img src="/images/baobao.jpg" alt="宝宝起名" />
            <div class="intro-text">
              <h3>宝宝起名时尚 高雅 大气 吉祥</h3>
              <p>培养宝宝成才，从起个好名字开始</p>
            </div>
          </router-link>
        </div>
      </section>

      <section class="service-cards">
        <div class="container">
          <div class="service-card-item">
            <h3>八字起名</h3>
            <p>汇聚多位国内权威易学大师，以深厚经验精准解析八字</p>
            <router-link to="/bazi" class="card-link">立即八字起名</router-link>
          </div>
          <div class="service-card-item">
            <h3>公司起名</h3>
            <p>资深命名专家与品牌策划大师联手打造企业好名</p>
            <router-link to="/gongsiqiming" class="card-link">立即公司起名</router-link>
          </div>
          <div class="service-card-item">
            <h3>诗词起名</h3>
            <p>从二十多万诗词古文中取字，确保每个名字意蕴优美</p>
            <router-link to="/shici" class="card-link">立即诗词起名</router-link>
          </div>
          <div class="service-card-item">
            <h3>周易起名</h3>
            <p>汲取千年国学智慧，融汇《周易》精髓</p>
            <router-link to="/zhouyi" class="card-link">立即周易起名</router-link>
          </div>
        </div>
      </section>

      <section class="ranks-section">
        <div class="container">
          <h2>起个好名字，陪伴一辈子</h2>
          <a-row :gutter="24">
            <a-col :span="12">
              <a-card title="男孩热门名字排行">
                <template #extra><router-link to="/baobao">更多</router-link></template>
                <div class="rank-list">
                  <router-link v-for="name in boyNames" :key="name.path" :to="name.path">
                    {{ name.name }}
                  </router-link>
                </div>
              </a-card>
            </a-col>
            <a-col :span="12">
              <a-card title="女孩热门名字排行">
                <template #extra><router-link to="/baobao">更多</router-link></template>
                <div class="rank-list">
                  <router-link v-for="name in girlNames" :key="name.path" :to="name.path">
                    {{ name.name }}
                  </router-link>
                </div>
              </a-card>
            </a-col>
          </a-row>
        </div>
      </section>

      <section class="articles-section">
        <div class="container">
          <a-row :gutter="24">
            <a-col :span="8">
              <h3>八字起名</h3>
              <ul>
                <li v-for="a in baziArticles" :key="a.path">
                  <router-link :to="a.path">{{ a.title }}</router-link>
                </li>
              </ul>
            </a-col>
            <a-col :span="8">
              <h3>诗词起名</h3>
              <ul>
                <li v-for="a in poetryArticles" :key="a.path">
                  <router-link :to="a.path">{{ a.title }}</router-link>
                </li>
              </ul>
            </a-col>
            <a-col :span="8">
              <h3>周易起名</h3>
              <ul>
                <li v-for="a in zhouyiArticles" :key="a.path">
                  <router-link :to="a.path">{{ a.title }}</router-link>
                </li>
              </ul>
            </a-col>
          </a-row>
        </div>
      </section>

      <section class="stats-section">
        <div class="container">
          <div class="stat-item">
            <strong>5000万+</strong>
            <span>访问总数</span>
          </div>
          <div class="stat-item">
            <strong>2000万+</strong>
            <span>名字库收录</span>
          </div>
          <div class="stat-item">
            <strong>560万+</strong>
            <span>起名知识</span>
          </div>
          <div class="stat-item">
            <strong>80万+</strong>
            <span>起名客户</span>
          </div>
          <div class="stat-item">
            <strong>98%+</strong>
            <span>用户满意度</span>
          </div>
          <div class="stat-item">
            <strong>20年+</strong>
            <span>运行时间</span>
          </div>
        </div>
      </section>
    </main>

    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-links">
            <h4>快捷导航</h4>
            <router-link to="/baobao">宝宝起名</router-link>
            <router-link to="/bazi">八字起名</router-link>
            <router-link to="/zhouyi">周易起名</router-link>
            <router-link to="/kxzd">康熙字典</router-link>
            <router-link to="/gongsiqiming">公司起名</router-link>
            <router-link to="/xingmingceshi">姓名测试</router-link>
          </div>
          <div class="footer-links">
            <h4>关于我们</h4>
            <router-link to="/about">关于我们</router-link>
            <router-link to="/service">服务条款</router-link>
            <router-link to="/copyright">版权声明</router-link>
            <router-link to="/busine">投诉建议</router-link>
          </div>
          <div class="footer-links">
            <h4>友情链接</h4>
            <a href="https://zidian.qiming.cn" target="_blank">汉语字典</a>
            <a href="https://cidian.qiming.cn" target="_blank">汉语词典</a>
            <a href="https://chengyu.qiming.cn" target="_blank">成语大全</a>
          </div>
        </div>
        <div class="footer-copyright">
          © 2005-2025 <a href="https://www.qiming.cn">起名网</a> All rights reserved
          <a href="https://beian.miit.gov.cn/" target="_blank">皖ICP备2024064902号</a>
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
const searchKeyword = ref('')

const babyForm = reactive({ surname: '', gender: 1 })
const testForm = reactive({ name: '', gender: 1 })

const hotSearches = ref([
  { name: '雪', path: '/kxzd/998141.html' },
  { name: '旭', path: '/kxzd/892215.html' },
  { name: '煜晨', path: '/mingzi/88032507.html' }
])

const mainServices = [
  { path: '/baobao', title: '宝宝起名', image: '/images/xbbqm.jpg' },
  { path: '/bazi', title: '八字起名', image: '/images/xbzqm.jpg' },
  { path: '/shici', title: '诗词起名', image: '/images/xscqm.jpg' },
  { path: '/gaimingzi', title: '成人改名', image: '/images/xcrgm.jpg' },
  { path: '/xingmingceshi', title: '姓名测试', image: '/images/xxmcs.jpg' },
  { path: '/gongsiqiming', title: '公司起名', image: '/images/xgsqm.jpg' },
  { path: '/zhishi', title: '起名知识', image: '/images/xqmzs.jpg' },
  { path: '/kxzd', title: '康熙字典', image: '/images/xkxzd.jpg' }
]

const poetryServices = [
  { path: '/tangshi', title: '唐诗起名', image: '/images/tang.png' },
  { path: '/shijing', title: '诗经起名', image: '/images/shi.png' },
  { path: '/songci', title: '宋词起名', image: '/images/song.png' },
  { path: '/chuci', title: '楚辞起名', image: '/images/ci.png' }
]

const hotNames = ref([
  '牟颖周', '迟善玺', '璩香之', '孔欣雅', '田彦鑫', '吴梓熙', '孙贝芊', '孙家淼', '魏玉来', '岳煦尧', '廖承锐', '舒宏庆'
].map(n => ({ name: n, path: `/mingzi/${n}` })));

const latestArticles = [
  { title: '有哪些出自《易经》且有寓意的名字？', path: '/a/1126860.html' },
  { title: '出自易经小众名字，大气且吉利', path: '/a/1821259.html' },
  { title: '《周易》经典取名赏析，周易起名', path: '/a/1458258.html' }
]

const boyNames = ref([
  '颜豪', '颢凯', '颢哲', '颢宁', '颢宸', '璟桓', '璟霆', '翔士', '名浩', '淳皓', '家岐', '毅铮'
].map(n => ({ name: n, path: `/mingzi/${n}` })));

const girlNames = ref([
  '颜菲', '孟馨', '宁丽', '宁俪', '宁娟', '宁婕', '宁汐', '宁萱', '宇妃', '宇妍', '宇妮', '宇娇'
].map(n => ({ name: n, path: `/mingzi/${n}` })));

const baziArticles = [
  { title: '八字起名取名的寓意，生辰八字怎么起名字', path: '/a/1746243.html' },
  { title: '八字五行不全，就一定不好吗？', path: '/a/1983644.html' },
  { title: '怎么样根据生辰八字为宝宝起名字', path: '/a/1755549.html' },
  { title: '如何根据生辰八字起名取名', path: '/a/1607850.html' },
  { title: '为什么起名要结合生辰八字起名，什么是三才五格呢？', path: '/a/1792251.html' },
  { title: '八字五行缺火的人应该怎么起名？', path: '/a/1699752.html' }
].map(a => ({ ...a, path: '/a/1126860' }))

const poetryArticles = [
  { title: '如何起一个富含诗意的好名字', path: '/a/176842' },
  { title: '从诗词歌赋谈起名，如何起个好名字', path: '/a/1441612' },
  { title: '有哪些适合起名的古诗词？', path: '/a/1350553' },
  { title: '诗词起名，古诗词中有哪些好听的名字', path: '/a/1600454' },
  { title: '诗词起名，盘点100个出自诗词的好听名字', path: '/a/1149855' },
  { title: '诗词起名，藏在诗词中的绝美名字', path: '/a/1774656' }
]

const zhouyiArticles = [
  { title: '周易起名，来自周易里的内涵的好名欣赏', path: '/a/1514734' },
  { title: '周易起名：《周易》中那些大气厚重的好听名字', path: '/a/1128835' },
  { title: '6个出自《易经》的女孩名字，独特且美好', path: '/a/1347457' },
  { title: '《周易》经典取名赏析，周易起名', path: '/a/1458258' },
  { title: '出自易经小众名字，大气且吉利', path: '/a/1821259' },
  { title: '有哪些出自《易经》且有寓意的名字？', path: '/a/1126860' }
]

const onSearch = () => {
  if (searchKeyword.value) {
    router.push({ path: '/search', query: { q: searchKeyword.value } })
  }
}

const goBabyName = () => {
  if (!babyForm.surname) {
    message.warning('请输入姓氏')
    return
  }
  router.push({ path: '/baobao', query: { surname: babyForm.surname, gender: babyForm.gender } })
}

const goNameTest = () => {
  if (!testForm.name) {
    message.warning('请输入姓名')
    return
  }
  router.push({ path: '/xingmingceshi', query: { name: testForm.name, gender: testForm.gender } })
}
</script>

<style lang="scss" scoped>
.home-page {
  font-family: 'Microsoft YaHei', 'PingFang SC', sans-serif;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #fff;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

.header {
  background: #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  padding: 0 24px;

  .container {
    display: flex;
    align-items: center;
    height: 64px;
  }

  .logo {
    display: flex;
    align-items: center;
    text-decoration: none;
    color: #d4380d;
    font-weight: bold;
    font-size: 20px;
    margin-right: 48px;

    img {
      height: 40px;
      margin-right: 8px;
    }
  }

  .nav {
    display: flex;
    gap: 24px;
    flex: 1;

    a {
      color: #333;
      text-decoration: none;
      font-size: 15px;

      &:hover, &.router-link-active {
        color: #d4380d;
      }
    }
  }
}

.main {
  flex: 1;
}

.hero {
  background: linear-gradient(135deg, #d4380d 0%, #ff4d4f 100%);
  padding: 40px 0;
  text-align: center;
  color: #fff;

  h1 {
    font-size: 28px;
    margin-bottom: 24px;
    font-weight: 500;
  }

  .search-bar {
    max-width: 500px;
    margin: 0 auto 16px;

    :deep(.ant-input-search) {
      .ant-input-group {
        input {
          height: 40px;
        }
      }
    }
  }

  .hot-search {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.9);

    span {
      margin-right: 12px;
    }

    a {
      color: #fff;
      text-decoration: none;
      margin: 0 8px;
      text-decoration: underline;

      &:hover {
        text-decoration: none;
      }
    }
  }
}

.quick-tools {
  background: #fff;
  padding: 16px 0;
  border-bottom: 1px solid #f0f0f0;

  .container {
    display: flex;
    justify-content: center;
    gap: 32px;
  }

  .tool-btn {
    color: #333;
    text-decoration: none;
    font-size: 15px;

    &:hover {
      color: #d4380d;
    }
  }
}

.luban-section {
  padding: 48px 0;
  background: #fafafa;

  .container {
    display: flex;
    align-items: center;
    gap: 40px;
  }

  h3 {
    font-size: 20px;
    margin-bottom: 8px;
    color: #333;
  }

  p {
    color: #666;
    margin-bottom: 24px;
  }

  .form-row {
    display: flex;
    gap: 12px;
    align-items: center;

    :deep(.ant-input) {
      width: 120px;
    }

    :deep(.ant-radio-group) {
      margin-left: 8px;
    }
  }

  .luban-icons {
    display: none;
  }
}

.services-grid {
  padding: 32px 0;
  background: #fff;

  .container {
    display: flex;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
  }

  .service-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    padding: 16px;
    border-radius: 8px;
    transition: transform 0.2s;

    &:hover {
      transform: translateY(-4px);
    }

    img {
      width: 80px;
      height: 80px;
      margin-bottom: 12px;
    }

    span {
      font-size: 14px;
      color: #333;
    }
  }
}

.poetry-section {
  padding: 32px 0;
  background: #fafafa;

  h2 {
    text-align: center;
    font-size: 18px;
    margin-bottom: 24px;
    color: #333;
  }

  .container {
    display: flex;
    justify-content: center;
    gap: 24px;
  }

  .poetry-card {
    display: flex;
    align-items: center;
    text-decoration: none;
    padding: 12px 16px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);

    img {
      width: 24px;
      margin-right: 8px;
    }

    span {
      color: #333;
      font-size: 14px;
    }
  }
}

.hot-names-section {
  padding: 32px 0;
  background: #fff;

  h2 {
    text-align: center;
    font-size: 18px;
    margin-bottom: 16px;

    span {
      font-size: 12px;
      color: #999;
      font-weight: normal;
      margin-left: 12px;
    }
  }

  .hot-names-list {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 16px;

    a {
      color: #333;
      text-decoration: none;
      font-size: 14px;

      &:hover {
        color: #d4380d;
      }
    }
  }
}

.latest-articles {
  padding: 24px 0;
  background: #fafafa;

  h2 {
    font-size: 16px;
    color: #333;
    margin-bottom: 16px;
  }

  ul {
    list-style: none;
    padding: 0;
    margin: 0;

    li {
      display: inline-block;
      margin-right: 24px;
      margin-bottom: 8px;

      a {
        color: #666;
        font-size: 14px;
        text-decoration: none;

        &:hover {
          color: #d4380d;
        }
      }
    }
  }
}

.service-intro {
  padding: 32px 0;

  .intro-card {
    display: block;
    text-decoration: none;

    img {
      width: 100%;
      max-width: 600px;
      display: block;
      margin: 0 auto;
    }

    .intro-text {
      text-align: center;
      padding-top: 16px;

      h3 {
        font-size: 18px;
        color: #333;
        margin-bottom: 4px;
      }

      p {
        color: #666;
        font-size: 14px;
      }
    }
  }
}

.service-cards {
  padding: 32px 0;
  background: #fafafa;

  .container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
  }

  .service-card-item {
    background: #fff;
    padding: 24px;
    border-radius: 8px;
    text-align: center;

    h3 {
      font-size: 18px;
      color: #333;
      margin-bottom: 8px;
    }

    p {
      color: #666;
      font-size: 14px;
      margin-bottom: 16px;
    }

    .card-link {
      color: #d4380d;
      font-size: 14px;
      text-decoration: none;

      &:hover {
        text-decoration: underline;
      }
    }
  }
}

.ranks-section {
  padding: 32px 0;

  h2 {
    font-size: 18px;
    text-align: center;
    margin-bottom: 24px;
    color: #333;
  }

  .rank-list {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 16px;

    a {
      color: #333;
      text-decoration: none;
      font-size: 14px;

      &:hover {
        color: #d4380d;
      }
    }
  }

  :deep(.ant-card) {
    .ant-card-head {
      background: #fafafa;
    }
  }
}

.articles-section {
  padding: 32px 0;
  background: #fafafa;

  h3 {
    font-size: 16px;
    color: #333;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid #d4380d;
  }

  ul {
    list-style: none;
    padding: 0;
    margin: 0;

    li {
      margin-bottom: 12px;

      a {
        color: #666;
        font-size: 14px;
        text-decoration: none;

        &:hover {
          color: #d4380d;
        }
      }
    }
  }
}

.stats-section {
  padding: 48px 0;
  background: linear-gradient(135deg, #d4380d 0%, #ff4d4f 100%);
  color: #fff;

  .container {
    display: flex;
    justify-content: space-around;
  }

  .stat-item {
    text-align: center;

    strong {
      display: block;
      font-size: 28px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    span {
      font-size: 14px;
      opacity: 0.9;
    }
  }
}

.footer {
  background: #333;
  color: #fff;
  padding: 48px 0 24px;

  .footer-content {
    display: flex;
    justify-content: space-around;
    margin-bottom: 24px;
  }

  .footer-links {
    h4 {
      font-size: 16px;
      margin-bottom: 16px;
    }

    a {
      display: block;
      color: #ccc;
      font-size: 14px;
      text-decoration: none;
      margin-bottom: 8px;

      &:hover {
        color: #fff;
      }
    }
  }

  .footer-copyright {
    text-align: center;
    padding-top: 24px;
    border-top: 1px solid #444;
    font-size: 14px;
    color: #999;

    a {
      color: #999;
      text-decoration: none;
      margin: 0 8px;

      &:hover {
        color: #fff;
      }
    }
  }
}
</style>

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
            href="#header-search"
            @click.prevent
          >
            <i class="iconfont icon-search" />
          </a>
        </div>
      </div>
    </header>

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
                  <span
                    :class="{ active: form.gender === 0 }"
                    @click="form.gender = 0"
                  >未知</span>
                </div>
              </div>
              <div class="list_bd">
                <select
                  v-model="form.birthday"
                  class="native-select"
                >
                  <option
                    value=""
                    disabled
                    selected
                  >请选择出生日期</option>
                  <option
                    v-for="date in dateOptions"
                    :key="date"
                    :value="date"
                  >
                    {{ date }}
                  </option>
                </select>
                <img
                  src="/images/xl.png"
                  alt=""
                  class="select-arrow"
                >
              </div>
            </div>
            <div
              class="list_bd"
              style="width:100%;margin-right:0;margin-bottom:20px;"
            >
              <select
                v-model="form.birthAddress"
                class="native-select"
              >
                <option
                  value=""
                  disabled
                  selected
                >请选择出生地</option>
                <optgroup
                  v-for="region in regionOptions"
                  :key="region.value"
                  :label="region.label"
                >
                  <option
                    v-for="city in region.children"
                    :key="city.value"
                    :value="city.value"
                  >
                    {{ city.label }}
                  </option>
                </optgroup>
              </select>
              <img
                src="/images/xl.png"
                alt=""
                class="select-arrow"
              >
            </div>
            <button
              type="button"
              class="qmbtn"
              @click="handleSubmit"
            >
              立即起名
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="showLunpan"
        class="lunpan-overlay"
        @click="closeLunpan"
      >
        <div class="lunpan-container">
          <div class="lunpan">
            <img
              src="/images/luopan.png"
              alt=""
              class="luopan-img"
            >
            <img
              src="/images/zhizheng.png"
              alt=""
              class="zhizheng-img"
            >
          </div>
          <p class="lunpan-text">
            正在为您分析取名...
          </p>
        </div>
      </div>

      <div
        v-if="showResults"
        class="name-results"
      >
        <div class="results-header">
          <h3>{{ form.surname }}姓宝宝名字推荐</h3>
          <p>共为您推荐 {{ nameResults.length }} 个好名字</p>
        </div>
        <div class="results-list">
          <div
            v-for="(name, index) in nameResults"
            :key="index"
            class="result-item"
            @click="handleResultClick(name)"
          >
            <div class="result-name">
              <span class="full-name">{{ name.full_name }}</span>
              <span class="pinyin">{{ name.pinyin }}</span>
            </div>
            <div class="result-info">
              <span class="score">评分: {{ name.total_score }}分</span>
              <span class="wuxing">五行: {{ name.five_element }}</span>
              <span class="meaning">{{ name.meaning }}</span>
            </div>
          </div>
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
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { message } from 'ant-design-vue';

const router = useRouter();
const route = useRoute();

const form = reactive({
  surname: '',
  gender: 1,
  birthday: '',
  birthAddress: ''
});

const showLunpan = ref(false);
const showResults = ref(false);
const nameResults = ref([]);

const generateDateOptions = () => {
  const dates = [];
  const today = new Date();
  for (let i = 0; i < 3650; i++) {
    const date = new Date(today);
    date.setDate(date.getDate() + i);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    dates.push(`${year}-${month}-${day}`);
  }
  return dates;
};

const dateOptions = generateDateOptions();

const regionOptions = [
  {
    value: '华北',
    label: '华北',
    children: [
      { value: '北京', label: '北京' },
      { value: '天津', label: '天津' },
      { value: '河北', label: '河北' },
      { value: '山西', label: '山西' },
      { value: '内蒙古', label: '内蒙古' }
    ]
  },
  {
    value: '东北',
    label: '东北',
    children: [
      { value: '辽宁', label: '辽宁' },
      { value: '吉林', label: '吉林' },
      { value: '黑龙江', label: '黑龙江' }
    ]
  },
  {
    value: '华东',
    label: '华东',
    children: [
      { value: '上海', label: '上海' },
      { value: '江苏', label: '江苏' },
      { value: '浙江', label: '浙江' },
      { value: '安徽', label: '安徽' },
      { value: '福建', label: '福建' },
      { value: '江西', label: '江西' },
      { value: '山东', label: '山东' }
    ]
  },
  {
    value: '华中',
    label: '华中',
    children: [
      { value: '河南', label: '河南' },
      { value: '湖北', label: '湖北' },
      { value: '湖南', label: '湖南' }
    ]
  },
  {
    value: '华南',
    label: '华南',
    children: [
      { value: '广东', label: '广东' },
      { value: '广西', label: '广西' },
      { value: '海南', label: '海南' }
    ]
  },
  {
    value: '西南',
    label: '西南',
    children: [
      { value: '重庆', label: '重庆' },
      { value: '四川', label: '四川' },
      { value: '贵州', label: '贵州' },
      { value: '云南', label: '云南' },
      { value: '西藏', label: '西藏' }
    ]
  },
  {
    value: '西北',
    label: '西北',
    children: [
      { value: '陕西', label: '陕西' },
      { value: '甘肃', label: '甘肃' },
      { value: '青海', label: '青海' },
      { value: '宁夏', label: '宁夏' },
      { value: '新疆', label: '新疆' }
    ]
  },
  {
    value: '港澳台',
    label: '港澳台',
    children: [
      { value: '香港', label: '香港' },
      { value: '澳门', label: '澳门' },
      { value: '台湾', label: '台湾' }
    ]
  }
];

const mockNames = [
  { pinyin: 'jun hao', total_score: 95, five_element: '金', meaning: '才智超群，豪迈大气' },
  { pinyin: 'zi han', total_score: 92, five_element: '木', meaning: '生机勃勃，涵养深厚' },
  { pinyin: 'yu chen', total_score: 94, five_element: '火', meaning: '光明照耀，晨曦微露' },
  { pinyin: 'si yuan', total_score: 91, five_element: '土', meaning: '思虑周全，志向远大' },
  { pinyin: 'xin yi', total_score: 93, five_element: '金', meaning: '心情愉悦，幸福美好' },
  { pinyin: 'yu tong', total_score: 90, five_element: '水', meaning: '雨露滋润，梧桐栖凤' },
  { pinyin: 'shi han', total_score: 94, five_element: '水', meaning: '诗情画意，含蓄内秀' },
  { pinyin: 'si qi', total_score: 92, five_element: '木', meaning: '思维敏捷，杰出不凡' },
  { pinyin: 'bo wen', total_score: 93, five_element: '水', meaning: '博学多才，温文尔雅' },
  { pinyin: 'jia rui', total_score: 91, five_element: '木', meaning: '家业兴旺，祥瑞降临' },
  { pinyin: 'yu han', total_score: 94, five_element: '土', meaning: '玉树临风，寒梅傲雪' },
  { pinyin: 'zi xuan', total_score: 90, five_element: '木', meaning: '紫气东来，轩昂大气' }
];

const handleSubmit = () => {
  if (!form.surname) {
    message.warning('请输入姓氏');
    return;
  }
  
  showLunpan.value = true;
  showResults.value = false;
  
  setTimeout(() => {
    showLunpan.value = false;
    nameResults.value = mockNames.map(n => ({
      ...n,
      full_name: form.surname + mockNames[Math.floor(Math.random() * 7)].pinyin.split(' ')[0]
    }));
    
    const boyNames = ['俊豪', '梓涵', '煜晨', '思远', '欣怡', '雨桐', '诗涵', '思琪', '博文', '家瑞', '宇涵', '子轩'];
    const girlNames = ['欣怡', '梓涵', '雨桐', '诗涵', '思琪', '雅婷', '欣悦', '梦瑶', '佳怡', '雪丽'];
    const names = form.gender === 1 ? boyNames : (form.gender === 2 ? girlNames : [...boyNames, ...girlNames]);
    
    nameResults.value = names.slice(0, 8).map((name, i) => ({
      ...mockNames[i],
      full_name: form.surname + name
    }));
    
    showResults.value = true;
    showLunpan.value = false;
  }, 2000);
};

const closeLunpan = () => {
  showLunpan.value = false;
};

const handleResultClick = (name) => {
  router.push({ path: '/xingmingceshi', query: { name: name.full_name } });
};

const scrollTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(() => {
  if (route.query.surname) {
    form.surname = route.query.surname;
    form.gender = parseInt(route.query.gender) || 1;
    handleSubmit();
  }
});
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

.native-select {
  width: 100%;
  height: 46px;
  border: none;
  background: transparent;
  font-size: 14px;
  color: #696969;
  padding-right: 30px;
  appearance: none;
  -webkit-appearance: none;
  cursor: pointer;
  outline: none;
}

.select-arrow {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
}

.lunpan-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.lunpan-container {
  text-align: center;
}

.lunpan {
  width: 200px;
  height: 200px;
  position: relative;
  margin: 0 auto;
}

.luopan-img {
  width: 200px;
  height: 200px;
  animation: rotate 4s linear infinite;
}

.zhizheng-img {
  width: 20px;
  height: 160px;
  position: absolute;
  top: 20px;
  left: 50%;
  margin-left: -10px;
  animation: rotate2 4s linear infinite;
}

@keyframes rotate {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@keyframes rotate2 {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(-360deg); }
}

.lunpan-text {
  color: #fff;
  font-size: 18px;
  margin-top: 30px;
}

.name-results {
  background: #fff;
  border-radius: 8px;
  padding: 30px;
  margin-top: 20px;
}

.results-header {
  text-align: center;
  margin-bottom: 30px;
}

.results-header h3 {
  font-size: 24px;
  color: #333;
  margin-bottom: 10px;
}

.results-header p {
  font-size: 14px;
  color: #999;
}

.results-list {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 15px;
}

@media (max-width: 1200px) {
  .results-list {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .results-list {
    grid-template-columns: repeat(2, 1fr);
  }
}

.result-item {
  background: #f9f9f9;
  border-radius: 8px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.3s;
}

.result-item:hover {
  background: #a93121;
  color: #fff;
  transform: translateY(-3px);
  box-shadow: 0 4px 12px rgba(169, 49, 33, 0.3);
}

.result-item:hover .result-info {
  color: #fff;
}

.result-name {
  text-align: center;
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 1px solid #eee;
}

.result-item:hover .result-name {
  border-bottom-color: rgba(255,255,255,0.3);
}

.full-name {
  display: block;
  font-size: 24px;
  font-weight: bold;
  color: #a93121;
  margin-bottom: 5px;
}

.result-item:hover .full-name {
  color: #fff;
}

.pinyin {
  font-size: 12px;
  color: #999;
}

.result-item:hover .pinyin {
  color: rgba(255,255,255,0.8);
}

.result-info {
  font-size: 12px;
  color: #666;
}

.result-info span {
  display: block;
  margin-bottom: 5px;
}

.score {
  color: #a93121;
  font-weight: bold;
}

.result-item:hover .score {
  color: #fff;
}

.wapnone {
  @media (max-width: 767px) {
    display: none;
  }
}

.iconfont {
  font-family: "iconfont" !important;
  font-size: 16px;
  font-style: normal;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.qiming_follow_service {
  position: fixed;
  top: 75%;
  right: 20px;
  z-index: 100;
}

.qiming_follow_service_box {
  list-style: none;
}

.qiming_follow_service_ax {
  display: block;
  width: 50px;
  height: 50px;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 50%;
  text-align: center;
  line-height: 50px;
  color: #fff;
  cursor: pointer;
}

.qiming_follow_service_ax:hover {
  background: rgba(169, 49, 33, 0.8);
}

.qiming_follow_service_ax i {
  font-size: 24px;
}

.qiming_follow_service_ax_cont {
  display: none;
}

.qiming_follow_service_triangle {
  display: none;
}
</style>

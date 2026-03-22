<template>
  <div class="home-page">
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
            <li class="current-menu-item">
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
                  >
                    请选择出生日期
                  </option>
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
                >
                  请选择出生地
                </option>
                <option
                  v-for="region in flatRegions"
                  :key="region.value"
                  :value="region.value"
                >
                  {{ region.label }}
                </option>
              </select>
              <img
                src="/images/xl.png"
                alt=""
                class="select-arrow"
              >
            </div>
            <a
              href="javascript:void(0);"
              class="qmbtn"
              @click="handleSubmit"
            >
              立即起名
            </a>
          </div>
        </div>
      </div>

      <div class="qiming-container">
        <div class="main-content-grid">
          <div class="main-left">
            <div class="search-hot">
              <div class="search-box">
                <input
                  v-model="searchKeyword"
                  type="text"
                  placeholder="立即搜索"
                  @keyup.enter="handleSearch"
                >
                <i class="iconfont icon-search" />
              </div>
              <div class="hot-words">
                <span class="hot-title">搜索最多的：</span>
                <router-link
                  v-for="word in hotWords"
                  :key="word"
                  :to="`/kxzd/${getCharCode(word)}`"
                  class="hot-item"
                >
                  {{ word }}
                </router-link>
              </div>
            </div>

            <div class="service-entry">
              <ul class="service-list">
                <li>
                  <router-link to="/baobao">
                    <img
                      src="/images/xbbqm.jpg"
                      alt="宝宝起名"
                    >
                    <span>宝宝起名</span>
                  </router-link>
                </li>
                <li>
                  <router-link to="/bazi">
                    <img
                      src="/images/xbzqm.jpg"
                      alt="八字起名"
                    >
                    <span>八字起名</span>
                  </router-link>
                </li>
                <li>
                  <router-link to="/shici">
                    <img
                      src="/images/xscqm.jpg"
                      alt="诗词起名"
                    >
                    <span>诗词起名</span>
                  </router-link>
                </li>
                <li>
                  <router-link to="/gaimingzi">
                    <img
                      src="/images/xcrgm.jpg"
                      alt="成人改名"
                    >
                    <span>成人改名</span>
                  </router-link>
                </li>
                <li>
                  <router-link to="/xingmingceshi">
                    <img
                      src="/images/xxmcs.jpg"
                      alt="姓名测试"
                    >
                    <span>姓名测试</span>
                  </router-link>
                </li>
                <li>
                  <router-link to="/gongsiqiming">
                    <img
                      src="/images/xgsqm.jpg"
                      alt="公司起名"
                    >
                    <span>公司起名</span>
                  </router-link>
                </li>
                <li>
                  <router-link to="/zhishi">
                    <img
                      src="/images/xqmzs.jpg"
                      alt="起名知识"
                    >
                    <span>起名知识</span>
                  </router-link>
                </li>
                <li>
                  <router-link to="/kxzd">
                    <img
                      src="/images/xkxzd.jpg"
                      alt="康熙字典"
                    >
                    <span>康熙字典</span>
                  </router-link>
                </li>
              </ul>
            </div>

            <div class="banner-images">
              <router-link to="/baobao">
                <img
                  src="/images/nanhaiqiming.jpg"
                  alt="南海起名"
                >
              </router-link>
              <router-link to="/nvhai">
                <img
                  src="/images/nvhaiqiming.jpg"
                  alt="女孩起名"
                >
              </router-link>
              <router-link to="/xingmingpeidui">
                <img
                  src="/images/xingmingpeidui.jpg"
                  alt="姓名配对"
                >
              </router-link>
              <router-link to="/xingmingceshi">
                <img
                  src="/images/xingmingceshi.jpg"
                  alt="姓名测试"
                >
              </router-link>
              <router-link to="/dingzi">
                <img
                  src="/images/dingziqiming.jpg"
                  alt="订子起名"
                >
              </router-link>
              <router-link to="/gaimingzi">
                <img
                  src="/images/chengrengaiming.jpg"
                  alt="成人改名"
                >
              </router-link>
            </div>

            <div class="poetry-section">
              <h3>唐诗宋词传承千年的诗意起名艺术</h3>
              <div class="poetry-list">
                <router-link
                  to="/tangshi"
                  class="poetry-item"
                >
                  <img
                    src="/images/tang.png"
                    alt="唐诗起名"
                  >
                  <span>唐诗起名</span>
                </router-link>
                <router-link
                  to="/shijing"
                  class="poetry-item"
                >
                  <img
                    src="/images/shi.png"
                    alt="诗经起名"
                  >
                  <span>诗经起名</span>
                </router-link>
                <router-link
                  to="/songci"
                  class="poetry-item"
                >
                  <img
                    src="/images/song.png"
                    alt="宋词起名"
                  >
                  <span>宋词起名</span>
                </router-link>
                <router-link
                  to="/chuci"
                  class="poetry-item"
                >
                  <img
                    src="/images/ci.png"
                    alt="楚辞起名"
                  >
                  <span>楚辞起名</span>
                </router-link>
              </div>
            </div>

            <div class="names-checking">
              <h3>别人正在查的姓名</h3>
              <p class="subtitle">
                专业的国学起名网站
              </p>
              <div class="names-list">
                <router-link
                  v-for="name in checkingNames"
                  :key="name.id"
                  :to="`/xingming/${name.id}`"
                  class="name-item"
                >
                  {{ name.name }}
                </router-link>
              </div>
            </div>

            <div class="latest-names">
              <h3>最新更新起名</h3>
              <ul class="article-list">
                <li
                  v-for="article in latestArticles"
                  :key="article.id"
                >
                  <router-link :to="`/a/${article.id}`">
                    {{ article.title }}
                  </router-link>
                </li>
              </ul>
            </div>
          </div>

          <div class="main-right">
            <div class="almanac-card">
              <div class="almanac-header">
                <h4>今日黄历</h4>
                <span class="date">{{ almanac.date }}</span>
              </div>
              <div class="almanac-info">
                <p class="lunar-date">
                  {{ almanac.lunarYear }} 【{{ almanac.zodiac }}】<br>
                  {{ almanac.lunarMonth }}·{{ almanac.lunarDay }}
                </p>
                <p class="solar-info">
                  <span class="constellation">{{ almanac.constellation }}</span>
                </p>
              </div>
              <div class="almanac-day">
                <div class="day-number">
                  {{ almanac.dayNumber }}
                </div>
                <div class="solar-term-info">
                  <p>{{ almanac.solarTerm }} (第{{ almanac.solarTermDay }}天)</p>
                  <p class="next-term">
                    距离下一节气{{ almanac.nextSolarTerm }}还有{{ almanac.nextSolarTermDays }}天
                  </p>
                </div>
              </div>
              <div class="almanac-detail">
                <div class="detail-row">
                  <span class="label">宜</span>
                  <div class="values">
                    <span
                      v-for="item in almanac.yi"
                      :key="item"
                      class="yi-item"
                    >{{ item }}</span>
                  </div>
                </div>
                <div class="detail-row">
                  <span class="label ji">忌</span>
                  <div class="values">
                    <span
                      v-for="item in almanac.ji"
                      :key="item"
                      class="ji-item"
                    >{{ item }}</span>
                  </div>
                </div>
              </div>
              <div class="almanac-footer">
                <div class="info-item">
                  <span class="label">冲煞</span>
                  <span class="value">{{ almanac.chongSha }} {{ almanac.chongZodiac }}</span>
                </div>
                <div class="info-item">
                  <span class="label">今日吉时</span>
                  <span class="value">{{ almanac.luckyHours.join('') }}</span>
                </div>
                <div class="gods-row">
                  <div class="god-item">
                    <span class="god-label">财神</span>
                    <span class="god-value">{{ almanac.caiShen }}</span>
                  </div>
                  <div class="god-item">
                    <span class="god-label">喜神</span>
                    <span class="god-value">{{ almanac.xiShen }}</span>
                  </div>
                  <div class="god-item">
                    <span class="god-label">福神</span>
                    <span class="god-value">{{ almanac.fuShen }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="baobao-banner">
        <router-link to="/baobao">
          <img
            src="/images/baobao.jpg"
            alt="宝宝起名"
          >
          <div class="baobao-text">
            <h3>宝宝起名</h3>
            <p>时尚 高雅 大气 吉祥</p>
          </div>
        </router-link>
      </div>

      <div class="service-cards">
        <div class="service-card">
          <img
            src="/images/bzqmBanner.jpg"
            alt="八字起名"
          >
          <div class="service-card-content">
            <h3>八字起名</h3>
            <p>汇聚多位国内权威易学大师，以深厚经验精准解析八字，结合心理学，量身打造帮扶一生的优质好名</p>
            <router-link
              to="/bazi"
              class="service-btn"
            >
              立即八字起名
            </router-link>
          </div>
        </div>
        <div class="service-card">
          <img
            src="/images/gsqmBanner.jpg"
            alt="公司起名"
          >
          <div class="service-card-content">
            <h3>公司起名</h3>
            <p>资深命名专家与品牌策划大师联手打造，为企业量身定制独特专属好名，助力企业成为行业龙头</p>
            <router-link
              to="/gongsiqiming"
              class="service-btn"
            >
              立即公司起名
            </router-link>
          </div>
        </div>
        <div class="service-card">
          <img
            src="/images/scqmBanner.jpg"
            alt="诗词起名"
          >
          <div class="service-card-content">
            <h3>诗词起名</h3>
            <p>结合孩子出生信息和父母期盼，从二十多万诗词古文中取字，确保每个名字意蕴优美、 诗情画意。</p>
            <router-link
              to="/shici"
              class="service-btn"
            >
              立即诗词起名
            </router-link>
          </div>
        </div>
        <div class="service-card">
          <img
            src="/images/xmcsBanner.jpg"
            alt="周易起名"
          >
          <div class="service-card-content">
            <h3>周易起名</h3>
            <p>汲取千年国学智慧，融汇《周易》精髓，结合现代科学理念，为您提供文化深厚、寓意吉祥的好名字</p>
            <router-link
              to="/zhouyi"
              class="service-btn"
            >
              立即周易起名
            </router-link>
          </div>
        </div>
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

      <div class="name-ranks-section">
        <div class="ranks-grid">
          <div class="rank-card boy-zi">
            <h3 class="rank-title">
              男孩起名用字排行
            </h3>
            <p class="rank-subtitle">
              2026年3月男孩起名用字排行
            </p>
            <ul class="rank-list">
              <li
                v-for="(item, index) in boyZi"
                :key="index"
              >
                <router-link :to="`/kxzd/${item.id}`">
                  {{ item.pinyin }} {{ item.name }}
                </router-link>
              </li>
            </ul>
            <div class="wuxing-filter">
              <span class="filter-title">五行分类</span>
              <router-link
                v-for="item in wuxingOptions"
                :key="item.value"
                :to="`/kxzd/wuxing/${item.value}`"
                class="wuxing-btn"
              >
                {{ item.label }}
              </router-link>
            </div>
          </div>

          <div class="rank-card girl-zi">
            <h3 class="rank-title">
              女孩起名用字排行
            </h3>
            <p class="rank-subtitle">
              2026年3月女孩起名用字排行
            </p>
            <ul class="rank-list">
              <li
                v-for="(item, index) in girlZi"
                :key="index"
              >
                <router-link :to="`/kxzd/${item.id}`">
                  {{ item.pinyin }} {{ item.name }}
                </router-link>
              </li>
            </ul>
            <div class="wuxing-filter">
              <span class="filter-title">五行分类</span>
              <router-link
                v-for="item in wuxingOptionsGirl"
                :key="item.value"
                :to="`/kxzd/wuxing/${item.value}`"
                class="wuxing-btn"
              >
                {{ item.label }}
              </router-link>
            </div>
          </div>

          <div class="rank-card boy-names">
            <h3 class="rank-title">
              男孩热门名字排行
            </h3>
            <p class="rank-subtitle">
              2026年3月男孩名字排行
            </p>
            <ul class="rank-list name-rank-list">
              <li
                v-for="(name, index) in boyNames"
                :key="index"
              >
                <router-link :to="`/mingzi/${name.id}`">
                  {{ name.name }}
                </router-link>
              </li>
            </ul>
            <div class="surname-filter">
              <span class="filter-title">百家姓男孩起名</span>
              <router-link
                v-for="surname in surnames"
                :key="surname.name"
                :to="`/nanhai/xing/${surname.id}`"
                class="surname-btn"
              >
                {{ surname.name }}
              </router-link>
            </div>
          </div>

          <div class="rank-card girl-names">
            <h3 class="rank-title">
              女孩热门名字排行
            </h3>
            <p class="rank-subtitle">
              2026年3月女孩名字排行
            </p>
            <ul class="rank-list name-rank-list">
              <li
                v-for="(name, index) in girlNames"
                :key="index"
              >
                <router-link :to="`/mingzi/${name.id}`">
                  {{ name.name }}
                </router-link>
              </li>
            </ul>
            <div class="surname-filter">
              <span class="filter-title">百家姓女孩起名</span>
              <router-link
                v-for="surname in surnames"
                :key="surname.name + '-g'"
                :to="`/nvhai/xing/${surname.id}`"
                class="surname-btn"
              >
                {{ surname.name }}
              </router-link>
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
                <router-link to="/baobao">查看更多<i class="iconfont icon-arrow-right-bold" /></router-link>
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
                  <router-link
                    to="/a/1591321"
                    class="cover b-r-4 qiming-display-block qiming-overflow-hidden"
                  >
                    <img
                      src="https://static.qiming.cn/upload/upimg/0919/1758256277595861.jpg"
                      alt="宝宝起名别跟风"
                    >
                  </router-link>
                </div>
                <div class="qiming-cat-blog blog-item-content">
                  <div class="qiming-blog-items">
                    <h3 class="qiming-text-truncate">
                      <router-link
                        to="/a/1591321"
                        class="title qiming-h4"
                      >
                        宝宝起名别跟风！8个独特技巧，让名字跳出"重名魔咒"
                      </router-link>
                    </h3>
                    <p class="qiming-text-small">
                      大家有没有发现，每次去医院或者学校，总能碰到几个孩子叫子涵、梓涵、紫轩、宇轩这样的名字？
                    </p>
                  </div>
                  <div class="item-foot">
                    <div class="cat qiming-margin-bottom-10 qiming-margin-top-10 qiming-text-truncate">
                      <router-link to="/baobao">
                        <i class="iconfont icon-menu qiming-right-3 arttag" />宝宝起名
                      </router-link>
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
                      <div class="qiming-text-small qiming-text-right">
                        <span class="qiming-display-inline-block qiming-flex qiming-flex-middle qiming-margin-right-6">
                          <i class="iconfont icon-time" />09-19
                        </span>
                        <span class="qiming-display-inline-block qiming-flex qiming-flex-middle">
                          <i class="iconfont icon-browse" />243
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="blog-item b-r-4 qiming-padding-small qiming-background-default qiming-overflow-hidden">
              <div class="qiming-grid-collapse">
                <div class="qiming-vip-icons">
                  <router-link
                    to="/a/1748420"
                    class="cover b-r-4 qiming-display-block qiming-overflow-hidden"
                  >
                    <img
                      src="https://static.qiming.cn/upload/upimg/0919/1758244202109391.jpg"
                      alt="宝宝起名新解"
                    >
                  </router-link>
                </div>
                <div class="qiming-cat-blog blog-item-content">
                  <div class="qiming-blog-items">
                    <h3 class="qiming-text-truncate">
                      <router-link
                        to="/a/1748420"
                        class="title qiming-h4"
                      >
                        宝宝起名新解,一个好名字可能给宝宝的将来带来良好的影响
                      </router-link>
                    </h3>
                    <p class="qiming-text-small">
                      一个好名字可能给宝宝的将来带来良好的影响，甚至有可能会受益一生，所以我们在给宝宝起名字时一定要认真，仔细，讲究
                    </p>
                  </div>
                  <div class="item-foot">
                    <div class="cat qiming-margin-bottom-10 qiming-margin-top-10 qiming-text-truncate">
                      <router-link to="/baobao">
                        <i class="iconfont icon-menu qiming-right-3 arttag" />宝宝起名
                      </router-link>
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
                      <div class="qiming-text-small qiming-text-right">
                        <span class="qiming-display-inline-block qiming-flex qiming-flex-middle qiming-margin-right-6">
                          <i class="iconfont icon-time" />09-19
                        </span>
                        <span class="qiming-display-inline-block qiming-flex qiming-flex-middle">
                          <i class="iconfont icon-browse" />204
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="stats-section">
        <div class="container">
          <div class="stats-grid">
            <div class="stat-item">
              <div class="stat-number">
                5000万+
              </div>
              <div class="stat-label">
                访问总数
              </div>
            </div>
            <div class="stat-item">
              <div class="stat-number">
                2000万+
              </div>
              <div class="stat-label">
                名字库收录
              </div>
            </div>
            <div class="stat-item">
              <div class="stat-number">
                560万+
              </div>
              <div class="stat-label">
                起名知识
              </div>
            </div>
            <div class="stat-item">
              <div class="stat-number">
                80万+
              </div>
              <div class="stat-label">
                起名客户
              </div>
            </div>
            <div class="stat-item">
              <div class="stat-number">
                98%+
              </div>
              <div class="stat-label">
                用户满意度
              </div>
            </div>
            <div class="stat-item">
              <div class="stat-number">
                20年+
              </div>
              <div class="stat-label">
                运行时间
              </div>
            </div>
          </div>
          <p class="stats-desc">
            已服务数据，客户的信任是我们成长的动力
          </p>
        </div>
      </div>
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

    <div
      v-if="showLunpan"
      class="lunpan_box"
    >
      <div class="lunpan">
        <img
          src="/images/luopan.png"
          alt=""
        >
        <img
          src="/images/zhizheng.png"
          alt=""
        >
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { message } from 'ant-design-vue';
import request, { USE_MOCK } from '@/utils/request';

const router = useRouter();
const showSearch = ref(false);
const showLunpan = ref(false);
const searchKeyword = ref('');

const form = reactive({
  surname: '',
  gender: 1,
  birthday: '',
  birthAddress: ''
});

const generateDateOptions = () => {
  const dates = [];
  const today = new Date();
  for (let i = 0; i < 365; i++) {
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
  { value: '华北', label: '华北', children: [
    { value: '北京', label: '北京' }, { value: '天津', label: '天津' }, { value: '河北', label: '河北' },
    { value: '山西', label: '山西' }, { value: '内蒙古', label: '内蒙古' }
  ]},
  { value: '东北', label: '东北', children: [
    { value: '辽宁', label: '辽宁' }, { value: '吉林', label: '吉林' }, { value: '黑龙江', label: '黑龙江' }
  ]},
  { value: '华东', label: '华东', children: [
    { value: '上海', label: '上海' }, { value: '江苏', label: '江苏' }, { value: '浙江', label: '浙江' },
    { value: '安徽', label: '安徽' }, { value: '福建', label: '福建' }, { value: '江西', label: '江西' }, { value: '山东', label: '山东' }
  ]},
  { value: '华中', label: '华中', children: [
    { value: '河南', label: '河南' }, { value: '湖北', label: '湖北' }, { value: '湖南', label: '湖南' }
  ]},
  { value: '华南', label: '华南', children: [
    { value: '广东', label: '广东' }, { value: '广西', label: '广西' }, { value: '海南', label: '海南' }
  ]},
  { value: '西南', label: '西南', children: [
    { value: '重庆', label: '重庆' }, { value: '四川', label: '四川' }, { value: '贵州', label: '贵州' },
    { value: '云南', label: '云南' }, { value: '西藏', label: '西藏' }
  ]},
  { value: '西北', label: '西北', children: [
    { value: '陕西', label: '陕西' }, { value: '甘肃', label: '甘肃' }, { value: '青海', label: '青海' },
    { value: '宁夏', label: '宁夏' }, { value: '新疆', label: '新疆' }
  ]},
  { value: '港澳台', label: '港澳台', children: [
    { value: '香港', label: '香港' }, { value: '澳门', label: '澳门' }, { value: '台湾', label: '台湾' }
  ]}
];

const flatRegions = regionOptions.flatMap(region => 
  region.children.map(city => ({ value: city.value, label: city.label }))
);

const hotWords = ref(['雪', '旭', '信川', '煜晨']);

const checkingNames = ref([
  { id: 663883192, name: '雷洛初' },
  { id: 1371202950, name: '逯鑫和' },
  { id: 5896373931, name: '吕春航' },
  { id: 3896379537, name: '唐传茗' },
  { id: 6324488417, name: '邱维夏' },
  { id: 3936549642, name: '杨圣宇' },
  { id: 9369623612, name: '马子沫' },
  { id: 7895717636, name: '谢媛艾' },
  { id: 5166879067, name: '苏小龙' },
  { id: 82741108598, name: '任子泓' },
  { id: 55851193449, name: '窦姝妍' },
  { id: 80711240817, name: '杜茗崇' }
]);

const latestArticles = ref([
  { id: 1126860, title: '有哪些出自《易经》且有寓意的名字？' },
  { id: 1821259, title: '出自易经小众名字，大气且吉利' },
  { id: 1458258, title: '《周易》经典取名赏析，周易起名' }
]);

const almanac = reactive({
  date: '2026年3月22日 星期日',
  lunarYear: '丙午年',
  zodiac: '马',
  lunarMonth: '三月',
  lunarDay: '初四',
  dayNumber: '22',
  constellation: '白羊座',
  solarTerm: '春分',
  solarTermDay: '3',
  nextSolarTerm: '清明',
  nextSolarTermDays: '14',
  yi: ['嫁娶', '祭祀', '开光', '祈福', '求嗣', '出行'],
  ji: ['动土', '伐木', '安葬', '行丧'],
  chongSha: '己丑',
  chongZodiac: '牛',
  luckyHours: ['子', '寅', '卯', '巳', '申', '亥'],
  caiShen: '东北',
  xiShen: '西北',
  fuShen: '西南'
});

const boyZi = ref([
  { id: 67917, pinyin: 'shèng,kū', name: '圣' },
  { id: 54078, pinyin: 'jié', name: '杰' },
  { id: 414411, pinyin: 'hào', name: '浩' },
  { id: 892215, pinyin: 'xù', name: '旭' },
  { id: 580716, pinyin: 'yáo', name: '尧' },
  { id: 920617, pinyin: 'jùn', name: '俊' },
  { id: 603319, pinyin: 'tiān', name: '天' },
  { id: 288220, pinyin: 'lěi', name: '磊' },
  { id: 833922, pinyin: 'wěi', name: '伟' },
  { id: 305723, pinyin: 'shì', name: '世' },
  { id: 972324, pinyin: 'bó', name: '博' },
  { id: 228126, pinyin: 'zhé', name: '哲' },
  { id: 140228, pinyin: 'guān,guàn', name: '冠' },
  { id: 407630, pinyin: 'huī', name: '辉' },
  { id: 111431, pinyin: 'jīn', name: '金' },
  { id: 473832, pinyin: 'ào', name: '傲' },
  { id: 373433, pinyin: 'yuè', name: '越' },
  { id: 872735, pinyin: 'lín', name: '霖' },
  { id: 487736, pinyin: 'péng', name: '朋' },
  { id: 689437, pinyin: 'jiàn', name: '健' }
]);

const girlZi = ref([
  { id: 82815, pinyin: 'jǐn', name: '瑾' },
  { id: 214818, pinyin: 'nán', name: '楠' },
  { id: 669440, pinyin: 'yíng', name: '莹' },
  { id: 998141, pinyin: 'xuě', name: '雪' },
  { id: 942543, pinyin: 'hán', name: '晗' },
  { id: 590945, pinyin: 'qín', name: '琴' },
  { id: 785846, pinyin: 'qíng', name: '晴' },
  { id: 786447, pinyin: 'lì,lí', name: '丽' },
  { id: 185249, pinyin: 'yáo', name: '瑶' },
  { id: 485051, pinyin: 'qiàn,xī', name: '茜' },
  { id: 264755, pinyin: 'méi', name: '梅' },
  { id: 732265, pinyin: 'tíng', name: '婷' },
  { id: 197566, pinyin: 'fēi,fěi', name: '菲' },
  { id: 259867, pinyin: 'xuān', name: '萱' },
  { id: 995069, pinyin: 'wēi', name: '薇' },
  { id: 220772, pinyin: 'fāng', name: '芳' },
  { id: 963073, pinyin: 'fēn', name: '芬' },
  { id: 659074, pinyin: 'huì', name: '慧' },
  { id: 7801148, pinyin: 'yè,xié', name: '叶' },
  { id: 1130199, pinyin: 'yāng', name: '鸯' }
]);

const boyNames = ref([
  { id: 47683, name: '颜豪' },
  { id: 764612, name: '颢凯' },
  { id: 830513, name: '颢哲' },
  { id: 447016, name: '颢宁' },
  { id: 644717, name: '颢宸' },
  { id: 334728, name: '璟桓' },
  { id: 226334, name: '璟霆' },
  { id: 164864, name: '翔士' },
  { id: 2205122, name: '名浩' },
  { id: 7354131, name: '淳皓' },
  { id: 2297135, name: '家岐' },
  { id: 9903143, name: '毅铮' },
  { id: 6683184, name: '振彦' },
  { id: 4509198, name: '书豪' },
  { id: 4276223, name: '庭佑' },
  { id: 4099255, name: '昊然' },
  { id: 2210261, name: '旭尧' },
  { id: 9519271, name: '铭灏' },
  { id: 3241276, name: '俊灏' },
  { id: 7850298, name: '俊鹏' }
]);

const girlNames = ref([
  { id: 11662, name: '颜菲' },
  { id: 986143, name: '孟馨' },
  { id: 335746, name: '宁丽' },
  { id: 632947, name: '宁俪' },
  { id: 820349, name: '宁娟' },
  { id: 344850, name: '宁婕' },
  { id: 100151, name: '宁汐' },
  { id: 673253, name: '宁萱' },
  { id: 968756, name: '宇妃' },
  { id: 442557, name: '宇妍' },
  { id: 627058, name: '宇妮' },
  { id: 921659, name: '宇娇' },
  { id: 299760, name: '宇婷' },
  { id: 844461, name: '宇晴' },
  { id: 434375, name: '辉芳' },
  { id: 887793, name: '玥西' },
  { id: 455398, name: '颂伊' },
  { id: 790299, name: '煜菲' },
  { id: 1301100, name: '亦伊' },
  { id: 8650108, name: '梓怡' }
]);

const wuxingOptions = [
  { label: '金', value: 1 },
  { label: '木', value: 2 },
  { label: '水', value: 3 },
  { label: '火', value: 4 },
  { label: '土', value: 5 }
];

const wuxingOptionsGirl = [
  { label: '金', value: 6 },
  { label: '木', value: 7 },
  { label: '水', value: 8 },
  { label: '火', value: 9 },
  { label: '土', value: 10 }
];

const surnames = ref([
  { id: 79711, name: '王' },
  { id: 69822, name: '李' },
  { id: 12573, name: '张' },
  { id: 98404, name: '刘' },
  { id: 81405, name: '陈' },
  { id: 38766, name: '杨' },
  { id: 71237, name: '黄' },
  { id: 97988, name: '赵' },
  { id: 22471, name: '吴' },
  { id: 106010, name: '周' }
]);

const handleSubmit = () => {
  if (!form.surname) {
    message.warning('请输入姓氏');
    return;
  }
  router.push({ path: '/baobao', query: { surname: form.surname, gender: form.gender } });
};

const handleSearch = () => {
  if (!searchKeyword.value.trim()) {
    message.warning('请输入搜索关键词');
    return;
  }
  showSearch.value = false;
  router.push({ path: '/search', query: { keyword: searchKeyword.value } });
};

const scrollTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const getCharCode = (char) => {
  return char.charCodeAt(0);
};

const fetchAlmanac = async () => {
  if (USE_MOCK) {
    const res = await request.mockGet('/v1/almanac/today');
    if (res.data) {
      Object.assign(almanac, {
        date: `${res.data.date} ${res.data.weekday}`,
        lunarYear: res.data.lunarYear,
        zodiac: res.data.zodiac,
        lunarMonth: res.data.lunarMonth,
        lunarDay: res.data.lunarDay,
        constellation: res.data.constellation,
        yi: res.data.yi,
        ji: res.data.ji,
        chongSha: res.data.chongSha.split('')[0],
        chongZodiac: res.data.chongZodiac,
        luckyHours: res.data.luckyHours,
        caiShen: res.data.caiShen,
        xiShen: res.data.xiShen,
        fuShen: res.data.fuShen
      });
    }
  }
};

onMounted(() => {
  fetchAlmanac();
});
</script>

<style lang="scss">
@import '@/assets/styles/qiming.scss';

.home-page {
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
  z-index: 0;
}

.b-r-4 {
  border-radius: 4px;
}

.qiming-padding-small {
  padding: 15px;
}

.qiming-overflow-hidden {
  overflow: hidden;
}

.qiming-display-block {
  display: block;
}

.qiming-display-inline-block {
  display: inline-block;
}

.qiming-flex {
  display: flex;
}

.qiming-flex-middle {
  align-items: center;
}

.qiming-flex-1 {
  flex: 1;
}

.qiming-margin-small-left {
  margin-left: 8px;
}

.qiming-margin-right-6 {
  margin-right: 6px;
}

.qiming-right-3 {
  margin-right: 3px;
}

.qiming-text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.qiming-margin-bottom-10 {
  margin-bottom: 10px;
}

.qiming-margin-top-10 {
  margin-top: 10px;
}

.qiming-text-small {
  font-size: 13px;
}

.qiming-text-right {
  text-align: right;
}

.qiming-visible\@s {
  @media (min-width: 768px) {
    display: block;
  }
  @media (max-width: 767px) {
    display: none;
  }
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

.arstag {
  background: #af4031;
  color: #fff;
  padding: 3px 6px;
  border-radius: 2px;
  font-size: 12px;
}

.qiming-h4 {
  font-size: 18px;
  font-weight: normal;
}

.main-content-grid {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 20px;
  margin-top: 20px;
}

.main-left {
  min-width: 0;
}

.search-hot {
  background: #fff;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.search-box {
  position: relative;
  margin-bottom: 10px;
}

.search-box input {
  width: 100%;
  height: 40px;
  border: 1px solid #edd5d2;
  border-radius: 20px;
  padding: 0 40px 0 20px;
  font-size: 14px;
}

.search-box i {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #999;
}

.hot-words {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.hot-title {
  font-size: 13px;
  color: #999;
}

.hot-item {
  font-size: 13px;
  color: #a93121;
  padding: 2px 8px;
  background: #fdf5f4;
  border-radius: 3px;
}

.service-entry {
  background: #fff;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.service-list {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 15px;
  list-style: none;
  padding: 0;
  margin: 0;
}

.service-list li a {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  color: #333;
}

.service-list li img {
  width: 60px;
  height: 60px;
  border-radius: 8px;
  margin-bottom: 8px;
}

.service-list li span {
  font-size: 13px;
  color: #333;
}

.service-list li:hover span {
  color: #a93121;
}

.banner-images {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 20px;
}

.banner-images a {
  display: block;
  border-radius: 8px;
  overflow: hidden;
}

.banner-images img {
  width: 100%;
  height: auto;
  display: block;
}

.poetry-section {
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.poetry-section h3 {
  font-size: 16px;
  color: #333;
  margin-bottom: 15px;
}

.poetry-list {
  display: flex;
  justify-content: space-around;
}

.poetry-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  color: #333;
}

.poetry-item img {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  margin-bottom: 8px;
}

.poetry-item span {
  font-size: 13px;
}

.names-checking {
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
}

.names-checking h3 {
  font-size: 16px;
  color: #333;
  margin-bottom: 5px;
}

.names-checking .subtitle {
  font-size: 13px;
  color: #999;
  margin-bottom: 15px;
}

.names-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.name-item {
  font-size: 13px;
  color: #a93121;
  padding: 2px 8px;
  background: #fdf5f4;
  border-radius: 3px;
  text-decoration: none;
}

.name-item:hover {
  background: #a93121;
  color: #fff;
}

.latest-names {
  background: #fff;
  padding: 20px;
  border-radius: 8px;
}

.latest-names h3 {
  font-size: 16px;
  color: #333;
  margin-bottom: 15px;
  border-left: 3px solid #a93121;
  padding-left: 10px;
}

.article-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.article-list li {
  padding: 8px 0;
  border-bottom: 1px dashed #eee;
}

.article-list li:last-child {
  border-bottom: none;
}

.article-list li a {
  font-size: 13px;
  color: #333;
  text-decoration: none;
  display: block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.article-list li a:hover {
  color: #a93121;
}

.main-right {
  min-width: 0;
}

.almanac-card {
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.almanac-header {
  background: linear-gradient(135deg, #a93121, #c92009);
  color: #fff;
  padding: 15px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.almanac-header h4 {
  font-size: 16px;
  margin: 0;
}

.almanac-header .date {
  font-size: 12px;
  opacity: 0.9;
}

.almanac-info {
  padding: 15px;
  text-align: center;
  border-bottom: 1px solid #f0f0f0;
}

.lunar-date {
  font-size: 14px;
  color: #333;
  line-height: 1.6;
}

.solar-info {
  margin-top: 5px;
}

.constellation {
  display: inline-block;
  background: #fdf5f4;
  color: #a93121;
  padding: 2px 10px;
  border-radius: 10px;
  font-size: 12px;
}

.almanac-day {
  display: flex;
  align-items: center;
  padding: 15px;
  border-bottom: 1px solid #f0f0f0;
}

.day-number {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #a93121, #c92009);
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: bold;
  margin-right: 15px;
}

.solar-term-info {
  flex: 1;
}

.solar-term-info p {
  font-size: 13px;
  color: #333;
  margin: 0;
}

.next-term {
  color: #999 !important;
  font-size: 12px !important;
  margin-top: 3px !important;
}

.almanac-detail {
  padding: 15px;
  border-bottom: 1px solid #f0f0f0;
}

.detail-row {
  display: flex;
  margin-bottom: 10px;
}

.detail-row:last-child {
  margin-bottom: 0;
}

.detail-row .label {
  width: 30px;
  height: 20px;
  background: #a93121;
  color: #fff;
  border-radius: 3px;
  text-align: center;
  line-height: 20px;
  font-size: 12px;
  margin-right: 10px;
}

.detail-row .label.ji {
  background: #666;
}

.detail-row .values {
  flex: 1;
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.yi-item, .ji-item {
  font-size: 12px;
  color: #333;
  padding: 0 5px;
}

.almanac-footer {
  padding: 15px;
}

.info-item {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
  font-size: 13px;
}

.info-item .label {
  color: #999;
  margin-right: 10px;
}

.info-item .value {
  color: #333;
}

.gods-row {
  display: flex;
  justify-content: space-around;
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid #f0f0f0;
}

.god-item {
  text-align: center;
}

.god-label {
  display: block;
  font-size: 12px;
  color: #999;
  margin-bottom: 3px;
}

.god-value {
  font-size: 14px;
  color: #a93121;
  font-weight: bold;
}

.baobao-banner {
  margin-top: 20px;
  border-radius: 8px;
  overflow: hidden;
  position: relative;
}

.baobao-banner a {
  display: block;
  position: relative;
}

.baobao-banner img {
  width: 100%;
  display: block;
}

.baobao-text {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
  color: #fff;
}

.baobao-text h3 {
  font-size: 36px;
  margin-bottom: 10px;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

.baobao-text p {
  font-size: 18px;
  text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
}

.service-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-top: 30px;
}

.service-card {
  background: #fff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.service-card img {
  width: 100%;
  height: 120px;
  object-fit: cover;
}

.service-card-content {
  padding: 15px;
}

.service-card-content h3 {
  font-size: 16px;
  color: #333;
  margin-bottom: 8px;
}

.service-card-content p {
  font-size: 13px;
  color: #666;
  line-height: 1.6;
  margin-bottom: 12px;
}

.service-btn {
  display: inline-block;
  padding: 6px 15px;
  background: linear-gradient(90deg, #c92009, #e6614f);
  color: #fff;
  border-radius: 20px;
  font-size: 13px;
  text-decoration: none;
}

.name-ranks-section {
  margin-top: 30px;
}

.ranks-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.rank-card {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
}

.rank-title {
  font-size: 16px;
  color: #333;
  margin-bottom: 5px;
  border-left: 3px solid #a93121;
  padding-left: 10px;
}

.rank-subtitle {
  font-size: 12px;
  color: #999;
  margin-bottom: 15px;
}

.rank-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.rank-list li a {
  font-size: 13px;
  color: #333;
  text-decoration: none;
  display: block;
  padding: 4px 8px;
  background: #f9f9f9;
  border-radius: 3px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.rank-list li a:hover {
  background: #a93121;
  color: #fff;
}

.name-rank-list {
  grid-template-columns: repeat(3, 1fr);
}

.wuxing-filter, .surname-filter {
  margin-top: 15px;
  padding-top: 15px;
  border-top: 1px solid #f0f0f0;
}

.filter-title {
  font-size: 13px;
  color: #999;
  margin-right: 10px;
}

.wuxing-btn, .surname-btn {
  display: inline-block;
  padding: 3px 10px;
  font-size: 12px;
  color: #a93121;
  background: #fdf5f4;
  border-radius: 3px;
  text-decoration: none;
  margin-right: 5px;
  margin-bottom: 5px;
}

.wuxing-btn:hover, .surname-btn:hover {
  background: #a93121;
  color: #fff;
}

.surname-filter .surname-btn {
  color: #333;
  background: #f5f5f5;
}

.stats-section {
  background: #fff;
  padding: 40px 0;
  margin-top: 30px;
  border-radius: 8px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 20px;
  text-align: center;
}

.stat-item {
  padding: 20px;
}

.stat-number {
  font-size: 28px;
  font-weight: bold;
  color: #a93121;
  margin-bottom: 5px;
}

.stat-label {
  font-size: 14px;
  color: #666;
}

.stats-desc {
  text-align: center;
  font-size: 13px;
  color: #999;
  margin-top: 20px;
}

.search-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.qiming-tan {
  width: 500px;
  background: #fff;
  border-radius: 12px;
  padding: 30px;
}

.qiming-tan h3 {
  text-align: center;
  font-size: 20px;
  margin-bottom: 20px;
}

.search-navbar {
  display: flex;
  border: 1px solid #eee;
  border-radius: 25px;
  overflow: hidden;
}

.search-navbar input {
  flex: 1;
  border: none;
  padding: 12px 20px;
  font-size: 14px;
  outline: none;
}

.search-navbar i {
  padding: 12px 20px;
  background: #f5f5f5;
  color: #999;
  cursor: pointer;
}

.header-btn-search {
  text-align: center;
  margin-top: 20px;
}

.lunpan_box {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.7);
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.lunpan {
  width: 170px;
  height: 170px;
  position: relative;
}

.lunpan img:nth-child(1) {
  width: 170px;
  height: 170px;
  animation: rotate 4s linear infinite;
}

.lunpan img:nth-child(2) {
  width: 20px;
  height: 140px;
  position: absolute;
  top: 15px;
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

@media (max-width: 1200px) {
  .main-content-grid {
    grid-template-columns: 1fr;
  }
  
  .main-right {
    order: -1;
  }
  
  .service-cards {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .ranks-grid {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 768px) {
  .service-list {
    grid-template-columns: repeat(4, 1fr);
  }
  
  .banner-images {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .service-cards {
    grid-template-columns: 1fr;
  }
  
  .poetry-list {
    flex-wrap: wrap;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>

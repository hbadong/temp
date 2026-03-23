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
      </div>

      <div class="qiming_pages_calendar qiming-background-default">
        <div class="calendar-left">
          <h3>今日黄历</h3>
          <div class="calendar-info">
            <p class="calendar-date">2026年3月23日 星期一</p>
            <p class="calendar-lunar">农历二月初五 丙午年【马】</p>
            <p class="calendar-zodiac">白羊座</p>
          </div>
          <div class="calendar-detail">
            <div class="calendar-item yi">
              <span class="label">宜</span>
              <span class="content">嫁娶 开光 祭祀 祈福 求嗣 出行</span>
            </div>
            <div class="calendar-item ji">
              <span class="label">忌</span>
              <span class="content">开市 交易 作灶 纳财 上梁 安床</span>
            </div>
          </div>
        </div>
        <div class="calendar-right">
          <div class="calendar-grid">
            <div class="calendar-grid-item">
              <span class="label">财神</span>
              <span class="value">西南</span>
            </div>
            <div class="calendar-grid-item">
              <span class="label">喜神</span>
              <span class="value">西南</span>
            </div>
            <div class="calendar-grid-item">
              <span class="label">福神</span>
              <span class="value">西北</span>
            </div>
            <div class="calendar-grid-item">
              <span class="label">今日吉时</span>
              <span class="value">丑寅辰未戌亥</span>
            </div>
          </div>
        </div>
      </div>

      <div class="qiming-pages-vip">
        <section class="qiming-container qiming-margin-medium-bottom qiming-margin-top-20">
          <div class="qiming-background-default qiming-padding-app qiming-margin-medium-bottom">
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

      <div class="qiming-pages-ranking qiming-background-default">
        <div class="container">
          <div class="ranking-tabs">
            <span
              :class="{ active: rankingTab === 'boy' }"
              @click="rankingTab = 'boy'"
            >男孩起名用字排行</span>
            <span
              :class="{ active: rankingTab === 'girl' }"
              @click="rankingTab = 'girl'"
            >女孩起名用字排行</span>
            <span
              :class="{ active: rankingTab === 'boyName' }"
              @click="rankingTab = 'boyName'"
            >男孩热门名字</span>
            <span
              :class="{ active: rankingTab === 'girlName' }"
              @click="rankingTab = 'girlName'"
            >女孩热门名字</span>
          </div>
          <div class="ranking-content">
            <div
              v-if="rankingTab === 'boy'"
              class="ranking-list"
            >
              <ul>
                <li><a href="/kxzd/67917.html">圣</a></li>
                <li><a href="/kxzd/54078.html">杰</a></li>
                <li><a href="/kxzd/414411.html">浩</a></li>
                <li><a href="/kxzd/892215.html">旭</a></li>
                <li><a href="/kxzd/580716.html">尧</a></li>
                <li><a href="/kxzd/920617.html">俊</a></li>
                <li><a href="/kxzd/603319.html">天</a></li>
                <li><a href="/kxzd/288220.html">磊</a></li>
                <li><a href="/kxzd/833922.html">伟</a></li>
                <li><a href="/kxzd/305723.html">世</a></li>
              </ul>
            </div>
            <div
              v-if="rankingTab === 'girl'"
              class="ranking-list"
            >
              <ul>
                <li><a href="/kxzd/82815.html">瑾</a></li>
                <li><a href="/kxzd/214818.html">楠</a></li>
                <li><a href="/kxzd/669440.html">莹</a></li>
                <li><a href="/kxzd/998141.html">雪</a></li>
                <li><a href="/kxzd/942543.html">晗</a></li>
                <li><a href="/kxzd/590945.html">琴</a></li>
                <li><a href="/kxzd/785846.html">晴</a></li>
                <li><a href="/kxzd/786447.html">丽</a></li>
                <li><a href="/kxzd/185249.html">瑶</a></li>
                <li><a href="/kxzd/485051.html">茜</a></li>
              </ul>
            </div>
            <div
              v-if="rankingTab === 'boyName'"
              class="ranking-list"
            >
              <ul>
                <li><a href="/mingzi/47683.html">颜豪</a></li>
                <li><a href="/mingzi/764612.html">颢凯</a></li>
                <li><a href="/mingzi/830513.html">颢哲</a></li>
                <li><a href="/mingzi/447016.html">颢宁</a></li>
                <li><a href="/mingzi/644717.html">颢宸</a></li>
                <li><a href="/mingzi/334728.html">璟桓</a></li>
                <li><a href="/mingzi/226334.html">璟霆</a></li>
                <li><a href="/mingzi/164864.html">翔士</a></li>
                <li><a href="/mingzi/2205122.html">名浩</a></li>
                <li><a href="/mingzi/7354131.html">淳皓</a></li>
              </ul>
            </div>
            <div
              v-if="rankingTab === 'girlName'"
              class="ranking-list"
            >
              <ul>
                <li><a href="/mingzi/11662.html">颜菲</a></li>
                <li><a href="/mingzi/986143.html">孟馨</a></li>
                <li><a href="/mingzi/335746.html">宁丽</a></li>
                <li><a href="/mingzi/632947.html">宁俪</a></li>
                <li><a href="/mingzi/820349.html">宁娟</a></li>
                <li><a href="/mingzi/344850.html">宁婕</a></li>
                <li><a href="/mingzi/100151.html">宁汐</a></li>
                <li><a href="/mingzi/673253.html">宁萱</a></li>
                <li><a href="/mingzi/968756.html">宇妃</a></li>
                <li><a href="/mingzi/442557.html">宇妍</a></li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="qiming-pages-surname qiming-background-default">
        <div class="container">
          <div class="section-title">
            <i class="iconfont icon-book" />
            <h3 class="qiming-display-inline-block">
              百家姓入口
            </h3>
          </div>
          <div class="surname-grid">
            <router-link
              v-for="item in surnameList"
              :key="item.name"
              :to="item.url"
              class="surname-item"
            >
              {{ item.name }}
            </router-link>
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

      <div class="qiming-pages-stats qiming-background-default">
        <div class="container">
          <div class="stats-grid">
            <div class="stats-item">
              <span class="stats-number">5000万+</span>
              <span class="stats-label">访问总数</span>
            </div>
            <div class="stats-item">
              <span class="stats-number">2000万+</span>
              <span class="stats-label">名字库收录</span>
            </div>
            <div class="stats-item">
              <span class="stats-number">560万+</span>
              <span class="stats-label">起名知识</span>
            </div>
            <div class="stats-item">
              <span class="stats-number">80万+</span>
              <span class="stats-label">起名客户</span>
            </div>
            <div class="stats-item">
              <span class="stats-number">98%+</span>
              <span class="stats-label">用户满意度</span>
            </div>
            <div class="stats-item">
              <span class="stats-number">20年+</span>
              <span class="stats-label">运行时间</span>
            </div>
          </div>
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

    <div
      v-if="showSearch"
      class="search-modal"
    >
      <div
        class="search-modal-overlay"
        @click="showSearch = false"
      />
      <div class="search-modal-content">
        <div class="search-header">
          <h3>搜索</h3>
          <a
            href="javascript:;"
            @click="showSearch = false"
          >
            <i class="iconfont icon-close" />
          </a>
        </div>
        <div class="search-body">
          <div class="search-input-wrapper">
            <input
              v-model="searchKeyword"
              type="text"
              placeholder="立即搜索"
              @keyup.enter="handleSearch"
            >
            <i class="iconfont icon-search" />
          </div>
          <div class="search-hot">
            <span class="search-hot-title">搜索最多的：</span>
            <div class="search-hot-list">
              <a
                v-for="item in hotSearch"
                :key="item.word"
                :href="item.url"
              >{{ item.word }}</a>
            </div>
          </div>
          <div class="search-tags">
            <span
              v-for="tag in searchTags"
              :key="tag"
              class="search-tag"
            >{{ tag }}</span>
          </div>
        </div>
      </div>
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
const showMobileMenu = ref(false);
const searchKeyword = ref('');
const rankingTab = ref('boy');

const form = reactive({
  surname: '',
  gender: 1,
  birthday: '',
  birthAddress: ''
});

const hotSearch = reactive([
  { word: '雪', url: '/kxzd/998141.html' },
  { word: '旭', url: '/kxzd/892215.html' },
  { word: '信川', url: '/mingzi/6676140668.html' },
  { word: '煜晨', url: '/mingzi/88032507.html' }
]);

const searchTags = ['宝宝起名', '姓名测试', '姓名打分'];

const surnameList = reactive([
  { name: '王', url: '/nanhai/xing/79711.html' },
  { name: '李', url: '/nanhai/xing/69822.html' },
  { name: '张', url: '/nanhai/xing/12573.html' },
  { name: '刘', url: '/nanhai/xing/98404.html' },
  { name: '陈', url: '/nanhai/xing/81405.html' },
  { name: '杨', url: '/nanhai/xing/38766.html' },
  { name: '黄', url: '/nanhai/xing/71237.html' },
  { name: '赵', url: '/nanhai/xing/97988.html' },
  { name: '吴', url: '/nanhai/xing/22479.html' },
  { name: '周', url: '/nanhai/xing/106010.html' }
]);

const handleSubmit = () => {
  if (!form.surname) {
    message.warning('请输入姓氏');
    return;
  }
  router.push({ path: '/baobao', query: { surname: form.surname, gender: form.gender } });
};

const handleSearch = () => {
  if (!searchKeyword.value) {
    message.warning('请输入搜索关键词');
    return;
  }
  message.info(`搜索：${searchKeyword.value}`);
  showSearch.value = false;
};

const scrollTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
};
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

.qiming_pages_calendar {
  max-width: 1200px;
  margin: 20px auto;
  padding: 24px;
  border-radius: 8px;
  display: flex;
  gap: 30px;

  @media (max-width: 767px) {
    flex-direction: column;
    padding: 15px;
  }
}

.calendar-left {
  flex: 1;

  h3 {
    font-size: 18px;
    color: #333;
    margin-bottom: 15px;
    border-left: 3px solid #a93121;
    padding-left: 10px;
  }
}

.calendar-info {
  margin-bottom: 15px;

  .calendar-date {
    font-size: 16px;
    color: #333;
    font-weight: bold;
    margin-bottom: 5px;
  }

  .calendar-lunar {
    font-size: 14px;
    color: #666;
    margin-bottom: 3px;
  }

  .calendar-zodiac {
    font-size: 14px;
    color: #999;
  }
}

.calendar-detail {
  .calendar-item {
    display: flex;
    margin-bottom: 8px;
    font-size: 13px;

    .label {
      width: 32px;
      color: #fff;
      text-align: center;
      border-radius: 3px;
      padding: 2px 0;
      margin-right: 10px;
    }

    &.yi .label {
      background: #52c41a;
    }

    &.ji .label {
      background: #f5222d;
    }

    .content {
      flex: 1;
      color: #666;
      line-height: 1.5;
    }
  }
}

.calendar-right {
  flex: 1;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 15px;

  @media (max-width: 767px) {
    grid-template-columns: repeat(2, 1fr);
  }
}

.calendar-grid-item {
  text-align: center;
  padding: 12px;
  background: #fafafa;
  border-radius: 6px;

  .label {
    display: block;
    font-size: 12px;
    color: #999;
    margin-bottom: 5px;
  }

  .value {
    font-size: 14px;
    color: #333;
    font-weight: bold;
  }
}

.qiming-pages-ranking {
  max-width: 1200px;
  margin: 20px auto;
  padding: 24px;
  border-radius: 8px;
}

.ranking-tabs {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
  border-bottom: 2px solid #f0f0f0;
  padding-bottom: 15px;

  span {
    cursor: pointer;
    font-size: 15px;
    color: #666;
    padding: 5px 15px;
    border-radius: 4px;
    transition: all 0.3s;

    &:hover {
      color: #a93121;
    }

    &.active {
      background: linear-gradient(90deg, #c92009, #e6614f);
      color: #fff;
    }
  }
}

.ranking-list {
  ul {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    list-style: none;
    padding: 0;
    margin: 0;

    li {
      a {
        display: inline-block;
        padding: 8px 16px;
        background: #f5f5f5;
        color: #333;
        border-radius: 4px;
        font-size: 14px;
        transition: all 0.3s;

        &:hover {
          background: #a93121;
          color: #fff;
        }
      }
    }
  }
}

.qiming-pages-surname {
  max-width: 1200px;
  margin: 20px auto;
  padding: 24px;
  border-radius: 8px;
}

.surname-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 15px;
}

.surname-item {
  width: calc(10% - 12px);
  padding: 12px;
  background: #fafafa;
  border-radius: 6px;
  text-align: center;
  color: #333;
  font-size: 16px;
  font-weight: bold;
  transition: all 0.3s;

  &:hover {
    background: linear-gradient(90deg, #c92009, #e6614f);
    color: #fff;
  }

  @media (max-width: 767px) {
    width: calc(20% - 10px);
    padding: 10px;
    font-size: 14px;
  }
}

.qiming-pages-stats {
  max-width: 1200px;
  margin: 20px auto;
  padding: 30px;
  border-radius: 8px;
}

.stats-grid {
  display: flex;
  justify-content: space-around;
  flex-wrap: wrap;
  gap: 20px;
}

.stats-item {
  text-align: center;

  .stats-number {
    display: block;
    font-size: 28px;
    font-weight: bold;
    color: #a93121;
    margin-bottom: 8px;
  }

  .stats-label {
    font-size: 14px;
    color: #666;
  }
}

.search-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 1000;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 100px;
}

.search-modal-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
}

.search-modal-content {
  position: relative;
  width: 600px;
  max-width: 90%;
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.search-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px 20px;
  border-bottom: 1px solid #eee;

  h3 {
    font-size: 18px;
    color: #333;
    margin: 0;
  }

  a {
    color: #999;
    font-size: 20px;

    &:hover {
      color: #333;
    }
  }
}

.search-body {
  padding: 20px;
}

.search-input-wrapper {
  position: relative;

  input {
    width: 100%;
    padding: 12px 15px 12px 45px;
    border: 2px solid #eee;
    border-radius: 6px;
    font-size: 15px;
    outline: none;
    transition: border-color 0.3s;

    &:focus {
      border-color: #a93121;
    }
  }

  .iconfont {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 18px;
  }
}

.search-hot {
  margin-top: 15px;

  .search-hot-title {
    font-size: 13px;
    color: #999;
  }

  .search-hot-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;

    a {
      color: #a93121;
      font-size: 14px;

      &:hover {
        text-decoration: underline;
      }
    }
  }
}

.search-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 15px;

  .search-tag {
    padding: 6px 15px;
    background: #f5f5f5;
    color: #666;
    border-radius: 20px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;

    &:hover {
      background: #a93121;
      color: #fff;
    }
  }
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

.qiming-margin-medium-top {
  margin-top: 20px;
}

.qiming-margin-bottom-40 {
  margin-bottom: 40px;
}
</style>

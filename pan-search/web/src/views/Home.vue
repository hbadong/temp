<template>
  <div class="home">
    <header class="topbar">
      <div class="container topbar-inner">
        <router-link to="/" class="logo small">盘搜</router-link>
        <nav>
          <router-link to="/complain">侵权投诉</router-link>
          <router-link to="/admin">管理后台</router-link>
        </nav>
      </div>
    </header>

    <main class="hero">
      <div class="hero-content">
        <h1 class="logo">盘搜 <span>PanSearch</span></h1>
        <p class="slogan">聚合百度 / 阿里 / 夸克 / 迅雷等网盘资源，一搜即达</p>

        <div class="search-area">
          <SearchBox big v-model="keyword" @search="goSearch" />
          <div class="hot">
            <span>热门搜索：</span>
            <a
              v-for="kw in hotKeywords"
              :key="kw"
              @click.prevent="keyword = kw; goSearch(kw)"
              class="tag tag--info"
            >{{ kw }}</a>
          </div>
        </div>

        <div class="cloud-entry">
          <a
            v-for="c in clouds"
            :key="c.type"
            class="cloud-item card"
            @click.prevent="goSearch('', c.type)"
          >
            <span class="emoji">{{ c.emoji }}</span>
            <span>{{ c.name }}</span>
          </a>
        </div>

        <div v-if="stats" class="stats">
          已收录 <b>{{ stats.total.toLocaleString() }}</b> 条资源 · 今日新增
          <b>{{ stats.today_new.toLocaleString() }}</b> 条
        </div>
      </div>
    </main>

    <footer class="footer">
      <div class="container">
        <p>
          本站所有内容均来自网络公开渠道，仅用于学习交流，请在下载后 24 小时内删除。
        </p>
        <p>
          <router-link to="/complain">侵权投诉 DMCA</router-link>
          <span> · </span>
          <router-link to="/admin">后台管理</router-link>
        </p>
      </div>
    </footer>
  </div>
</template>

<script>
import SearchBox from '../components/SearchBox.vue'
import { getHot, getStats } from '../api'
import { CLOUD_NAMES, CLOUD_ICONS } from '../constants'

export default {
  name: 'HomeView',
  components: { SearchBox },
  data() {
    return { keyword: '', hotKeywords: [], stats: null }
  },
  computed: {
    clouds() {
      return ['baidu', 'aliyun', 'quark', 'xunlei'].map((type) => ({
        type,
        name: CLOUD_NAMES[type],
        emoji: CLOUD_ICONS[type],
      }))
    },
  },
  mounted() {
    getHot().then((d) => (this.hotKeywords = d.keywords)).catch(() => {})
    getStats().then((d) => (this.stats = d)).catch(() => {})
  },
  methods: {
    goSearch(q, cloud) {
      const query = {}
      if (q) query.q = q
      if (cloud) query.cloud = cloud
      this.$router.push({ path: '/search', query })
    },
  },
}
</script>

<style scoped>
.topbar {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 10;
}

.topbar-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 56px;
}

.topbar nav a {
  margin-left: 18px;
  color: var(--text-secondary);
  font-size: var(--font-size-base);
  transition: color var(--transition-fast);
}

.topbar nav a:hover {
  color: var(--primary);
}

.home {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--bg-page);
}

.hero {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 20px 40px;
  text-align: center;
}

.hero-content {
  width: 100%;
  max-width: 640px;
}

.logo {
  font-size: 36px;
  font-weight: 600;
  letter-spacing: -0.5px;
  color: var(--text-primary);
  line-height: 1.2;
}

.logo.small {
  font-size: 20px;
  font-weight: 600;
}

.logo span {
  font-size: 14px;
  color: var(--text-secondary);
  font-weight: 400;
  letter-spacing: 0;
  display: inline-block;
  margin-left: 8px;
  vertical-align: middle;
}

.slogan {
  margin: 16px 0 32px;
  color: var(--text-regular);
  font-size: var(--font-size-base);
  line-height: 1.6;
}

.search-area {
  width: 100%;
}

.hot {
  margin-top: 20px;
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
}

.hot > span {
  color: var(--text-secondary);
}

.hot a.tag {
  cursor: pointer;
  transition: all var(--transition-fast);
}

.hot a.tag:hover {
  background: var(--primary);
  color: #fff;
  border-color: var(--primary);
}

.cloud-entry {
  margin-top: 40px;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
}

.cloud-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  width: 100px;
  padding: 20px 0;
  cursor: pointer;
  transition: all var(--transition-base);
  font-size: var(--font-size-sm);
  color: var(--text-regular);
}

.cloud-item:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-base);
  border-color: var(--border-light);
  color: var(--primary);
}

.emoji {
  font-size: 28px;
  line-height: 1;
}

.stats {
  margin-top: 40px;
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
}

.stats b {
  color: var(--text-primary);
  font-weight: 600;
}

.footer {
  padding: 24px 0;
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  text-align: center;
  line-height: 1.8;
  border-top: 1px solid var(--border-divider);
  margin-top: auto;
}

.footer a {
  color: var(--text-secondary);
  transition: color var(--transition-fast);
}

.footer a:hover {
  color: var(--primary);
}

.footer span {
  color: var(--border-base);
}

@media (max-width: 767px) {
  .logo {
    font-size: 28px;
  }

  .logo span {
    font-size: 12px;
  }

  .slogan {
    font-size: var(--font-size-sm);
  }

  .cloud-item {
    width: calc(50% - 6px);
    padding: 16px 0;
  }

  .emoji {
    font-size: 24px;
  }

  .topbar-inner {
    height: 48px;
  }
}
</style>
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
  color: var(--text-2);
  font-size: 13px;
}

.topbar nav a:hover {
  color: var(--primary);
}

.home {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background:
    radial-gradient(800px 400px at 20% -10%, rgba(47, 107, 255, 0.12), transparent),
    radial-gradient(600px 300px at 80% 0%, rgba(122, 92, 255, 0.1), transparent),
    var(--bg);
}

.logo {
  font-size: 40px;
  font-weight: 700;
  letter-spacing: 2px;
  color: var(--primary);
}

.logo.small {
  font-size: 22px;
}

.logo span {
  font-size: 16px;
  color: var(--text-3);
  font-weight: 400;
  letter-spacing: 0;
}

.hero {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 16px 40px;
  text-align: center;
}

.slogan {
  margin: 14px 0 30px;
  color: var(--text-2);
  font-size: 15px;
}

.search-area {
  width: min(640px, 100%);
}

.hot {
  margin-top: 16px;
  font-size: 13px;
  color: var(--text-3);
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: center;
}

.hot a {
  color: var(--text-2);
  background: rgba(255, 255, 255, 0.7);
  padding: 3px 10px;
  border-radius: 20px;
  cursor: pointer;
  border: 1px solid var(--border);
}

.hot a:hover {
  color: var(--primary);
  border-color: var(--primary);
}

.cloud-entry {
  margin-top: 44px;
  display: flex;
  gap: 14px;
  flex-wrap: wrap;
  justify-content: center;
}

.cloud-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  width: 92px;
  padding: 16px 0;
  cursor: pointer;
  transition: all 0.15s;
  font-size: 13px;
  color: var(--text-2);
}

.cloud-item:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(31, 45, 80, 0.1);
  color: var(--primary);
}

.emoji {
  font-size: 26px;
}

.stats {
  margin-top: 36px;
  color: var(--text-3);
  font-size: 13px;
}

.stats b {
  color: var(--primary);
}

.footer {
  padding: 18px 0 22px;
  color: var(--text-3);
  font-size: 12px;
  text-align: center;
  line-height: 1.9;
}
</style>

<template>
  <div class="home">
    <header class="topbar">
      <div class="inner-block topbar-inner">
        <router-link to="/" class="logo small">盘搜</router-link>
        <nav>
          <router-link to="/submit">提交资源</router-link>
          <router-link to="/complain">侵权投诉</router-link>
          <router-link to="/admin">管理后台</router-link>
          <ThemeToggle />
        </nav>
      </div>
    </header>

    <main class="hero">
      <div class="inner-block hero-content">
        <h1 class="logo">盘搜 <span>PanSearch</span></h1>
        <p class="slogan">聚合百度 / 阿里 / 夸克 / 迅雷等网盘资源，一搜即达</p>

        <div class="search-area">
          <SearchBox big v-model="keyword" @search="goSearch" />
          <div class="hot">
            <span class="hot__label">热门搜索</span>
            <a
              v-for="kw in hotKeywords"
              :key="kw"
              @click.prevent="keyword = kw; goSearch(kw)"
              class="hot-tag"
            >{{ kw }}</a>
          </div>
        </div>

        <div v-if="searchHistory.length" class="history">
          <div class="history-header">
            <span class="history__label">搜索历史</span>
            <button class="btn-text" @click="clearHistory">清空</button>
          </div>
          <div class="history-tags">
            <a
              v-for="kw in searchHistory"
              :key="kw"
              @click.prevent="keyword = kw; goSearch(kw)"
              class="hot-tag"
            >{{ kw }}</a>
          </div>
        </div>

        <div class="cloud-entry">
          <a
            v-for="c in clouds"
            :key="c.type"
            class="cloud-item"
            @click.prevent="goSearch('', c.type)"
          >
            <span class="cloud-emoji">{{ c.emoji }}</span>
            <span class="cloud-name">{{ c.name }}</span>
          </a>
        </div>

        <div v-if="stats" class="stats">
          <div class="stat-item">
            <div class="stat-num">{{ stats.total.toLocaleString() }}</div>
            <div class="stat-label">资源总数</div>
          </div>
          <div class="stat-item">
            <div class="stat-num">{{ stats.today_new.toLocaleString() }}</div>
            <div class="stat-label">今日新增</div>
          </div>
        </div>
      </div>
    </main>

    <SiteFooter />
  </div>
</template>

<script>
import SearchBox from '../components/SearchBox.vue'
import ThemeToggle from '../components/ThemeToggle.vue'
import SiteFooter from '../components/SiteFooter.vue'
import { getHot, getStats } from '../api'
import { CLOUD_NAMES, CLOUD_ICONS } from '../constants'

export default {
  name: 'HomeView',
  components: { SearchBox, ThemeToggle, SiteFooter },
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
    searchHistory() {
      try {
        const history = JSON.parse(localStorage.getItem('pansearch_history') || '[]')
        return history.slice(0, 5)
      } catch {
        return []
      }
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
    clearHistory() {
      localStorage.removeItem('pansearch_history')
      this.$forceUpdate()
    },
  },
}
</script>

<style scoped>
.inner-block {
  width: 940px;
  max-width: 100%;
  margin: 0 auto;
  padding: 0 20px;
}

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

.topbar nav {
  display: flex;
  align-items: center;
  gap: 18px;
}

.topbar nav a {
  color: var(--text-secondary);
  font-size: 14px;
  transition: color var(--transition-fast);
  text-decoration: none;
}

.topbar nav a:hover,
.topbar nav a.router-link-active {
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
  padding: 80px 0 40px;
  text-align: center;
}

.hero-content {
  width: 100%;
}

.logo {
  font-size: 36px;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.2;
}

.logo.small {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  text-decoration: none;
}

.logo span {
  font-size: 14px;
  color: var(--text-secondary);
  font-weight: 400;
  display: inline-block;
  margin-left: 8px;
  vertical-align: middle;
}

.slogan {
  margin: 16px 0 32px;
  color: var(--text-regular);
  font-size: 14px;
  line-height: 1.6;
}

.search-area {
  width: 100%;
  max-width: 560px;
  margin: 0 auto;
}

.hot {
  margin-top: 20px;
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
}

.hot__label {
  font-size: 13px;
  color: var(--text-secondary);
  margin-right: 4px;
}

.hot-tag {
  display: inline-block;
  height: 32px;
  padding: 0 10px;
  line-height: 30px;
  font-size: 12px;
  color: var(--primary);
  border-radius: 4px;
  background-color: var(--primary-bg);
  border: 1px solid #f0e1ff;
  white-space: nowrap;
  cursor: pointer;
  transition: all .2s ease;
  text-decoration: none;
}

.hot-tag:hover {
  color: #fff;
  background-color: var(--primary);
  border-color: var(--primary);
}

.history {
  margin-top: 24px;
  max-width: 560px;
  margin-left: auto;
  margin-right: auto;
  text-align: left;
}

.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
  font-size: 13px;
  color: var(--text-secondary);
}

.history__label {
  color: var(--text-secondary);
}

.btn-text {
  padding: 0;
  height: auto;
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 12px;
  cursor: pointer;
}

.btn-text:hover {
  color: var(--primary);
}

.history-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
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
  transition: all .2s ease;
  font-size: 13px;
  color: var(--text-regular);
  text-decoration: none;
  border-radius: 4px;
}

.cloud-item:hover {
  transform: translateY(-2px);
  color: var(--primary);
}

.cloud-emoji {
  font-size: 28px;
  line-height: 1;
}

.cloud-name {
  font-size: 13px;
}

.stats {
  margin-top: 40px;
  display: flex;
  justify-content: center;
  gap: 60px;
}

.stat-item {
  text-align: center;
}

.stat-num {
  font-size: 20px;
  font-weight: 700;
  color: var(--text-primary);
  font-variant: tabular-nums;
  font-feature-settings: "tnum";
  padding: 0 4px;
}

.stat-label {
  margin-top: 4px;
  font-size: 13px;
  color: var(--text-secondary);
}

@media (max-width: 700px) {
  .inner-block {
    width: 100%;
    padding: 0 15px;
  }

  .logo {
    font-size: 28px;
  }

  .logo span {
    font-size: 12px;
  }

  .slogan {
    font-size: 13px;
  }

  .search-area {
    max-width: 100%;
  }

  .cloud-item {
    width: calc(50% - 6px);
    padding: 16px 0;
  }

  .cloud-emoji {
    font-size: 24px;
  }

  .topbar-inner {
    height: 48px;
  }

  .topbar nav {
    gap: 12px;
  }

  .topbar nav a {
    font-size: 13px;
  }

  .stats {
    gap: 30px;
  }

  .stat-num {
    font-size: 18px;
  }
}
</style>
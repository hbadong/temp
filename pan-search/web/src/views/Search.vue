<template>
  <div class="search-page">
    <header class="header">
      <div class="container header-inner">
        <router-link to="/" class="logo">盘搜</router-link>
        <div class="search-wrap">
          <SearchBox v-model="keyword" @search="onSearch" />
        </div>
        <nav>
          <router-link to="/complain">投诉</router-link>
          <router-link to="/admin">后台</router-link>
        </nav>
      </div>
    </header>

    <main class="container">
      <div class="filters card">
        <div class="filter-row">
          <span class="filter-label">网盘</span>
          <a
            v-for="c in cloudOptions"
            :key="c.value"
            :class="['filter-item', { active: currentCloud === c.value }]"
            @click.prevent="setFilter('cloud', c.value)"
          >{{ c.label }}</a>
        </div>
        <div class="filter-row">
          <span class="filter-label">类型</span>
          <a
            v-for="t in typeOptions"
            :key="t.value"
            :class="['filter-item', { active: currentType === t.value }]"
            @click.prevent="setFilter('type', t.value)"
          >{{ t.label }}</a>
        </div>
      </div>

      <div class="result-meta" v-if="result">
        <span>共找到 <b>{{ result.total.toLocaleString() }}</b> 条结果</span>
        <span class="took">耗时 {{ result.took_ms }} ms</span>
      </div>

      <template v-if="result && result.items.length">
        <ResourceCard
          v-for="item in result.items"
          :key="item.id"
          :item="item"
          :keyword="currentQ"
        />
        <div class="pager" v-if="totalPages > 1">
          <button class="btn btn-sm" :disabled="page <= 1" @click="goPage(page - 1)">上一页</button>
          <span class="page-info">{{ page }} / {{ totalPages }}</span>
          <button class="btn btn-sm" :disabled="page >= totalPages" @click="goPage(page + 1)">下一页</button>
        </div>
      </template>

      <div v-else-if="!loading" class="empty card">
        <p style="font-size: 40px; margin-bottom: 12px">🔍</p>
        <p>没有找到相关资源，换个关键词试试吧</p>
      </div>

      <div v-if="loading" class="empty">搜索中...</div>
    </main>

    <footer class="footer">
      本站所有内容均来自网络公开渠道，仅用于学习交流 ·
      <router-link to="/complain">侵权投诉</router-link>
    </footer>
  </div>
</template>

<script>
import SearchBox from '../components/SearchBox.vue'
import ResourceCard from '../components/ResourceCard.vue'
import { search } from '../api'
import { CLOUD_NAMES, RES_TYPE_NAMES } from '../constants'

export default {
  name: 'SearchView',
  components: { SearchBox, ResourceCard },
  data() {
    return {
      keyword: this.$route.query.q || '',
      currentCloud: this.$route.query.cloud || '',
      currentType: this.$route.query.type || '',
      page: parseInt(this.$route.query.page, 10) || 1,
      result: null,
      loading: false,
    }
  },
  computed: {
    currentQ() {
      return (this.$route.query.q || '').trim()
    },
    totalPages() {
      return Math.max(1, Math.ceil(this.result.total / this.result.size))
    },
    cloudOptions() {
      const main = ['aliyun', 'baidu', 'quark', 'xunlei']
      return [
        { value: '', label: '全部' },
        ...main.map((t) => ({ value: t, label: CLOUD_NAMES[t] })),
      ]
    },
    typeOptions() {
      return [
        { value: '', label: '全部' },
        ...Object.entries(RES_TYPE_NAMES).map(([value, label]) => ({ value, label })),
      ]
    },
  },
  watch: {
    $route() {
      this.syncFromRoute()
      this.doSearch()
    },
  },
  mounted() {
    this.doSearch()
  },
  methods: {
    syncFromRoute() {
      this.keyword = this.$route.query.q || ''
      this.currentCloud = this.$route.query.cloud || ''
      this.currentType = this.$route.query.type || ''
      this.page = parseInt(this.$route.query.page, 10) || 1
    },
    updateRoute() {
      const query = {}
      if (this.currentQ) query.q = this.currentQ
      if (this.currentCloud) query.cloud = this.currentCloud
      if (this.currentType) query.type = this.currentType
      if (this.page > 1) query.page = String(this.page)
      this.$router.push({ path: '/search', query }).catch(() => {})
    },
    onSearch(q) {
      this.keyword = q
      this.page = 1
      this.updateRoute()
    },
    setFilter(kind, value) {
      if (kind === 'cloud') this.currentCloud = value
      if (kind === 'type') this.currentType = value
      this.page = 1
      this.updateRoute()
    },
    goPage(p) {
      this.page = p
      this.updateRoute()
      window.scrollTo({ top: 0 })
    },
    async doSearch() {
      this.loading = true
      try {
        this.result = await search({
          q: this.currentQ,
          cloud: this.currentCloud,
          type: this.currentType,
          page: this.page,
          size: 20,
        })
      } catch (err) {
        this.$toast(err.message)
        this.result = { total: 0, items: [], took_ms: 0, size: 20 }
      } finally {
        this.loading = false
      }
    },
  },
}
</script>

<style scoped>
.header {
  position: sticky;
  top: 0;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid var(--border);
  z-index: 50;
}

.header-inner {
  display: flex;
  align-items: center;
  gap: 18px;
  height: 62px;
}

.logo {
  font-size: 24px;
  font-weight: 700;
  color: var(--primary);
  flex-shrink: 0;
}

.search-wrap {
  flex: 1;
  max-width: 560px;
}

.header nav {
  margin-left: auto;
  display: flex;
  gap: 14px;
  flex-shrink: 0;
}

.header nav a {
  color: var(--text-2);
  font-size: 13px;
}

.filters {
  padding: 10px 16px;
  margin: 16px 0;
}

.filter-row {
  display: flex;
  align-items: center;
  gap: 4px;
  flex-wrap: wrap;
  padding: 4px 0;
}

.filter-label {
  color: var(--text-3);
  font-size: 13px;
  width: 36px;
  flex-shrink: 0;
}

.filter-item {
  padding: 4px 12px;
  border-radius: 6px;
  color: var(--text-2);
  cursor: pointer;
  font-size: 13px;
}

.filter-item:hover {
  color: var(--primary);
}

.filter-item.active {
  background: var(--primary);
  color: #fff;
}

.result-meta {
  display: flex;
  justify-content: space-between;
  color: var(--text-3);
  font-size: 13px;
  margin-bottom: 10px;
}

.result-meta b {
  color: var(--primary);
}

.took {
  color: var(--text-3);
}

.page-info {
  color: var(--text-2);
  font-size: 13px;
}

.footer {
  text-align: center;
  padding: 24px 0;
  color: var(--text-3);
  font-size: 12px;
}

@media (max-width: 640px) {
  .header-inner {
    flex-wrap: wrap;
    height: auto;
    padding-top: 10px;
    padding-bottom: 10px;
  }

  .search-wrap {
    order: 3;
    max-width: 100%;
    flex-basis: 100%;
  }
}
</style>

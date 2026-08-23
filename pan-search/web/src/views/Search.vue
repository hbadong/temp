<template>
  <div class="search-page">
    <header class="header">
      <div class="inner-block header-inner">
        <router-link to="/" class="logo small">盘搜</router-link>
        <div class="search-wrap">
          <SearchBox
            v-model="keyword"
            @search="onSearch"
            @input="onSearchInput"
            big
          />
        </div>
        <nav>
          <router-link to="/submit">提交资源</router-link>
          <router-link to="/complain">投诉</router-link>
          <router-link to="/admin">后台</router-link>
          <ThemeToggle />
        </nav>
      </div>
    </header>

    <main class="inner-block main">
      <div class="filter-wrap" :class="{ 'filter-wrap--collapsed': !filtersExpanded }">
        <button class="filter-toggle" @click="filtersExpanded = !filtersExpanded">
          <span>筛选</span>
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" :class="{ 'filter-toggle__arrow--open': filtersExpanded }">
            <polyline points="6 9 12 15 18 9" />
          </svg>
        </button>
        <div class="filter-body" v-show="filtersExpanded">
          <label class="filter-label">筛选:</label>
          <div class="filter-item" :class="{ active: activeFilter === 'type' }" @click.stop="toggleFilter('type')">
            <span>{{ currentTypeLabel }}</span>
            <svg class="arrow" viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9" /></svg>
            <ul v-show="activeFilter === 'type'">
              <li @click.stop="setFilter('type', '')">全部类型</li>
              <li v-for="(name, key) in RES_TYPE_NAMES" :key="key" @click.stop="setFilter('type', key)">{{ name }}</li>
            </ul>
          </div>
          <div class="filter-item" :class="{ active: activeFilter === 'size' }" @click.stop="toggleFilter('size')">
            <span>{{ currentSizeLabel }}</span>
            <svg class="arrow" viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9" /></svg>
            <ul v-show="activeFilter === 'size'">
              <li @click.stop="setFilter('size', '')">全部大小</li>
              <li v-for="s in sizeOptions" :key="s.value" @click.stop="setFilter('size', s.value)">{{ s.label }}</li>
            </ul>
          </div>
          <div class="filter-item" :class="{ active: activeFilter === 'time' }" @click.stop="toggleFilter('time')">
            <span>{{ currentTimeLabel }}</span>
            <svg class="arrow" viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9" /></svg>
            <ul v-show="activeFilter === 'time'">
              <li @click.stop="setFilter('time', '')">全部时间</li>
              <li v-for="t in timeOptions" :key="t.value" @click.stop="setFilter('time', t.value)">{{ t.label }}</li>
            </ul>
          </div>
          <div class="filter-item" :class="{ active: activeFilter === 'sort' }" @click.stop="toggleFilter('sort')">
            <span>{{ currentSortLabel }}</span>
            <svg class="arrow" viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9" /></svg>
            <ul v-show="activeFilter === 'sort'">
              <li v-for="s in sortOptions" :key="s.value" @click.stop="setFilter('sort', s.value)">{{ s.label }}</li>
            </ul>
          </div>
          <a v-if="hasActiveFilter" class="clear-button" @click.prevent="clearFilters">清除</a>
        </div>
      </div>

      <div class="tab-wrap">
        <span class="tab-label">网盘：</span>
        <a
          v-for="t in cloudTabs"
          :key="t.value"
          :class="['tab-item', { active: currentCloud === t.value }]"
          @click.prevent="setCloud(t.value)"
        >
          <span class="tab-icon">{{ t.icon }}</span>
          {{ t.label }}
        </a>
      </div>

      <div v-if="currentQ && !loading && result" class="search-result">
        <div class="result-inner" v-if="result.items.length">
          <p class="search-tip">
            <span v-if="currentCloudName">{{ currentCloudName }} </span>
            <span>共搜索出 <span class="em">{{ result.total.toLocaleString() }}</span> 条结果</span>
          </p>
          <div class="result-wrap">
            <div
              v-for="item in result.items"
              :key="item.id"
              class="resource-item-wrap"
              :class="{ invalid: item.status === 'expired' }"
            >
              <div class="resource-item">
                <img class="filetype" :src="fileTypeIcon(item)" :alt="item.title" />
                <div class="resource-info">
                  <h1 class="resource-title">
                    <router-link :to="`/detail/${item.id}`" v-html="highlightHtml(item.title, currentQ)"></router-link>
                  </h1>
                  <div class="detail-wrap" v-if="item.files && item.files.length">
                    <p
                      v-for="(f, i) in item.files.slice(0, 5)"
                      :key="i"
                      class="detail-item-wrap"
                    >
                      <span class="detail-item-title" v-html="highlightHtml(f.name, currentQ)"></span>
                      <span v-if="!f.is_dir && f.size"> - {{ formatSize(f.size) }}</span>
                    </p>
                    <p v-if="item.files.length > 5" class="detail-item-wrap">
                      <span>......</span>
                    </p>
                  </div>
                  <div class="resource-meta">
                    <span class="meta-item">
                      <span class="label">文件大小</span>
                      <span class="em">{{ item.size_text || '未知' }}</span>
                    </span>
                    <span class="tag">{{ cloudName(item.cloud_type) }}</span>
                    <span class="tag">{{ resTypeName(item.res_type) }}</span>
                    <span v-if="item.status === 'expired'" class="warning em" style="padding-left:6px">资源已失效</span>
                    <span v-else class="success em" style="padding-left:6px">资源有效</span>
                  </div>
                </div>
                <div class="other-info">
                  <p class="time">{{ formatDate(item.published_at) }}</p>
                </div>
              </div>
            </div>
          </div>
          <div class="pager-wrap" v-if="totalPages > 1">
            <div class="pc-pager-wrap">
              <a v-if="page > 1" class="pager-item" @click.prevent="goPage(page - 1)">上一页</a>
              <a
                v-for="p in pageList"
                :key="p"
                :class="['pager-item', { active: p === page, 'pager-mark': p === '...' }]"
                @click.prevent="p !== '...' && goPage(p)"
              >{{ p }}</a>
              <a v-if="page < totalPages" class="pager-item" @click.prevent="goPage(page + 1)">下一页</a>
            </div>
            <div class="mobile-pager-wrap">
              <a v-if="page > 1" class="pager-item" @click.prevent="goPage(page - 1)">上一页</a>
              <a v-if="page < totalPages" class="pager-item" @click.prevent="goPage(page + 1)">下一页</a>
            </div>
          </div>
        </div>
        <div v-else class="sensitive">
          <LottiePlayer :animationData="searchEmptyAnimation" width="150px" height="150px" />
          <p class="none-tip">没有搜到 <span class="em">『{{ currentQ }}』</span> 的任何结果，换个关键词试试吧。</p>
        </div>
      </div>

      <div v-if="loading" class="loading-wrap">
        <LottiePlayer :animationData="loadingAnimation" width="160px" height="160px" :speed="0.4" />
        <p>资源搜索中...</p>
      </div>

      <div v-if="!currentQ && !loading" class="search-tip-wrap">
        <h4>搜索小技巧：</h4>
        <p class="search-tip">1、可以灵活选用「精准搜索」和「模糊搜索」</p>
        <p class="search-tip">2、搜索资源时可以通过资源「类型」、「时间」等进行筛选</p>
        <p class="search-tip">3、可以使用网盘切换来分别搜索不同网盘的资源。</p>
        <p class="search-tip">4、搜索关键词尽量不要包含「的」「与」等无关助词，只包含关键词即可</p>
        <p class="search-tip em">5、坚决抵制劣质、违规、隐私、风险版权、其他问题资源，请大家发现一律通过页面举报</p>
      </div>
    </main>

    <SiteFooter />
  </div>
</template>

<script>
import SearchBox from '../components/SearchBox.vue'
import LottiePlayer from '../components/LottiePlayer.vue'
import ThemeToggle from '../components/ThemeToggle.vue'
import SiteFooter from '../components/SiteFooter.vue'
import { search } from '../api'
import { CLOUD_NAMES, RES_TYPE_NAMES } from '../constants'
import loadingAnimation from '../assets/lottie/loading.json'
import searchEmptyAnimation from '../assets/lottie/search-empty.json'

const FILE_TYPE_ICONS = {
  video: '/icons/video.png',
  software: '/icons/software.png',
  doc: '/icons/doc.png',
  music: '/icons/music.png',
  image: '/icons/image.png',
  other: '/icons/file.png',
}

export default {
  name: 'SearchView',
  components: { SearchBox, LottiePlayer, ThemeToggle, SiteFooter },
  data() {
    return {
      keyword: this.$route.query.q || '',
      currentCloud: this.$route.query.cloud || '',
      currentType: this.$route.query.type || '',
      currentSort: this.$route.query.sort || 'relevance',
      currentTime: this.$route.query.time || '',
      currentSize: this.$route.query.fileSize || '',
      filtersExpanded: typeof window !== 'undefined' && window.innerWidth > 700,
      activeFilter: null,
      page: parseInt(this.$route.query.page, 10) || 1,
      result: null,
      loading: false,
      pageSize: 30,
      loadingAnimation,
      searchEmptyAnimation,
    }
  },
  computed: {
    currentQ() {
      return (this.$route.query.q || this.keyword || '').trim()
    },
    totalPages() {
      if (!this.result) return 0
      return Math.ceil(this.result.total / this.pageSize)
    },
    hasActiveFilter() {
      return !!(this.currentType || this.currentTime || this.currentSize || (this.currentSort && this.currentSort !== 'relevance'))
    },
    cloudTabs() {
      return [
        { value: '', label: '全部', icon: '' },
        { value: 'baidu', label: '百度网盘', icon: '☁' },
        { value: 'aliyun', label: '阿里网盘', icon: '🚀' },
        { value: 'quark', label: '夸克网盘', icon: '⚡' },
        { value: 'xunlei', label: '迅雷网盘', icon: '🌊' },
      ]
    },
    sizeOptions() {
      return [
        { value: 'large', label: '大于2GB' },
        { value: 'medium', label: '200MB-2GB' },
        { value: 'small', label: '20MB-200MB' },
        { value: 'tiny', label: '小于20MB' },
      ]
    },
    timeOptions() {
      return [
        { value: 'week', label: '最近一周' },
        { value: 'month', label: '最近一月' },
        { value: 'halfyear', label: '最近半年' },
        { value: 'year', label: '最近一年' },
      ]
    },
    sortOptions() {
      return [
        { value: 'relevance', label: '相关度' },
        { value: 'date', label: '最新' },
        { value: 'size', label: '大小' },
      ]
    },
    currentTypeLabel() {
      if (!this.currentType) return '文件类型'
      return RES_TYPE_NAMES[this.currentType] || '文件类型'
    },
    currentSizeLabel() {
      if (!this.currentSize) return '文件大小'
      const s = this.sizeOptions.find((o) => o.value === this.currentSize)
      return s ? s.label : '文件大小'
    },
    currentTimeLabel() {
      if (!this.currentTime) return '文件时间'
      const t = this.timeOptions.find((o) => o.value === this.currentTime)
      return t ? t.label : '文件时间'
    },
    currentSortLabel() {
      const s = this.sortOptions.find((o) => o.value === this.currentSort)
      return s ? s.label : '排序'
    },
    currentCloudName() {
      if (!this.currentCloud) return ''
      return CLOUD_NAMES[this.currentCloud] || ''
    },
    pageList() {
      const total = this.totalPages
      const cur = this.page
      if (total <= 10) {
        return Array.from({ length: total }, (_, i) => i + 1)
      }
      if (cur - 1 <= 5) {
        return [...Array.from({ length: cur + 1 }, (_, i) => i + 1), '...', total - 1, total]
      }
      if (total - cur <= 5) {
        return [1, 2, '...', ...Array.from({ length: total - cur + 2 }, (_, i) => cur - 1 + i)]
      }
      return [1, 2, '...', cur - 1, cur, cur + 1, '...', total - 1, total]
    },
  },
  watch: {
    $route() {
      this.syncFromRoute()
      this.doSearch()
    },
  },
  mounted() {
    document.addEventListener('click', this.closeFilter)
    this.doSearch()
  },
  beforeUnmount() {
    document.removeEventListener('click', this.closeFilter)
  },
  methods: {
    syncFromRoute() {
      this.keyword = this.$route.query.q || ''
      this.currentCloud = this.$route.query.cloud || ''
      this.currentType = this.$route.query.type || ''
      this.currentSort = this.$route.query.sort || 'relevance'
      this.currentTime = this.$route.query.time || ''
      this.currentSize = this.$route.query.fileSize || ''
      this.page = parseInt(this.$route.query.page, 10) || 1
    },
    updateRoute() {
      const query = {}
      if (this.currentQ) query.q = this.currentQ
      if (this.currentCloud) query.cloud = this.currentCloud
      if (this.currentType) query.type = this.currentType
      if (this.currentSort && this.currentSort !== 'relevance') query.sort = this.currentSort
      if (this.currentTime) query.time = this.currentTime
      if (this.currentSize) query.fileSize = this.currentSize
      if (this.page > 1) query.page = this.page
      this.$router.push({ path: '/search', query }).catch(() => {})
    },
    onSearch(q) {
      this.keyword = q
      this.page = 1
      this.updateRoute()
    },
    onSearchInput(q) {
      this.keyword = q
    },
    toggleFilter(name) {
      this.activeFilter = this.activeFilter === name ? null : name
    },
    closeFilter() {
      this.activeFilter = null
    },
    setFilter(kind, value) {
      if (kind === 'type') this.currentType = value
      if (kind === 'size') this.currentSize = value
      if (kind === 'sort') this.currentSort = value
      if (kind === 'time') this.currentTime = value
      this.activeFilter = null
      this.page = 1
      this.updateRoute()
    },
    setCloud(value) {
      this.currentCloud = value
      this.page = 1
      this.updateRoute()
    },
    clearFilters() {
      this.currentType = ''
      this.currentSize = ''
      this.currentTime = ''
      this.currentSort = 'relevance'
      this.page = 1
      this.updateRoute()
    },
    goPage(p) {
      this.page = p
      this.updateRoute()
      window.scrollTo({ top: 0, behavior: 'smooth' })
    },
    async doSearch() {
      if (!this.currentQ) {
        this.result = null
        return
      }
      this.loading = true
      this.result = null
      try {
        this.result = await search({
          q: this.currentQ,
          cloud: this.currentCloud,
          type: this.currentType,
          sort: this.currentSort,
          time: this.currentTime,
          fileSize: this.currentSize,
          page: this.page,
          size: this.pageSize,
        })
      } catch (err) {
        this.$toast(err.message)
        this.result = { total: 0, items: [], took_ms: 0, size: this.pageSize }
      } finally {
        this.loading = false
      }
    },
    cloudName(t) {
      return CLOUD_NAMES[t] || '其他'
    },
    resTypeName(t) {
      return RES_TYPE_NAMES[t] || '其他'
    },
    fileTypeIcon(item) {
      return FILE_TYPE_ICONS[item.res_type] || FILE_TYPE_ICONS.other
    },
    formatDate(iso) {
      if (!iso) return ''
      return iso.substr(0, 10)
    },
    formatSize(bytes) {
      if (!bytes) return ''
      if (bytes < 1024) return bytes + ' B'
      if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
      if (bytes < 1073741824) return (bytes / 1048576).toFixed(1) + ' MB'
      return (bytes / 1073741824).toFixed(1) + ' GB'
    },
    highlightHtml(title, keyword) {
      if (!keyword || !keyword.trim()) return title
      const tokens = keyword.trim().split(/\s+/).filter(Boolean)
      let html = title
      for (const token of tokens) {
        const escaped = token.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
        html = html.replace(new RegExp(escaped, 'gi'), (m) => `<mark>${m}</mark>`)
      }
      return html
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

.header {
  position: sticky;
  top: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  border-bottom: 1px solid #ebeef5;
  z-index: 50;
}

.header-inner {
  display: flex;
  align-items: center;
  gap: 16px;
  height: 56px;
}

.logo.small {
  font-size: 20px;
  font-weight: 600;
  color: var(--text-primary);
  text-decoration: none;
  flex-shrink: 0;
}

.search-wrap {
  flex: 1;
  max-width: 560px;
}

.header nav {
  margin-left: auto;
  display: flex;
  gap: 16px;
  flex-shrink: 0;
  align-items: center;
}

.header nav a {
  color: var(--text-secondary);
  font-size: 14px;
  transition: color var(--transition-fast);
  text-decoration: none;
}

.header nav a:hover {
  color: var(--primary);
}

.main {
  padding-top: 20px;
  padding-bottom: 40px;
}

.filter-wrap {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.filter-toggle {
  display: none;
}

.filter-body {
  display: flex;
  align-items: center;
  width: 100%;
}

.filter-label {
  margin-right: 10px;
  font-size: 14px;
  color: var(--text-secondary);
  flex-shrink: 0;
}

.filter-item {
  padding: 4px 15px;
  border-radius: 3px;
  border: 1px solid #ddd;
  margin-right: 15px;
  cursor: pointer;
  position: relative;
  transition: all .2s ease;
  font-size: 13px;
  color: var(--text-regular);
  display: inline-flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
}

.filter-item .arrow {
  vertical-align: 2px;
  transition: transform .2s ease;
}

.filter-item.active,
.filter-item:hover {
  background-color: #eee;
}

.filter-item ul {
  top: 35px;
  left: 0;
  width: 130px;
  z-index: 10;
  position: absolute;
  list-style: none;
  margin: 0;
  padding: 0;
  border-radius: 4px;
  border: 1px solid #ddd;
  background-color: #fff;
  box-shadow: 0 6px 12px rgba(0,0,0,.175);
}

.filter-item ul li {
  border-bottom: 1px solid #ddd;
  padding: 8px 15px;
  transition: all .2s ease;
  font-size: 13px;
  color: var(--text-regular);
}

.filter-item ul li:last-child {
  border-bottom: none;
}

.filter-item ul li:hover {
  background-color: #eee;
  color: var(--primary);
}

.clear-button {
  padding: 6px 15px;
  color: #fff;
  display: inline-block;
  font-weight: 700;
  border-radius: 4px;
  cursor: pointer;
  background-color: var(--primary);
  transition: all .2s ease;
  font-size: 13px;
}

.clear-button:hover {
  background-color: var(--primary-dark);
}

.clear-button .clear {
  height: 16px;
  vertical-align: middle;
}

.tab-wrap {
  display: flex;
  align-items: center;
  margin-bottom: 15px;
  flex-wrap: wrap;
  gap: 10px;
}

.tab-label {
  font-size: 14px;
  color: var(--text-secondary);
}

.tab-item {
  border-radius: 4px;
  border: 1px solid #ddd;
  font-size: 12px;
  padding: 5px 10px;
  display: inline-flex;
  align-items: center;
  cursor: pointer;
  transition: all .2s ease;
  color: var(--text-regular);
}

.tab-item .tab-icon {
  margin-right: 4px;
  font-size: 14px;
}

.tab-item.active {
  background-color: var(--primary);
  color: #fff;
  border-color: var(--primary);
}

.tab-item:hover {
  background-color: #eee;
}

.tab-item.active:hover {
  background-color: var(--primary-dark);
}

.search-result {
  position: relative;
}

.result-inner {
  position: relative;
}

.search-tip {
  font-size: 14px;
  margin-bottom: 10px;
  margin-top: 15px;
  color: var(--text-secondary);
}

.search-tip .em {
  color: var(--primary);
  font-weight: 700;
}

.result-wrap {
  display: flex;
  flex-direction: column;
}

.resource-item-wrap {
  color: inherit;
}

.resource-item-wrap.invalid .resource-item {
  filter: grayscale(100%);
}

.resource-item-wrap.invalid .resource-title a {
  text-decoration: line-through;
  color: #999 !important;
}

.resource-item-wrap.invalid .resource-info mark {
  color: inherit;
  text-decoration: underline;
}

.resource-item {
  padding: 20px 0;
  display: flex;
  border-bottom: 1px dashed #eee;
}

.resource-item .filetype {
  height: 30px;
  margin-right: 15px;
  flex: none;
}

.resource-item .resource-info {
  flex: auto;
}

.resource-item .resource-info .resource-title {
  word-break: break-all;
  font-size: 16px;
  color: #333;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
  overflow: hidden;
  margin: 0;
  font-weight: 400;
}

.resource-item .resource-info .resource-title a {
  color: inherit;
  cursor: pointer;
  text-decoration: none;
}

.resource-item .resource-info .resource-title a:hover {
  color: var(--primary);
  text-decoration: underline;
}

.resource-item .resource-info mark {
  color: #c184ff;
  background-color: transparent;
  text-decoration: underline;
}

.resource-item .detail-wrap {
  margin-top: 15px;
  padding: 10px;
  border-radius: 2px;
  background-color: #f8f8f8;
  width: 100%;
}

.resource-item .detail-wrap .detail-item-wrap span {
  font-size: 13px;
  display: inline;
  line-height: 1.8;
  color: #666;
}

.resource-item .detail-wrap .detail-item-wrap .detail-item-title {
  font-weight: 400;
  position: relative;
  padding-left: 15px;
  display: inline;
  word-break: break-all;
}

.resource-item .detail-wrap .detail-item-wrap .detail-item-title:before {
  content: "|-";
  position: absolute;
  top: -6px;
  left: 0;
}

.resource-item .resource-meta {
  margin-top: 15px;
  font-size: 12px;
  color: #666;
  position: relative;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
}

.resource-item .resource-meta .meta-item {
  margin-right: 6px;
  display: inline-block;
  border-radius: 2px;
  padding-right: 4px;
  border: 1px solid #ddd;
  background-color: #f8f8f8;
}

.resource-item .resource-meta .meta-item .label {
  display: inline-block;
  padding: 2px 4px;
  margin-right: 4px;
  background: #f5f5f5;
  border-right: 1px solid #ddd;
}

.resource-item .resource-meta .meta-item .em {
  font-weight: 700;
}

.resource-item .resource-meta .tag {
  height: 24px;
  margin-left: 4px;
  border-radius: 2px;
  padding: 3px 5px;
  font-size: 12px;
  background: var(--primary-bg);
  color: var(--primary);
  border: 1px solid #f0e1ff;
  display: inline-flex;
  align-items: center;
}

.resource-item .resource-meta .success {
  color: #67c23a;
}

.resource-item .resource-meta .warning {
  color: #e6a23c;
}

.resource-item .resource-meta .em {
  font-weight: 700;
}

.resource-item .other-info {
  min-width: 120px;
}

.resource-item .other-info .time {
  text-align: right;
  color: #999;
  font-size: 12px;
  margin: 0;
}

.pager-wrap {
  text-align: center;
  margin-top: 30px;
}

.pager-wrap .pc-pager-wrap {
  display: flex;
  justify-content: center;
  gap: 0;
}

.pager-wrap .mobile-pager-wrap {
  display: none;
}

.pager-wrap .pager-item {
  color: #666;
  font-size: 13px;
  display: inline-block;
  padding: 6px 12px;
  border: 1px solid #ddd;
  border-right: none;
  text-decoration: none;
  background-color: #fff;
  transition: background-color .2s ease;
  cursor: pointer;
}

.pager-wrap .pager-item:first-child {
  border-top-left-radius: 4px;
  border-bottom-left-radius: 4px;
}

.pager-wrap .pager-item:last-child {
  border-right: 1px solid #ddd;
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
}

.pager-wrap .pager-item.active,
.pager-wrap .pager-item:hover {
  cursor: pointer;
  font-weight: 700;
  color: #fff;
  background-color: var(--primary);
  border-color: var(--primary);
}

.pager-wrap .pager-item.pager-mark {
  cursor: default;
  font-weight: 400;
  background-color: #fff;
  color: #666;
}

.pager-wrap .pager-item.pager-mark:hover {
  border-color: #ddd;
  border-right: none;
  color: #666;
  background-color: #fff;
}

.sensitive {
  text-align: center;
  padding: 30px 0;
}

.sensitive .none-tip {
  margin-top: 30px;
  font-size: 16px;
  margin-bottom: 10px;
  color: var(--text-regular);
}

.sensitive .none-tip .em {
  color: var(--primary);
}

.loading-wrap {
  padding-top: 30px;
  text-align: center;
}

.loading-wrap .animation {
  height: 160px;
}

.search-tip-wrap {
  margin-top: 20px;
  line-height: 2;
}

.search-tip-wrap h4 {
  font-size: 15px;
  color: var(--text-primary);
  margin-bottom: 5px;
}

.search-tip-wrap .search-tip {
  color: #666;
  font-size: 13px;
  margin: 0;
}

.search-tip-wrap .search-tip.em {
  color: var(--primary);
}

@media (max-width: 700px) {
  .inner-block {
    width: 100%;
    padding: 0 15px;
  }

  .header-inner {
    flex-wrap: wrap;
    height: auto;
    padding: 12px 0;
    gap: 12px;
  }

  .search-wrap {
    order: 3;
    max-width: 100%;
    flex-basis: 100%;
  }

  .filter-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 8px 0;
    background: none;
    border: none;
    color: var(--text-primary);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
  }

  .filter-toggle__arrow--open {
    transform: rotate(180deg);
  }

  .filter-toggle svg {
    transition: transform 0.2s ease;
  }

  .filter-wrap--collapsed {
    padding: 0;
  }

  .filter-body {
    flex-wrap: wrap;
    gap: 8px;
  }

  .filter-label {
    display: none;
  }

  .filter-item {
    font-size: 0;
    width: 24%;
    padding: 6px 2px;
    text-align: center;
    background-color: #eee;
    margin-right: 0;
    border-radius: 2px;
    justify-content: center;
  }

  .filter-item span {
    font-size: 12px;
  }

  .filter-item .arrow {
    display: none;
  }

  .filter-item ul {
    font-size: 13px;
    top: 30px;
    width: 90px;
  }

  .filter-item ul li {
    font-size: 11px;
    padding: 6px 8px;
  }

  .clear-button {
    display: none;
  }

  .tab-wrap .tab-label {
    display: none;
  }

  .tab-item {
    font-size: 11px;
    padding: 4px 8px;
  }

  .resource-item {
    display: block;
    position: relative;
    padding: 10px 0 10px 26px;
  }

  .resource-item .filetype {
    position: absolute;
    top: 15px;
    height: 16px;
    left: 0;
    width: auto;
  }

  .resource-item .resource-info .resource-title {
    font-size: 14px;
  }

  .resource-item .resource-info .detail-wrap {
    margin-top: 5px;
  }

  .resource-item .resource-info .detail-wrap .detail-item-wrap span {
    font-size: 11px;
  }

  .resource-item .resource-info .resource-meta {
    margin-top: 10px;
  }

  .resource-item .other-info {
    margin-top: 10px;
    margin-left: 0;
    min-width: 0;
  }

  .resource-item .other-info .time {
    text-align: left;
  }

  .pager-wrap {
    margin-top: 15px;
  }

  .pager-wrap .pc-pager-wrap {
    display: none;
  }

  .pager-wrap .mobile-pager-wrap {
    display: flex;
    justify-content: center;
    gap: 10px;
  }

  .pager-wrap .mobile-pager-wrap .pager-item {
    width: 40%;
    text-align: center;
    border-radius: 4px;
    border: 1px solid #ddd;
  }
}
</style>
